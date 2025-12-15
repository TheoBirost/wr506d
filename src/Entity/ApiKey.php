<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Embeddable]
class ApiKey
{
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    #[Assert\Length(exactly: 64)]
    private ?string $hash = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\Length(exactly: 16)]
    private ?string $prefix = null;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function updateLastUsedAt(): self
    {
        $this->lastUsedAt = new DateTimeImmutable();
        return $this;
    }
}
