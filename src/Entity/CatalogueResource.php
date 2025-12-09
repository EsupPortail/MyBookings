<?php

namespace App\Entity;


use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use App\ApiPlatform\CustomLocalizationFilter;
use App\ApiPlatform\CustomRolesFilter;
use App\ApiPlatform\Provider\CatalogCollectionDataProvider;
use App\ApiPlatform\Provider\RessourcerieEffectProvider;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\ApiPlatform\SearchAvailableEffects;
use Doctrine\Common\Collections\Collection;
use App\Repository\CatalogueResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use App\ApiPlatform\SearchCatalogByKeywordsFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: CatalogueResourceRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate:'/catalogueTitleResources/{id}', normalizationContext: ['groups' => ['catalogueTitleResources::read']]),
        new Get(uriTemplate: '/catalogue_resources/{id}/provisions', normalizationContext: ['groups' => ['availableProvisions::read']], provider: CatalogCollectionDataProvider::class),
        new Get(normalizationContext: ['groups' => ['catalogue::read']], provider: CatalogCollectionDataProvider::class),
        new GetCollection(normalizationContext: ['groups' => ['availableCatalogue::read', "enable_max_depth"=>true]], provider: CatalogCollectionDataProvider::class),
        new GetCollection(uriTemplate:'/effects', normalizationContext: ['groups' => ['ressourcerie::read']], provider: RessourcerieEffectProvider::class),
        new Patch(normalizationContext: ['groups' => ['catalogue::read']], security: "is_granted('editCatalog', object)"),
        ]
    )]

