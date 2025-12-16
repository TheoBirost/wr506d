<?php

namespace App\Service;

use App\Entity\User;
use OTPHP\TOTP;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class TwoFactorService
{
    private string $issuer;

    public function __construct(string $appName = "Movie's 2FA : ")
    {
        $this->issuer = $appName;
    }

    /**
     * Generate a new TOTP secret for a user
     */
    public function generateSecret(): string
    {
        $totp = TOTP::generate();
        return $totp->getSecret();
    }

    /**
     * Get TOTP instance for a user
     */
    private function getTOTP(User $user): TOTP
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            throw new \RuntimeException('User does not have a 2FA secret');
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getFirstname() ?? 'user');
        $totp->setIssuer($this->issuer);

        return $totp;
    }

    /**
     * Generate provisioning URI for QR code
     */
    public function getProvisioningUri(User $user): string
    {
        return $this->getTOTP($user)->getProvisioningUri();
    }

    /**
     * Generate QR code as base64 image data
     */
    public function getQrCode(User $user): string
    {
        $provisioningUri = $this->getProvisioningUri($user);

        try {
            // Tentative avec instanciation directe de Builder (compatible v4, v5, v6 si create() n'existe pas)
            $builder = new Builder(
                writer: new SvgWriter(),
                writerOptions: [],
                validateResult: false,
                data: $provisioningUri,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );

            $result = $builder->build();
            return 'data:image/svg+xml;base64,' . base64_encode($result->getString());

        } catch (\Throwable $e) {
            // Fallback : tentative très basique si Builder échoue
            try {
                // Si on est sur une version très ancienne ou très différente
                // On essaie de construire manuellement si les classes existent
                if (class_exists('Endroid\QrCode\QrCode')) {
                    $qrCode = new \Endroid\QrCode\QrCode($provisioningUri);
                    // On ne configure rien d'autre pour éviter les erreurs de méthode
                    $writer = new SvgWriter();
                    $result = $writer->write($qrCode);
                    return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
                }
            } catch (\Throwable $e2) {
                 throw new \RuntimeException('QR Code generation failed: ' . $e->getMessage() . ' | Fallback: ' . $e2->getMessage());
            }

            throw new \RuntimeException('QR Code generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if (!$secret) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);

        // Vérifie le code avec une fenêtre de ±2 (tolérance de 60 sec)
        return $totp->verify($code, null, 2);
    }

    /**
     * Generate backup codes
     * @return list<string>
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate 8-character alphanumeric codes
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        return $codes;
    }

    /**
     * Hash backup codes for storage
     * @param list<string> $codes
     * @return list<string>
     */
    public function hashBackupCodes(array $codes): array
    {
        return array_map(fn($code) => hash('sha256', $code), $codes);
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $hashedCode = hash('sha256', $code);
        $backupCodes = $user->getTwoFactorBackupCodes();

        if ($backupCodes === null) {
            return false;
        }

        return in_array($hashedCode, $backupCodes, true);
    }

    /**
     * Remove used backup code
     */
    public function removeBackupCode(User $user, string $code): void
    {
        $hashedCode = hash('sha256', $code);
        $backupCodes = $user->getTwoFactorBackupCodes();

        if ($backupCodes === null) {
            return;
        }

        $backupCodes = array_filter(
            $backupCodes,
            fn($storedCode) => $storedCode !== $hashedCode
        );

        $user->setTwoFactorBackupCodes(array_values($backupCodes));
    }
}
