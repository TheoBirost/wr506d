<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\MediaObject;
use App\Entity\Movie;
use App\Repository\ActorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ActorRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['actor:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['actor:read']],
            security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            normalizationContext: ['groups' => ['actor:read']],
            denormalizationContext: ['groups' => ['actor:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            normalizationContext: ['groups' => ['actor:read']],
            denormalizationContext: ['groups' => ['actor:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            normalizationContext: ['groups' => ['actor:read']],
            denormalizationContext: ['groups' => ['actor:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(SearchFilter::class, properties: [
    'lastname' => 'start',
    'firstname' => 'start',
    'movies.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['dob', 'dod', 'createAt'])]
class Actor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actor:list', 'actor:read', 'movie:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de famille est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 40,
        minMessage: "Le nom doit contenir au moins 2 caractères.",
        maxMessage: "Le nom ne peut pas dépasser 40 caractères."
    )]
    #[Groups(['actor:read', 'actor:write', 'movie:read', 'actor:list'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le prénom ne peut pas dépasser 255 caractères."
    )]
    #[Groups(['actor:read', 'actor:write', 'movie:read', 'actor:list'])]
    private ?string $firstname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['actor:read', 'actor:write'])]
    private ?\DateTime $dob = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['actor:read', 'actor:write'])]
    private ?\DateTime $dod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1850,
        maxMessage: "La biographie ne peut pas dépasser 1850 caractères."
    )]
    #[Groups(['actor:read', 'actor:write'])]
    private ?string $bio = null;

    /**
     * @var Collection<int, Movie>
     * C'est Actor qui possède la relation (inversedBy)
     */
    #[ORM\ManyToMany(targetEntity: Movie::class, inversedBy: 'actors', cascade: ['persist'])]
    #[Groups(['actor:read'])]
    private Collection $movies;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\ManyToOne(inversedBy: 'actors')]
    #[Groups(['actor:read', 'actor:write', 'movie:read', 'actor:list'])]
    private ?MediaObject $photo = null;

    public function __construct()
    {
        $this->movies = new ArrayCollection();
        $this->createAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getDob(): ?\DateTime
    {
        return $this->dob;
    }

    public function setDob(?\DateTime $dob): static
    {
        $this->dob = $dob;
        return $this;
    }

    public function getDod(): ?\DateTime
    {
        return $this->dod;
    }

    public function setDod(?\DateTime $dod): static
    {
        $this->dod = $dod;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    /**
     * @return Collection<int, Movie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): static
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
            $movie->addActor($this);
        }
        return $this;
    }

    public function removeMovie(Movie $movie): static
    {
        if ($this->movies->removeElement($movie)) {
            $movie->removeActor($this);
        }
        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createAt = new DateTimeImmutable();
    }

    public function getPhoto(): ?MediaObject
    {
        return $this->photo;
    }

    public function setPhoto(?MediaObject $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * Nom complet (virtuel)
     */
    #[Groups(['actor:list', 'actor:read', 'movie:read'])]
    public function getFullName(): string
    {
        return trim($this->lastname . ' ' . $this->firstname);
    }

    /**
     * Âge calculé (virtuel)
     */
    #[Groups(['actor:read'])]
    public function getAge(): ?int
    {
        if ($this->dob === null) {
            return null;
        }
        $reference = $this->dod ?? new DateTimeImmutable();

        return $this->dob->diff($reference)->y;
    }
}
