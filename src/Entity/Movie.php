<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['movie:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['movie:read']],
            security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            normalizationContext: ['groups' => ['movie:read']],
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            normalizationContext: ['groups' => ['movie:read']],
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            normalizationContext: ['groups' => ['movie:read']],
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'start',
    'description' => 'partial',
    'duration' => 'exact',
    'categories.id' => 'exact',
    'actors.id' => 'exact'
])]
class Movie
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['movie:list', 'movie:read', 'actor:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du film est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 60,
        minMessage: "Le nom du film doit contenir au moins 2 caractères.",
        maxMessage: "Le nom du film ne peut pas dépasser 40 caractères."
    )]
    #[Groups(['movie:list', 'movie:read', 'movie:write', 'actor:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1850,
        maxMessage: "La description ne peut pas dépasser 1850 caractères."
    )]
    #[Groups(['movie:read', 'movie:write'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La durée doit être un nombre positif.")]
    #[Assert\LessThanOrEqual(1000, message: "La durée maximale est de 200 minutes.")]
    #[Groups(['movie:read', 'movie:write', 'movie:list'])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['movie:read', 'movie:write', 'movie:list'])]
    private ?\DateTime $releaseDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'movies')]
    #[Groups(['movie:read', 'movie:write'])]
    private Collection $categories;

    /**
     * @var Collection<int, Actor>
     * CHANGEMENT ICI : mappedBy au lieu de inversedBy
     */
    #[ORM\ManyToMany(targetEntity: Actor::class, mappedBy: 'movies')]
    #[Groups(['movie:read', 'movie:write'])]
    private Collection $actors;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(
        min: 1,
        maxMessage: "Le nombre d'entrée ne peut pas être inférieur a 500."
    )]
    #[Groups(['movie:read', 'movie:write'])]
    private ?int $nbEntries = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "L'URL du film n'est pas valide.", requireTld: true)]
    #[Groups(['movie:read', 'movie:write'])]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(
        min: 4,
        maxMessage: "Le budget ne peut pas être inférieur a 20 000."
    )]
    #[Groups(['movie:read', 'movie:write'])]
    private ?float $budget = null;

    #[ORM\ManyToOne(inversedBy: 'movies')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['movie:read', 'movie:write'])]
    private ?Director $director = null;

    #[ORM\ManyToOne(inversedBy: 'movies')]
    #[Groups(['movie:read', 'movie:write', 'movie:list'])]
    private ?MediaObject $image = null;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->actors = new ArrayCollection();
        $this->createAt = new DateTimeImmutable();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getReleaseDate(): ?\DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTime $releaseDate): static
    {
        $this->releaseDate = $releaseDate;
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

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addMovie($this);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeMovie($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Actor>
     */
    public function getActors(): Collection
    {
        return $this->actors;
    }

    public function addActor(Actor $actor): static
    {
        if (!$this->actors->contains($actor)) {
            $this->actors->add($actor);
            $actor->addMovie($this);
        }
        return $this;
    }

    public function removeActor(Actor $actor): static
    {
        if ($this->actors->removeElement($actor)) {
            $actor->removeMovie($this);
        }
        return $this;
    }

    public function getNbEntries(): ?int
    {
        return $this->nbEntries;
    }

    public function setNbEntries(?int $nbEntries): static
    {
        $this->nbEntries = $nbEntries;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getDirector(): ?Director
    {
        return $this->director;
    }

    public function setDirector(?Director $director): static
    {
        $this->director = $director;
        return $this;
    }

    public function getImage(): ?MediaObject
    {
        return $this->image;
    }

    public function setImage(?MediaObject $image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setMovie($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getMovie() === $this) {
                $review->setMovie(null);
            }
        }

        return $this;
    }

    /**
     * Durée formatée (virtuel)
     */
    #[Groups(['movie:read'])]
    public function getFormattedDuration(): string
    {
        if ($this->duration === null) {
            return 'N/A';
        }
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        return sprintf('%dh %02dmin', $hours, $minutes);
    }

    /**
     * Nombre d'acteurs (virtuel)
     */
    #[Groups(['movie:list', 'movie:read'])]
    public function getActorCount(): int
    {
        return $this->actors->count();
    }
}
