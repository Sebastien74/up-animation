<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\ScheduledCommandRepository;
use App\Service\Core\Urlizer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ScheduledCommand.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_scheduled_command')]
#[ORM\Entity(repositoryClass: ScheduledCommandRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ScheduledCommand extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'command',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $command = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $arguments = null;

    /**
     * @see http://www.abunchofutils.com/utils/developer/cron-expression-helper/
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $cronExpression = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastExecution = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $lastReturnCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logFile = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $priority = 0;

    /**
     * If true, command will be executed next time regardless cron expression.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $executeImmediately = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $locked = false;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->getLogFile()) {
            $this->setLogFile(Urlizer::urlize($this->getCommand()).'.log');
        }

        $this->setLastExecution(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $this->setLocked(false);

        parent::prePersist();
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): static
    {
        $this->cronExpression = $cronExpression;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLastExecution(): ?\DateTimeInterface
    {
        return $this->lastExecution;
    }

    public function setLastExecution(?\DateTimeInterface $lastExecution): static
    {
        $this->lastExecution = $lastExecution;

        return $this;
    }

    public function getLastReturnCode(): ?int
    {
        return $this->lastReturnCode;
    }

    public function setLastReturnCode(?int $lastReturnCode): static
    {
        $this->lastReturnCode = $lastReturnCode;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): static
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function isExecuteImmediately(): ?bool
    {
        return $this->executeImmediately;
    }

    public function setExecuteImmediately(bool $executeImmediately): static
    {
        $this->executeImmediately = $executeImmediately;

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

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }
}
