<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$cronSecret = (string) ($_ENV['CRON_SECRET'] ?? '');
$cronAllowedIps = array_filter(array_map('trim', explode(',', (string) ($_ENV['CRON_ALLOWED_IPS'] ?? '127.0.0.1,::1'))));
$givenSecret = (string) ($_GET['secret'] ?? $_SERVER['HTTP_X_CRON_SECRET'] ?? '');
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

$secretOk = '' !== $cronSecret && hash_equals($cronSecret, $givenSecret);
$ipOk = '' !== $clientIp && in_array($clientIp, $cronAllowedIps, true);

if (!$secretOk && !$ipOk) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$asynchronous = true;

/**
 * CronScheduler
 *
 * To run Cron Scheduler
 * Recommended run method, execute(). If not working, run executeProcedural()
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CronScheduler
{
    private static string $cmd = 'scheduler:execute';
    private string $environment;
    private bool $asynchronous = false;
    private string $dirname;
    private string $phpExecutable = 'php';
    private Logger $logger;

    /**
     * Cron constructor.
     *
     * @param Logger $logger
     * @param bool $asynchronous
     */
    public function __construct(Logger $logger, bool $asynchronous = false)
    {
        $this->dirname = dirname(__DIR__);
        $this->environment = (string) ($_ENV['APP_ENV'] ?? 'prod');
        $this->asynchronous = $asynchronous;

        $this->logger = $logger;
        $this->logger->info('================== Start ==================');
        $this->logger->info('Environment: ' . $this->environment);
        $this->logger->info('Asynchronous: ' . $this->asynchronous);
    }

    /**
     * Run command: Procedural PHP execution
     *
     * @throws Exception
     */
    public function executeProcedural(): void
    {
        $this->logger->info('Executed command method : executeProcedural()');

        $this->setPHPExecutable();
        $this->executeShellCommand();
    }

    /**
     * Get php executable
     *
     * @throws Exception
     */
    private function setPHPExecutable(): void
    {
        $phpPath = $this->phpExecutable;
        if ($this->environment !== 'local') {
            $phpFinder = new PhpExecutableFinder;
            if (!$phpPath = $phpFinder->find()) {
                $this->logger->critical('The php executable could not be found');
                throw new Exception('The php executable could not be found, add it to your PATH environment variable and try again');
            }
        }
        $this->phpExecutable = $phpPath;
        $this->logger->info('PHP executable : ' . $phpPath);
    }

    /**
     * Executes Shell command.
     *
     * @return void
     */
    private function executeShellCommand(): void
    {
        $filesystem = new Filesystem();
        $output = 'Execution failed!!';
        $consoleDirname = $this->dirname . '/bin/console';
        $asynchronous = filter_var($this->asynchronous, FILTER_VALIDATE_BOOLEAN);

        if ($this->phpExecutable && $filesystem->exists($consoleDirname) && $filesystem->exists($this->phpExecutable)
            || $this->environment === 'local' && $this->phpExecutable === 'php') {

            /** If windows, else */
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && $asynchronous) {
                $executableCmd = $this->phpExecutable . ' ' . $consoleDirname . ' ' . self::$cmd . " > NUL";
                $this->logger->info('Executable command: ' . $executableCmd);
                $output = system($executableCmd);
            } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !$asynchronous) {
                $executableCmd = $this->phpExecutable . ' ' . $consoleDirname . ' ' . self::$cmd;
                $this->logger->info('Executable command: ' . $executableCmd);
                $output = system($executableCmd);
            } elseif ($asynchronous) {
                $executableCmd = $this->phpExecutable . ' ' . $consoleDirname . ' ' . self::$cmd . ' >/dev/null';
                $this->logger->info('Executable command: ' . $executableCmd);
                $output = shell_exec($executableCmd);
            } else {
                $executableCmd = $this->phpExecutable . ' ' . $consoleDirname . ' ' . self::$cmd;
                $this->logger->info('Executable command: ' . $executableCmd);
                $output = shell_exec($executableCmd);
            }
        } else {
            $this->logger->info('Cron OUTPUT : Command not executed');
        }

        $this->logger->info('Cron OUTPUT : ' . $output);
        $this->logger->info('END execution');
    }
}

$logger = new Logger('CRON');
$logger->pushHandler(new RotatingFileHandler(dirname(__DIR__) . '/var/log/cron-scheduler.log', 20, \Monolog\Level::Info));

$scheduler = new CronScheduler($logger, $asynchronous);

header('Content-Type: application/json');

try {
    $scheduler->executeProcedural();
    echo json_encode(['result' => 'Cron successfully executed.']);
} catch (\Throwable $exception) {
    $logger->critical($exception->getMessage());
    echo json_encode(['result' => $exception->getMessage()]);
}

exit();