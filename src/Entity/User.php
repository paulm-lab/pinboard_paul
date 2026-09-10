<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 500)]
    private ?string $imageName = null;

    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Pin>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Pin::class)]
    private Collection $pins;

    public function __construct()
    {
        $this->pins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * L'email est utilisé comme identifiant de connexion.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Rôle par défaut de l'utilisateur.
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * Retourne le mot de passe haché.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Le mot de passe reçu ici doit déjà être haché.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, Pin>
     */
    public function getPins(): Collection
    {
        return $this->pins;
    }

    public function addPin(Pin $pin): static
    {
        if (!$this->pins->contains($pin)) {
            $this->pins->add($pin);
            $pin->setUser($this);
        }

        return $this;
    }

    public function removePin(Pin $pin): static
    {
        if ($this->pins->removeElement($pin)) {
            if ($pin->getUser() === $this) {
                $pin->setUser(null);
            }
        }

        return $this;
    }

    public function eraseCredentials(): void {}
}
