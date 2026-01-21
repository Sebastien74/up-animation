<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CronController.
 *
 * To execute cron
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/cron-scheduler', schemes: '%protocol%')]
class CronController extends BaseCommand
{
    /**
     * Execute cron.
     *
     * @throws \Exception
     */
    #[Route('/run', name: 'admin_cron_scheluder', methods: 'GET')]
    public function run(KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput(['command' => 'scheduler:execute']);
        $output = new BufferedOutput();
        $application->run($input, $output);

        return new JsonResponse(['success' => true, 'reload' => true]);
    }
}
