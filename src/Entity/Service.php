<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\ApiPlatform\Provider\ServiceProvider;
use App\ApiPlatform\SearchServiceByType;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ApiResource(operations: [
    new Get(normalizationContext: ['groups' => ['service::read', 'service-data::read']], security: "is_granted('moderateService')"),
    new Get(uriTemplate: '/services/{id}/users', normalizationContext: ['groups' => ['ressourcerie::read']]),
    new GetCollection(uriTemplate: '/services-data', normalizationContext: ['groups' => ['service-data::read']], security: "is_granted('moderateService')", provider: ServiceProvider::class),
    new GetCollection(normalizationContext: ['groups' => ['service::read']], security: "is_granted('moderateService')", provider: ServiceProvider::class),
    new Patch(normalizationContext: ['groups' => ['service::read']], security: "is_granted('editService', object)"),
]
)]

#[ApiFilter(SearchServiceByType::class, properties: ['type'])]
#[ApiFilter(SearchFilter::class, properties: ['acls.service.id' => 'exact', 'id' => 'exact'])]

class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user::read', 'availableCatalogue::read', 'catalogue::read', 'service::read', 'availableWorkflow::read', 'booking::read', 'effect-bookings::read', 'ressourcerie::read', 'service-data::read'])]
    private $id;

    #[ORM\Column(type: 'text')]
    #[Groups(['availableCatalogue::read', 'catalogue::read', 'service::read', 'booking::read', 'user::read', 'ressourcerie::read', 'effect-bookings::read'])]
    private $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['service::read'])]
    private $type;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Resource::class)]
    private $resources;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: CatalogueResource::class)]
    private $catalogueResources;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Acl::class)]
    #[Groups(['service::read', 'ressourcerie::read'])]
    private Collection $acls;

    #[ORM\OneToMany(mappedBy: 'Service', targetEntity: Workflow::class)]
    private Collection $workflows;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(mappedBy: 'Service', targetEntity: Group::class)]
    private Collection $groups;

    /**
     * @var PeriodBracket|null
     */
    #[ORM\ManyToOne(targetEntity: PeriodBracket::class, inversedBy: 'services')]
    private ?PeriodBracket $periodBracket = null;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
        $this->catalogueResources = new ArrayCollection();
        $this->acls = new ArrayCollection();
        $this->workflows = new ArrayCollection();
        $this->groups = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): self
    {
        if (!$this->resources->contains($resource)) {
            $this->resources[] = $resource;
            $resource->setService($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): self
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getService() === $this) {
                $resource->setService(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueResource>
     */
    public function getCatalogueResources(): Collection
    {
        return $this->catalogueResources;
    }

    public function addCatalogueResource(CatalogueResource $catalogueResource): self
    {
        if (!$this->catalogueResources->contains($catalogueResource)) {
            $this->catalogueResources[] = $catalogueResource;
            $catalogueResource->setService($this);
        }

        return $this;
    }

    public function removeCatalogueResource(CatalogueResource $catalogueResource): self
    {
        if ($this->catalogueResources->removeElement($catalogueResource)) {
            // set the owning side to null (unless already changed)
            if ($catalogueResource->getService() === $this) {
                $catalogueResource->setService(null);
            }
        }

        return $this;
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
            $acl->setService($this);
        }

        return $this;
    }

    public function removeAcl(Acl $acl): self
    {
        if ($this->acls->removeElement($acl)) {
            // set the owning side to null (unless already changed)
            if ($acl->getService() === $this) {
                $acl->setService(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Workflow>
     */
    public function getWorkflows(): Collection
    {
        return $this->workflows;
    }

    public function addWorkflow(Workflow $workflow): self
    {
        if (!$this->workflows->contains($workflow)) {
            $this->workflows->add($workflow);
            $workflow->setService($this);
        }

        return $this;
    }

    public function removeWorkflow(Workflow $workflow): self
    {
        if ($this->workflows->removeElement($workflow)) {
            // set the owning side to null (unless already changed)
            if ($workflow->getService() === $this) {
                $workflow->setService(null);
            }
        }

        return $this;
    }

    #[Groups(['service-data::read'])]
    public function getPendingBookingsFromService()
    {
        $pending = 0;
        foreach ($this->catalogueResources->getValues() as $catalog) {
            foreach ($catalog->getBookings() as $booking) {
                if ($booking->getStatus() === 'pending') {
                    $pending++;
                }
            }
        }
        return $pending;
    }

    #[Groups(['service-data::read'])]
    public function getRequestedBookingEffects()
    {
        $requested = 0;
        foreach ($this->catalogueResources->getValues() as $catalog) {
            foreach ($catalog->getBookings() as $booking) {
                if ($booking->getTitle() == $this->getId() && $booking->getStatus() === 'requested') {
                    $requested++;
                }
            }
        }
        return $requested;
    }

    #[Groups(['service-data::read'])]
    public function getPendingDeposits()
    {
        $pending = 0;
        foreach ($this->catalogueResources->getValues() as $catalog) {
            if ($catalog->getStatus() === 'pending') {
                $pending++;
            }
        }
        return $pending;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->setService($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->removeElement($group)) {
            // set the owning side to null (unless already changed)
            if ($group->getService() === $this) {
                $group->setService(null);
            }
        }

        return $this;
    }

    /**
     * @return ?PeriodBracket
     */
    public function getPeriodBracket(): ?PeriodBracket
    {
        return $this->periodBracket;
    }

    public function setPeriodBracket(?PeriodBracket $periodBracket): static
    {
        $this->periodBracket = $periodBracket;

        return $this;
    }
}
