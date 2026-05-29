<?php

use App\Kernel;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// External cron entry point. Runs scheduler:execute in-process (no shell_exec,
// shared-hosting safe), independently of the traffic-triggered heartbeat.
// Protected by a shared secret (?secret= or X-Cron-Secret header) or an IP allowlist.
$cronSecret = (string) ($_ENV['CRON_SECRET'] ?? '');
$cronAllowedIps = array_filter(array_map('trim', explode(',', (string) ($_ENV['CRON_ALLOWED_IPS'] ?? '127.0.0.1,::1'))));
$givenSecret = (string) ($_GET['secret'] ?? $_SERVER['HTTP_X_CRON_SECRET'] ?? '');
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

$secretOk = '' !== $cronSecret && hash_equals($cronSecret, $givenSecret);
$ipOk = '' !== $clientIp && in_array($clientIp, $cronAllowedIps, true);

header('Content-Type: application/json');

if (!$secretOk && !$ipOk) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);

    exit;
}

$kernel = new Kernel((string) ($_SERVER['APP_ENV'] ?? 'prod'), (bool) ($_SERVER['APP_DEBUG'] ?? false));
$application = new Application($kernel);
$application->setAutoExit(false);

$input = new ArrayInput(['command' => 'scheduler:execute']);
$output = new BufferedOutput();

try {
    $returnCode = $application->run($input, $output);
    echo json_encode(['result' => 'Cron executed.', 'code' => $returnCode]);
} catch (\Throwable $exception) {
    // Detail goes to the log only; the protected response stays generic.
    $logger = new Logger('CRON');
    $logger->pushHandler(new RotatingFileHandler(dirname(__DIR__).'/var/log/cron-scheduler.log', 20, Level::Critical));
    $logger->critical('[CRON.PHP] '.$exception->getMessage());

    http_response_code(500);
    echo json_encode(['error' => 'Cron execution failed.']);
}
