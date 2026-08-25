<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use App\State\UserPasswordProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "user")]
#[ApiResource(
    // Contexte de sérialisation déclaré au niveau de la ressource : une
    // opération qui oublierait ses groupes n'exposera plus l'intégralité des
    // accesseurs publics de l'entité (getPassword, getApiKey,
    // getTwoFactorSecret…). C'est exactement ce qui rendait GET /api/users
    // lisible publiquement, empreintes bcrypt comprises.
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        // La liste des comptes est une donnée d'administration.
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Inscription : reste ouverte, la validation et le hachage du mot de
        // passe sont assurés par UserPasswordListener.
        new Post(
            security: "is_granted('PUBLIC_ACCESS')",
            processor: UserPasswordProcessor::class
        ),
        // `object == user` : un compte n'accède qu'à lui-même.
        // `is_granted('ROLE_USER')` seul laissait n'importe quel inscrit lire
        // et modifier la fiche de n'importe quel autre.
        new Get(
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or object == user",
            processor: UserPasswordProcessor::class
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object == user",
            processor: UserPasswordProcessor::class
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide.")]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastname = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\LessThan('today', message: "La date de naissance doit être dans le passé.")]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTimeInterface $dob = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['user:read', 'user:write'])]
    private ?MediaObject $photo = null;

    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    #[ORM\Column(nullable: true)]
    private ?int $limiter = null;

    #[ORM\Embedded(class: ApiKey::class, columnPrefix: 'api_key_')]
    private ApiKey $apiKey;

    #[ORM\OneToOne(targetEntity: UserTwoFactor::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?UserTwoFactor $twoFactorAuth = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Review::class, orphanRemoval: true)]
    private Collection $reviews;


    public function __construct()
    {
        $this->createAt = new DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->limiter = 100;
        $this->apiKey = new ApiKey();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    // Empreinte du mot de passe : jamais sérialisée, quel que soit le groupe.
    #[Ignore]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
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

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): static
    {
        $this->dob = $dob;
        return $this;
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

    public function getLimiter(): ?int
    {
        return $this->limiter;
    }

    public function setLimiter(int $limiter): static
    {
        $this->limiter = $limiter;
        return $this;
    }

    // Contient l'empreinte de la clé API : hors de toute réponse.
    #[Ignore]
    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function setApiKey(ApiKey $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getTwoFactorAuth(): ?UserTwoFactor
    {
        return $this->twoFactorAuth;
    }

    public function setTwoFactorAuth(?UserTwoFactor $twoFactorAuth): static
    {
        $this->twoFactorAuth = $twoFactorAuth;
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorAuth !== null && $this->twoFactorAuth->isEnabled() === true;
    }

    // Secret TOTP : sa fuite annulerait la double authentification.
    #[Ignore]
    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorAuth?->getSecret();
    }

    public function setTwoFactorEnabled(bool $enabled): static
    {
        if ($this->twoFactorAuth === null) {
            $this->twoFactorAuth = new UserTwoFactor();
        }
        $this->twoFactorAuth->setEnabled($enabled);
        return $this;
    }

    public function setTwoFactorSecret(?string $secret): static
    {
        if ($this->twoFactorAuth === null) {
            $this->twoFactorAuth = new UserTwoFactor();
        }
        $this->twoFactorAuth->setSecret($secret);
        return $this;
    }

    public function setTwoFactorBackupCodes(?array $codes): static
    {
        if ($this->twoFactorAuth === null) {
            $this->twoFactorAuth = new UserTwoFactor();
        }
        $this->twoFactorAuth->setBackupCodes($codes);
        return $this;
    }

    // Codes de secours : même sensibilité que le secret TOTP.
    #[Ignore]
    public function getTwoFactorBackupCodes(): ?array
    {
        return $this->twoFactorAuth?->getBackupCodes();
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
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }
}
