<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Core\Website;
use App\Entity\Security\Company;
use App\Entity\Security\Logo;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * CompanyManager.
 *
 * Manage Company in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CompanyManager::class, 'key' => 'security_admin_company_form_manager'],
])]
class CompanyManager
{
    /**
     * CompanyManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @prePersist
     */
    public function prePersist(Company $company, Website $website)
    {
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Company $company, Website $website, array $interface, Form $form): void
    {
        $this->setLogo($company, $form);

        $address = $company->getAddress();
        if (!$address->getId()) {
            $address->setCompany($company);
            $this->entityManager->persist($address);
        }

        $this->entityManager->persist($company);
    }

    /**
     * Set Company Logo.
     */
    private function setLogo(Company $company, Form $form): void
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        if ($file instanceof UploadedFile) {
            /** @var Logo $logo */
            $logo = $company->getLogo() ? $company->getLogo() : new Logo();
            $filesystem = new Filesystem();
            $extension = $file->guessExtension();
            $filename = Urlizer::urlize(str_replace('.'.$extension, '', $file->getClientOriginalName())).'-'.md5(uniqid()).'.'.$extension;
            $baseDirname = '/uploads/companies/'.$company->getSecretKey().'/logo/';
            $baseDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDirname);
            $publicDirname = $this->projectDir.'/public/';
            $publicDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicDirname);
            $dirname = $publicDirname.$baseDirname;

            if ($logo->getDirname() && $filesystem->exists($publicDirname.$logo->getDirname()) && !is_dir($publicDirname.$logo->getDirname())) {
                $filesystem->remove($publicDirname.$logo->getDirname());
            }

            $logo->setFilename($filename);
            $logo->setDirname($baseDirname.$filename);

            if (!$logo->getId()) {
                $logo->setCompany($company);
                $company->setLogo($logo);
            }

            $file->move($dirname, $filename);
        }
    }
}
