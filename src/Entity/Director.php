<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use App\Repository\DirectorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DirectorRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['director:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['director:read']],
            security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            normalizationContext: ['groups' => ['director:read']],
            denormalizationContext: ['groups' => ['director:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            normalizationContext: ['groups' => ['director:read']],
            denormalizationContext: ['groups' => ['director:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            normalizationContext: ['groups' => ['director:read']],
            denormalizationContext: ['groups' => ['director:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'lastname' => 'start',
    'firstname' => 'start',
    'movies.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['dob', 'dod'])]
class Director
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['director:list', 'director:read', 'movie:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 40,
        minMessage: "Le nom doit contenir au moins 2 caractères.",
        maxMessage: "Le nom ne peut pas dépasser 40 caractères."
    )]
    #[Groups(['director:list', 'director:read', 'director:write', 'movie:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 40,
        minMessage: "Le prénom doit contenir au moins 2 caractères.",
        maxMessage: "Le prénom ne peut pas dépasser 40 caractères."
    )]
    #[Groups(['director:list', 'director:read', 'director:write', 'movie:read'])]
    private ?string $firstname = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "La date de naissance est obligatoire.")]
    #[Groups(['director:read', 'director:write'])]
    private ?\DateTime $dob = null;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThan("today", message: "La date de mort doit être dans le passé.")]
    #[Assert\Expression(
        "this.getDod() === null or this.getDod() > this.getDob()",
        message: "La date de mort doit être après à la date de naissance."
    )]
    #[Groups(['director:read', 'director:write'])]
    private ?\DateTime $dod = null;

    /**
     * @var Collection<int, Movie>
     */
    #[ORM\OneToMany(targetEntity: Movie::class, mappedBy: 'director', orphanRemoval: true)]
    #[Groups(['director:read'])]
    private Collection $movies;

    public function __construct()
    {
        $this->movies = new ArrayCollection();
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

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getDob(): ?\DateTime
    {
        return $this->dob;
    }

    public function setDob(\DateTime $dob): static
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
            $movie->setDirector($this);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): static
    {
        if ($this->movies->removeElement($movie)) {
            if ($movie->getDirector() === $this) {
                $movie->setDirector(null);
            }
        }

        return $this;
    }

    /**
     * Nom complet (virtuel)
     */
    #[Groups(['director:list', 'director:read', 'movie:read'])]
    public function getFullName(): string
    {
        return trim($this->lastname . ' ' . $this->firstname);
    }
}
