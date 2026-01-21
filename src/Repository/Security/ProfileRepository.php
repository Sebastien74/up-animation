<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ProfileRepository.
 *
 * @extends ServiceEntityRepository<Profile>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProfileRepository extends ServiceEntityRepository
{
    /**
     * ProfileRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Profile::class);
    }

    /**
     * Check if Address by slug exist.
     */
    public function addressExist(UserInterface $user, string $slug, $asObject = false)
    {
        $profile = $user->getProfile();
        if ($profile instanceof Profile) {
            foreach ($profile->getAddresses() as $address) {
                if ($address->getSlug() === $slug) {
                    return $asObject ? $address : true;
                }
            }
        }

        return false;
    }
}
