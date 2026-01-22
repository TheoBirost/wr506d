<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\User;

final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $anonymousApiLimiter,
        private readonly RateLimiterFactory $authenticatedApiLimiter,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ne pas limiter les requêtes OPTIONS (Preflight CORS)
        if ($request->isMethod('OPTIONS')) {
            return;
        }

        if (!str_starts_with($request->getPathInfo(), '/api/') ||
            str_starts_with($request->getPathInfo(), '/api/docs')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        $isAuthenticated = $user instanceof User;

        $limiterFactory = $isAuthenticated ? $this->authenticatedApiLimiter : $this->anonymousApiLimiter;
        $identifier = $isAuthenticated ? $user->getUserIdentifier() : $request->getClientIp() ?? 'unknown';

        $limiter = $limiterFactory->create($identifier);
        $limit = $limiter->consume();

        $userCustomLimit = $isAuthenticated ? $user->getLimiter() : null;

        $effectiveLimit = $userCustomLimit ?? $limit->getLimit();
        $consumed = $limit->getLimit() - $limit->getRemainingTokens();
        $remaining = max(0, $effectiveLimit - $consumed);
        $isAccepted = $consumed <= $effectiveLimit;

        $request->attributes->set('_rate_limit', [
            'limit' => $effectiveLimit,
            'remaining' => $remaining,
            'reset' => $limit->getRetryAfter()->getTimestamp(),
        ]);

        if (!$isAccepted) {
            $retryAfter = $limit->getRetryAfter();
            $response = new JsonResponse(
                ['error' => 'Too Many Requests', 'message' => 'Rate limit exceeded.'],
                429,
                [
                    'Retry-After' => $retryAfter->getTimestamp(),
                    'X-RateLimit-Limit' => $effectiveLimit,
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Reset' => $retryAfter->getTimestamp(),
                ]
            );
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_rate_limit')) {
            return;
        }

        $rateLimitInfo = $request->attributes->get('_rate_limit');
        $response = $event->getResponse();

        $response->headers->add([
            'X-RateLimit-Limit' => $rateLimitInfo['limit'],
            'X-RateLimit-Remaining' => $rateLimitInfo['remaining'],
            'X-RateLimit-Reset' => $rateLimitInfo['reset'],
        ]);
    }
}
