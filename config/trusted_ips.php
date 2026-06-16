<?php

/**
 * Single source of truth for the operator IP allowlist used by the standalone,
 * dependency-free utility pages (public/error.php, public/check.php) and by the
 * pre-boot error views (config/boot_error.php) to reveal technical details.
 *
 * Add or remove an authorised IP here only.
 */

declare(strict_types=1);

return ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2001:861:43c3:ce70:448f:74b:e526:cdae', '2001:861:43c3:ce70:60b8:f71:1c9:4843'];
