<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Log;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LogController.
 *
 * Webmaster logs
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development', schemes: '%protocol%')]
class LogController extends AdminController
{
    /**
     * Index Logs.
     *
     * @throws \Exception
     */
    #[Route('/logs', name: 'admin_logs', methods: 'GET')]
    public function logs(Request $request, string $logDir): Response
    {
        $this->setLogsAsRead();

        $logFiles = [];
        $dailyLogs = [];
        $keyFiles = ['dev', 'prod'];
        $filesystem = new Filesystem();

        if ($filesystem->exists($logDir)) {
            $finder = Finder::create();
            $finder->files()->in($logDir);
            foreach ($finder as $file) {
                $explodeFilename = explode('.', $file->getFilename());
                $date = substr($explodeFilename[0], -10);
                $isDate = false !== \DateTime::createFromFormat('Y-m-d', $date);
                if (in_array($explodeFilename[0], $keyFiles)) {
                    $date = substr($explodeFilename[1], -10);
                    $isDate = false !== \DateTime::createFromFormat('Y-m-d', $date);
                }
                if ($isDate) {
                    $dateFormat = abs(intval(str_replace('-', '', $date)));
                    $dailyLogs[$dateFormat][$date][] = $file->getFilename();
                } else {
                    $logFiles[str_replace('.log', '', $file->getFilename())] = $file->getFilename();
                }
            }
        }

        ksort($dailyLogs);
        ksort($logFiles);

        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/development/logs.html.twig', array_merge($this->arguments, [
            'dailyLogs' => array_reverse($dailyLogs),
            'logFiles' => $logFiles,
        ]));
    }

    /**
     * Log file.
     *
     * @throws \Exception
     */
    #[Route('/{website}/log/{file}', name: 'admin_log', methods: 'GET')]
    public function log(Request $request, string $logDir): Response
    {
        $logs = [];
        $fileDir = $logDir.'/'.$request->get('file');
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($fileDir)) {
            $logsContent = file_get_contents($fileDir);
            $splitLogs = preg_split('/\\r\\n|\\r|\\n/', $logsContent);
            foreach ($splitLogs as $log) {
                $date = $this->getStringBetween($log, '[', ']');
                $log = str_replace('['.$date.']', '', $log);
                $matches = explode(':', $log);
                $code = $matches[0];
                $log = str_replace($code.':', '', $log);
                if (!str_contains($log, '-----------------------------') && $date) {
                    $colorMatches = explode('.', $code);
                    $status = !empty($colorMatches[1]) ? strtolower($colorMatches[1]) : 'undefined';
                    if (str_contains($code, 'CRITICAL')) {
                        $status = 'critical';
                    }
                    $logs[] = [
                        'date' => $date ? new \DateTime($date) : $date,
                        'code' => $code,
                        'status' => str_contains($log, 'Deprecated') ? 'warning' : $status,
                        'message' => trim(htmlspecialchars_decode($log)),
                    ];
                }
            }
        }

        return $this->adminRender('admin/page/development/log.html.twig', [
            'file' => $request->get('file'),
            'logs' => array_slice($logs, 0, 50),
        ]);
    }

    /**
     * To clear all logs.
     */
    #[Route('/logs/clear', name: 'admin_log_clear', methods: 'DELETE')]
    public function clear(string $projectDir): JsonResponse
    {
        $logs = $this->coreLocator->em()->getRepository(Log::class)->findAll();
        foreach ($logs as $log) {
            $this->coreLocator->em()->remove($log);
        }
        $this->coreLocator->em()->flush();

        $filesystem = new Filesystem();
        $logsDirname = $projectDir.'/var/log';
        $logsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logsDirname);

        if ($filesystem->exists($logsDirname)) {
            $finder = Finder::create();
            $finder->in($logsDirname);
            foreach ($finder as $file) {
                try {
                    $filesystem->remove($file->getRealPath());
                } catch (\Exception $exception) {
                }
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get string between char.
     */
    private function getStringBetween(string $string, string $start, string $end): ?string
    {
        $string = ' '.$string;
        $ini = strpos($string, $start);
        if (0 == $ini) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    /**
     * To set logs as read.
     */
    private function setLogsAsRead(): void
    {
        $logs = $this->coreLocator->em()->getRepository(Log::class)->findBy(['asRead' => false]);
        foreach ($logs as $log) {
            /* @var Log $log */
            $log->setAsRead(true);
            $this->coreLocator->em()->persist($log);
            $this->coreLocator->em()->flush();
        }
    }
}
