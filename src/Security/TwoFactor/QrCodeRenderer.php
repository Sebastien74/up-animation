<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Renders an inline SVG QR code for an otpauth:// URI.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class QrCodeRenderer
{
    public function renderSvg(string $content, int $size = 220, int $margin = 10): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build()->getString();
    }
}
