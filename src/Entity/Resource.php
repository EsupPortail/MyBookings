<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['availableCatalogue::read', 'booking::read', 'catalogue::read', 'ruleRessource::read','ressourcerie::read', 'booking-user::read',  'catalogueTitleResources::read', 'booking-anonymous::read', 'effect-bookings::read'])]
    private $id;

    #[ORM\Column(type: 'text')]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ruleRessource::read', 'ressourcerie::read', 'catalogueTitleResources::read', 'booking::export', 'effect-bookings::read'])]
    private $title;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read'])]
    private $inventoryNumber;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true)]
    private $service;

    #[ORM\ManyToOne(targetEntity: CatalogueResource::class, inversedBy: 'resource')]
    #[ORM\JoinColumn(nullable: false)]
    private $catalogueResource;

    #[ORM\ManyToMany(targetEntity: Booking::class, mappedBy: 'Resource')]
    private Collection $Bookings;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'booking::read', 'booking::export'])]
    private ?string $actuator_profile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read', 'effect-bookings::read'])]
    private ?string $image = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'ressourcerie::read'])]
    private ?string $AdditionalInformations = null;

    #[ORM\OneToMany(mappedBy: 'Resource', targetEntity: CustomFieldResource::class, orphanRemoval: true)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'catalogueTitleResources::read', 'booking::export'])]
    private Collection $customFieldResources;

    public function __construct()
    {
        $this->Bookings = new ArrayCollection();
        $this->customFieldResources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getInventoryNumber(): ?string
    {
        return $this->inventoryNumber;
    }

    public function setInventoryNumber(?string $inventoryNumber): self
    {
        $this->inventoryNumber = $inventoryNumber;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getCatalogueResource(): ?CatalogueResource
    {
        return $this->catalogueResource;
    }

    public function setCatalogueResource(?CatalogueResource $catalogueResource): self
    {
        $this->catalogueResource = $catalogueResource;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->Bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->Bookings->contains($booking)) {
            $this->Bookings->add($booking);
            $booking->addResource($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->Bookings->removeElement($booking)) {
            $booking->removeResource($this);
        }

        return $this;
    }

    public function getActuatorProfile(): ?string
    {
        return $this->actuator_profile;
    }

    public function setActuatorProfile(?string $actuator_profile): self
    {
        $this->actuator_profile = $actuator_profile;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getAdditionalInformations(): ?string
    {
        return $this->AdditionalInformations;
    }

    public function setAdditionalInformations(?string $AdditionalInformations): static
    {
        $this->AdditionalInformations = $AdditionalInformations;

        return $this;
    }

    /**
     * @return Collection<int, CustomFieldResource>
     */
    public function getCustomFieldResources(): Collection
    {
        return $this->customFieldResources;
    }

    public function addCustomFieldResource(CustomFieldResource $customFieldResource): static
    {
        if (!$this->customFieldResources->contains($customFieldResource)) {
            $this->customFieldResources->add($customFieldResource);
            $customFieldResource->setResource($this);
        }

        return $this;
    }

    public function removeCustomFieldResource(CustomFieldResource $customFieldResource): static
    {
        if ($this->customFieldResources->removeElement($customFieldResource)) {
            // set the owning side to null (unless already changed)
            if ($customFieldResource->getResource() === $this) {
                $customFieldResource->setResource(null);
            }
        }

        return $this;
    }
}
