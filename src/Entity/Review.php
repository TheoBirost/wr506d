<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_REVIEW_USER_MOVIE', columns: ['user_id', 'movie_id'])]
#[ApiResource(
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
    operations: [
        // Lecture : réservée aux comptes connectés, comme les fiches de films.
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),

        // `securityPostDenormalize` est évalué APRÈS l'hydratation : c'est le
        // seul moment où l'on peut vérifier que l'avis créé est bien signé par
        // son auteur. La propriété `user` étant dans le groupe review:write,
        // n'importe quel inscrit pouvait sinon publier un avis au nom d'autrui.
        new Post(
            security: "is_granted('ROLE_USER')",
            securityPostDenormalize: "object.getUser() == user or is_granted('ROLE_ADMIN')",
            securityPostDenormalizeMessage: "Un avis ne peut être publié qu'en votre propre nom."
        ),

        // Modification et suppression : l'auteur ou un administrateur.
        // `is_granted('ROLE_USER')` seul laissait n'importe quel inscrit
        // réécrire ou supprimer l'avis de n'importe qui.
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
            securityMessage: "Vous ne pouvez modifier que vos propres avis."
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
            securityMessage: "Vous ne pouvez modifier que vos propres avis."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
            securityMessage: "Vous ne pouvez supprimer que vos propres avis."
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact', 'movie' => 'exact'])]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['review:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read', 'review:write'])]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['review:read', 'review:write'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['review:read'])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'review:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'review:write'])]
    private ?Movie $movie = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }
}
