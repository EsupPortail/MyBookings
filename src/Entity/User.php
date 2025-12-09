<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use App\ApiPlatform\SearchServiceByUsernameFilter;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(operations: [
		new Get(normalizationContext: ['groups' => ['user::read']], security: "is_granted('ROLE_ADMIN')"),
		new GetCollection(normalizationContext: ['groups' => ['user::read']], security: "is_granted('moderateService')")
	]
)]

#[ApiFilter(SearchFilter::class, properties: ['acls.type' => 'exact', 'acls.service.id' => 'exact'])]
#[ApiFilter(SearchServiceByUsernameFilter::class, properties: ['username'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user::read', 'ressourcerie::read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
	#[Groups(['user::read', 'booking::read', 'service::read', 'booking-user::read', 'ressourcerie::read', 'booking::export'])]
    private $username;

    #[ORM\Column(length: 255)]
	#[Groups(['user::read', 'booking::read', 'ressourcerie::read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
	#[Groups(['user::read', 'booking::read', 'booking-user::read', 'ressourcerie::read', 'effect-bookings::read'])]
    private ?string $displayUserName = null;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Acl::class, cascade: ['remove'])]
    #[Groups(['user::read'])]
    private Collection $acls;

    #[ORM\ManyToMany(targetEntity: Booking::class, mappedBy: 'user')]
	#[Groups(['user::read'])]
    private Collection $bookings;

    private $casAttributes;

    private $encryptedUsername;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct()
    {
        $this->acls = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() : void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Acl>
     */
    public function getAcls(): Collection
    {
        return $this->acls;
    }

    public function addAcl(Acl $acl): self
    {
        if (!$this->acls->contains($acl)) {
            $this->acls->add($acl);
            $acl->setUser($this);
        }

        return $this;
    }

    public function removeAcl(Acl $acl): self
    {
        if ($this->acls->removeElement($acl)) {
            // set the owning side to null (unless already changed)
            if ($acl->getUser() === $this) {
                $acl->setUser(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getDisplayUserName(): ?string
    {
        return $this->displayUserName;
    }

    public function setDisplayUserName(string $displayUserName): self
    {
        $this->displayUserName = $displayUserName;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->addUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            $booking->removeUser($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCasAttributes(): mixed
    {
        return $this->casAttributes;
    }

    /**
     * @param mixed $casAttributes
     */
    public function setCasAttributes($casAttributes): void
    {
        $this->casAttributes = $casAttributes;
    }

    /**
     * @return string
     */
    public function getEncryptedUsername(): string
    {
        return $this->encryptedUsername;
    }

    /**
     * @param string $encryptedUsername
     */
    public function setEncryptedUsername($encryptedUsername): void
    {
        $this->encryptedUsername = $encryptedUsername;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }


}