#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact', 'subType' => 'exact', 'resource.id' => 'exact', 'service.id' => 'exact', 'title' => 'partial', 'description' => 'partial', 'type.type' => 'exact', 'status' => 'exact', 'service.type' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['Provisions.dateStart', 'Provisions.dateEnd'])]
#[ApiFilter(SearchCatalogByKeywordsFilter::class)]
#[ApiFilter(SearchAvailableEffects::class)]
#[ApiFilter(CustomRolesFilter::class, properties: ['Provisions.groups'])]
#[ApiFilter(CustomLocalizationFilter::class, properties: ['localization'])]
class CatalogueResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'custom_field::read', 'ressourcerie::read', 'booking-user::read', 'effect-bookings::read'])]
    private $id;

    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read', 'catalogueTitleResources::read', 'effect-bookings::read'])]
    #[ORM\Column(type: 'text')]
    private $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'ressourcerie::read'])]
    private $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read', 'catalogueTitleResources::read', 'effect-bookings::read'])]
    private $image;

    #[ORM\OneToMany(mappedBy: 'catalogueResource', targetEntity: Booking::class)]
    #[MaxDepth(1)]
    private $bookings;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'catalogueResources')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read', 'effect-bookings::read'])]
    private $service;

    #[ORM\OneToMany(mappedBy: 'catalogueResource', targetEntity: Resource::class, fetch:"EAGER")]
    #[ORM\OrderBy(['title' => 'ASC'])]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'ressourcerie::read', 'catalogueTitleResources::read'])]
    #[MaxDepth(1)]
    private $resource;

    #[ORM\ManyToOne(inversedBy: 'catalogueResourcesType')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read'])]
    #[MaxDepth(1)]
    private ?Category $type = null;

    #[ORM\ManyToOne(inversedBy: 'catalogueResourcesSubType')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['availableCatalogue::read','catalogue::read', 'booking::read'])]
    #[MaxDepth(1)]
    private ?Category $subType = null;

    #[ORM\OneToMany(mappedBy: 'catalogueResource', targetEntity: Provision::class)]
    #[Groups(['availableCatalogue::read','availableProvisions::read', 'catalogue::read'])]
    #[MaxDepth(1)]
    private Collection $Provisions;

    #[ORM\ManyToOne(inversedBy: 'catalogue')]

    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['catalogue::read', 'booking::read', 'booking::export'])]
    #[MaxDepth(1)]
    private ?Actuator $actuator = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['catalogue::read', 'availableCatalogue::read'])]
    private ?string $view = null;

    #[ORM\OneToMany(mappedBy: 'Catalog', targetEntity: CustomField::class, cascade: ['remove'])]
    #[Groups(['availableCatalogue::read', 'catalogue::read'])]
    #[MaxDepth(1)]
    private Collection $customFields;

    #[ORM\ManyToOne(inversedBy: 'Catalog')]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'booking::read'])]
    #[MaxDepth(1)]
    private ?Localization $localization = null;

    #[ORM\Column(nullable: true)]
    private ?bool $IndependentOptions = null;

    #[Groups(['ressourcerie::read','catalogue::read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function __construct()
    {
        $this->resource = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->Provisions = new ArrayCollection();
        $this->customFields = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

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
            $this->bookings[] = $booking;
            $booking->setCatalogueResource($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getCatalogueResource() === $this) {
                $booking->setCatalogueResource(null);
            }
        }

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

    /**
     * @return Collection<int, Resource>
     */
    public function getResource(): Collection
    {
        return $this->resource;
    }

    #[Groups(['availableCatalogue::read'])]
    /**
     * @return Collection<int, Resource>
     */
    public function getChilds(): Collection
    {
        return $this->resource;
    }

    #[Groups(['availableCatalogue::read'])]
    public function getExpanded() {
        return false;
    }

    public function addResource(Resource $resource): self
    {
        if (!$this->resource->contains($resource)) {
            $this->resource[] = $resource;
            $resource->setCatalogueResource($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): self
    {
        if ($this->resource->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getCatalogueResource() === $this) {
                $resource->setCatalogueResource(null);
            }
        }

        return $this;
    }

    public function getType(): ?Category
    {
        return $this->type;
    }

    public function setType(?Category $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSubType(): ?Category
    {
        return $this->subType;
    }

    public function setSubType(?Category $subType): self
    {
        $this->subType = $subType;

        return $this;
    }

    /**
     * @return Collection<int, Provision>
     */
    public function getProvisions(): Collection
    {
        return $this->Provisions;
    }

    public function addProvision(Provision $provision): self
    {
        if (!$this->Provisions->contains($provision)) {
            $this->Provisions->add($provision);
            $provision->setCatalogueResource($this);
        }

        return $this;
    }

    public function removeProvision(Provision $provision): self
    {
        if ($this->Provisions->removeElement($provision)) {
            // set the owning side to null (unless already changed)
            if ($provision->getCatalogueResource() === $this) {
                $provision->setCatalogueResource(null);
            }
        }

        return $this;
    }

    public function getActuator(): ?Actuator
    {
        return $this->actuator;
    }

    public function setActuator(?Actuator $actuator): self
    {
        $this->actuator = $actuator;

        return $this;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return Collection<int, CustomField>
     */
    public function getCustomFields(): Collection
    {
        return $this->customFields;
    }

    public function addCustomField(CustomField $customField): static
    {
        if (!$this->customFields->contains($customField)) {
            $this->customFields->add($customField);
            $customField->setCatalog($this);
        }

        return $this;
    }

    public function removeCustomField(CustomField $customField): static
    {
        if ($this->customFields->removeElement($customField)) {
            // set the owning side to null (unless already changed)
            if ($customField->getCatalog() === $this) {
                $customField->setCatalog(null);
            }
        }

        return $this;
    }

    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    public function setLocalization(?Localization $localization): static
    {
        $this->localization = $localization;

        return $this;
    }

    public function isIndependentOptions(): ?bool
    {
        return $this->IndependentOptions;
    }

    public function setIndependentOptions(?bool $IndependentOptions): static
    {
        $this->IndependentOptions = $IndependentOptions;

        return $this;
    }

    #[Groups(['ressourcerie::read'])]
    public function getRemainingEffects()
    {
        $remainingEffects = [];
        foreach ($this->resource->getValues() as $resource) {
            if ($resource->getBookings()->count() == 0) {
                $remainingEffects[] = $resource;
            }
        }
        return $remainingEffects;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
