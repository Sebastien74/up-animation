<?php

declare(strict_types=1);

namespace App\Service\Delete;

use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\ContactStepForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * ContactDeleteService.
 *
 * Manage Contact Form deletion
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ContactDeleteService::class, 'key' => 'contact_delete_service'],
])]
class ContactDeleteService
{
    private ?Request $request;

    /**
     * ContactDeleteService constructor.
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Execute service.
     */
    public function execute(): void
    {
        $requestName = $this->request->get('formcontact') ? 'formcontact' : ($this->request->get('contactstepform') ? 'contactstepform' : null);
        if ($requestName) {
            $classname = 'formcontact' === $requestName ? ContactForm::class : ContactStepForm::class;
            $contact = $this->entityManager->getRepository($classname)->find($this->request->get($requestName));
            $this->deleteAttachments($contact);
        }
    }

    /**
     * Remove old by day limit.
     *
     * @throws \Exception
     */
    public function removeOldCmd(int $limit = 365): void
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $input = new ArrayInput([
                'command' => 'contacts:remove',
                'limit' => $limit,
            ]);
            $output = new BufferedOutput();
            $application->run($input, $output);
        }
    }

    /**
     * Remove old by day limit.
     *
     * @throws \Exception
     */
    public function removeOld(int $limit = 365): void
    {
        $datetime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $datetime->modify('- '.$limit.' days');

        $flush = false;
        $contacts = $this->entityManager->getRepository(ContactForm::class)
            ->createQueryBuilder('c')
            ->andWhere('c.createdAt < :createdAt')
            ->setParameter('createdAt', $datetime->format('Y-m-d').' 00:00:00')
            ->getQuery()->getResult();

        dd($contacts);

        foreach ($contacts as $contact) {
            $this->deleteAttachments($contact);
            $this->entityManager->remove($contact);
            $flush = true;
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Attachments deletion.
     */
    private function deleteAttachments(ContactForm|ContactStepForm|null $contact = null): void
    {
        if ($contact) {
            $filesystem = new Filesystem();
            $publicDirname = $this->kernel->getProjectDir().'/public';
            foreach ($contact->getContactValues() as $value) {
                if ($value->getValue() && preg_match('/uploads\/emails\//', $value->getValue())) {
                    $fileDirname = $publicDirname.$value->getValue();
                    if ($filesystem->exists($fileDirname)) {
                        $filesystem->remove($fileDirname);
                    }
                }
            }
        }
    }
}
