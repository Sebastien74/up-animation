<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\FeedPostRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FeedPost.
 *
 * Persisted social feed item (Instagram, TikTok, ...).
 * Decouples the front rendering from external API availability:
 * the rendering reads only from this table, the sync command refreshes it.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_feed_post')]
#[ORM\Entity(repositoryClass: FeedPostRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_feed_post_provider_external', columns: ['provider', 'externalId'])]
#[ORM\Index(name: 'idx_feed_post_provider_removed_published', columns: ['provider', 'removedAt', 'publishedAt'])]
class FeedPost
{
    public const string PROVIDER_INSTAGRAM = 'instagram';
    public const string PROVIDER_TIKTOK = 'tiktok';

    public const string MEDIA_TYPE_IMAGE = 'IMAGE';
    public const string MEDIA_TYPE_VIDEO = 'VIDEO';
    public const string MEDIA_TYPE_CAROUSEL = 'CAROUSEL_ALBUM';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::PROVIDER_INSTAGRAM, self::PROVIDER_TIKTOK])]
    private string $provider;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $externalId;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $permalink = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $mediaType = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $mediaLocalPath = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $thumbnailLocalPath = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $removedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $syncedAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    public function __construct()
    {
        $this->syncedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getPermalink(): ?string
    {
        return $this->permalink;
    }

    public function setPermalink(?string $permalink): static
    {
        $this->permalink = $permalink;
        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;
        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): static
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    public function getMediaLocalPath(): ?string
    {
        return $this->mediaLocalPath;
    }

    public function setMediaLocalPath(?string $mediaLocalPath): static
    {
        $this->mediaLocalPath = $mediaLocalPath;
        return $this;
    }

    public function getThumbnailLocalPath(): ?string
    {
        return $this->thumbnailLocalPath;
    }

    public function setThumbnailLocalPath(?string $thumbnailLocalPath): static
    {
        $this->thumbnailLocalPath = $thumbnailLocalPath;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getRemovedAt(): ?DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function setRemovedAt(?DateTimeImmutable $removedAt): static
    {
        $this->removedAt = $removedAt;
        return $this;
    }

    public function getSyncedAt(): DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(DateTimeImmutable $syncedAt): static
    {
        $this->syncedAt = $syncedAt;
        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->removedAt === null;
    }
}
