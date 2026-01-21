<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Service\Core\Urlizer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseSecurity.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[\AllowDynamicProperties] #[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class BaseSecurity extends BaseInterface implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('main')]
    protected ?string $login = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    #[Groups('main')]
    #[Assert\Email]
    protected ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('main')]
    protected ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('main')]
    protected ?string $firstName = null;

    #[Assert\Length(['max' => 4096])]
    protected ?string $plainPassword = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $isOnline = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $confirmEmail = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $agreeTerms = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $agreesTermsAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $tokenDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $tokenRequest = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $tokenRequestDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $tokenRemoveRequest = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $tokenRemoveRequestDate = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    protected ?string $locale = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastActivity = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $resetPassword = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $resetPasswordDate = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $secretKey = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $alerts = [];

    #[ORM\ManyToOne(targetEntity: Group::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Group $group = null;

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->secretKey = md5(uniqid().$this->login);
        $this->tokenDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->resetPasswordDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        parent::prePersist();
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Returns the identifier for this user (e.g. its login or e-mail address).
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * @throws InvalidArgumentException
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        /* @var Group $group */
        $group = $this->group;
        $roles = [];

        if ($group) {
            $filesystem = new Filesystem();
            $documentRoot = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT']);
            $documentRoot = DIRECTORY_SEPARATOR !== substr($documentRoot, -1) ? $documentRoot.DIRECTORY_SEPARATOR : $documentRoot;
            $dirname = str_replace(['\public\\', '/public/'], DIRECTORY_SEPARATOR, $documentRoot).'var/cache/security/roles-'.$group->getSlug().'.cache';
            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
            if ($filesystem->exists($dirname)) {
                $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
                $item = $cache->getItem('group.'.$group->getSlug());
                if ($item->isHit()) {
                    $roles = $item->get();
                }
            }
            if (empty($roles)) {
                $cacheData = [];
                foreach ($group->getRoles() as $role) {
                    $roles[] = $role->getName();
                    $cacheData['group.'.$group->getSlug()][] = $role->getName();
                }
                $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
                $cache->warmUp($cacheData);
            }
        }

        return array_unique($roles);
    }

    /**
     * Generate avatar.
     */
    public function getAvatar(int $size = 30, string $type = 'initialTxt'): ?string
    {
        if ('initialTxt' === $type) {
            $code = $this->firstName.' '.$this->lastName;
            if (' ' == trim($code) || ' ' == $code || empty($code)) {
                $code = $this->login;
            }
            $initials = '';
            $matches = explode(' ', $code);
            foreach ($matches as $match) {
                $initials .= substr($match, 0, 1);
            }
            return strtoupper(Urlizer::urlize(substr($initials, 0, 2)));
        } elseif ('initial' === $type) {
            $code = $this->firstName.'+'.$this->lastName;
            if ('+' === trim($code)) {
                $code = $this->login;
            }

            return 'https://eu.ui-avatars.com/api/?name='.$code.'&background=58a2d9&color=fff&font-size=0.33&size='.$size;
        } else {
            $url = 'https://robohash.org/'.$this->email.sprintf('?size=%dx%d', $size, $size);

            if ('robot' === $type) {
                return $url.'&set=set1';
            } elseif ('monster' === $type) {
                return $url.'&set=set2';
            } elseif ('robot-head' === $type) {
                return $url.'&set=set3';
            } elseif ('cat' === $type) {
                return $url.'&set=set4';
            }
        }

        return null;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        $method = $this instanceof User ? 'get'.ucfirst($_ENV['SECURITY_ADMIN_LOGIN_TYPE'])
            : 'get'.ucfirst($_ENV['SECURITY_FRONT_LOGIN_TYPE']);

        return $this->$method();
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password): void
    {
        $this->plainPassword = $password;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using bcrypt or argon
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getIsOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getConfirmEmail(): ?bool
    {
        return $this->confirmEmail;
    }

    public function setConfirmEmail(bool $confirmEmail): static
    {
        $this->confirmEmail = $confirmEmail;

        return $this;
    }

    public function getAgreesTermsAt(): ?\DateTimeInterface
    {
        return $this->agreesTermsAt;
    }

    public function setAgreesTermsAt(\DateTimeInterface $agreesTermsAt): static
    {
        $this->agreesTermsAt = $agreesTermsAt;

        return $this;
    }

    public function getAgreeTerms(): ?bool
    {
        return $this->agreeTerms;
    }

    public function setAgreeTerms(bool $agreeTerms): static
    {
        $this->agreeTerms = $agreeTerms;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function agreeTerms(): bool
    {
        $this->agreeTerms = true;
        $this->agreesTermsAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        return $this->agreeTerms;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenDate(): ?\DateTimeInterface
    {
        return $this->tokenDate;
    }

    public function setTokenDate(?\DateTimeInterface $tokenDate): static
    {
        $this->tokenDate = $tokenDate;

        return $this;
    }

    public function getTokenRequest(): ?string
    {
        return $this->tokenRequest;
    }

    public function setTokenRequest(?string $tokenRequest): static
    {
        $this->tokenRequest = $tokenRequest;

        return $this;
    }

    public function getTokenRequestDate(): ?\DateTimeInterface
    {
        return $this->tokenRequestDate;
    }

    public function setTokenRequestDate(?\DateTimeInterface $tokenRequestDate): static
    {
        $this->tokenRequestDate = $tokenRequestDate;

        return $this;
    }

    public function getTokenRemoveRequest(): ?string
    {
        return $this->tokenRemoveRequest;
    }

    public function setTokenRemoveRequest(?string $tokenRemoveRequest): static
    {
        $this->tokenRemoveRequest = $tokenRemoveRequest;

        return $this;
    }

    public function getTokenRemoveRequestDate(): ?\DateTimeInterface
    {
        return $this->tokenRemoveRequestDate;
    }

    public function setTokenRemoveRequestDate(?\DateTimeInterface $tokenRemoveRequestDate): static
    {
        $this->tokenRemoveRequestDate = $tokenRemoveRequestDate;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastActivity(): ?\DateTimeInterface
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?\DateTimeInterface $lastActivity): static
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    public function getResetPassword(): ?bool
    {
        return $this->resetPassword;
    }

    public function setResetPassword(bool $resetPassword): static
    {
        $this->resetPassword = $resetPassword;

        return $this;
    }

    public function getResetPasswordDate(): ?\DateTimeInterface
    {
        return $this->resetPasswordDate;
    }

    public function setResetPasswordDate(\DateTimeInterface $resetPasswordDate): static
    {
        $this->resetPasswordDate = $resetPasswordDate;

        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): static
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getAlerts(): ?array
    {
        return $this->alerts;
    }

    public function setAlerts(?array $alerts): static
    {
        $this->alerts = $alerts;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;

        return $this;
    }
}
