<?php
// src/EventSubscriber/MediaObjectSubscriber.php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\MediaObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vich\UploaderBundle\Storage\StorageInterface;

final class MediaObjectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StorageInterface $storage
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPreWrite', EventPriorities::PRE_WRITE],
        ];
    }

    public function onPreWrite(ViewEvent $event): void
    {
        $mediaObject = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$mediaObject instanceof MediaObject || Request::METHOD_POST !== $method) {
            return;
        }

        // Récupérer le fichier uploadé
        $uploadedFile = $event->getRequest()->files->get('file');

        if ($uploadedFile) {
            $mediaObject->file = $uploadedFile;
        }
    }
}
