<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use Random\RandomException;

/**
 * Generates one-time recovery codes used as backup factor when TOTP device is lost.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BackupCodeGenerator
{
    private const int CODE_COUNT = 8;
    private const int CODE_LENGTH = 10;

    /**
     * @return list<string>
     *
     * @throws RandomException
     */
    public function generate(int $count = self::CODE_COUNT): array
    {
        $codes = [];
        for ($i = 0; $i < $count; ++$i) {
            $codes[] = $this->generateCode();
        }

        return $codes;
    }

    /**
     * @throws RandomException
     */
    private function generateCode(): string
    {
        $bytes = random_bytes(self::CODE_LENGTH);
        $code = strtoupper(substr(bin2hex($bytes), 0, self::CODE_LENGTH));

        return substr($code, 0, 5).'-'.substr($code, 5, 5);
    }
}
