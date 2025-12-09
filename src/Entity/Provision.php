<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProvisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProvisionRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['provision::read']],
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(security: "is_granted('moderateService')"),
        new Post(),
        new Patch()
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['workflow.id' => 'exact'])]
class Provision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read', 'provision::read'])]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $dateStart;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $dateEnd;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $minBookingTime;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $maxBookingTime;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $BookingInterval;

    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[Groups(['catalogue::read', 'availableProvisions::read', 'provision::read', 'availableCatalogue::read'])]
    private $workflow;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private $maxBookingDuration;

    #[ORM\Column(nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read', 'catalogue::read'])]
    private ?array $days = [];

    #[ORM\ManyToOne(inversedBy: 'Provisions')]
    private ?CatalogueResource $catalogueResource = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read','catalogue::read'])]
    private ?int $maxBookingByDay = null;

    #[ORM\Column]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read','catalogue::read'])]
    private ?int $maxBookingByWeek = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'provisions')]
    private Collection $groups;

    #[ORM\Column(nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read','catalogue::read'])]
    private ?bool $allowMultipleDay = null;

    #[ORM\ManyToOne(targetEntity: PeriodBracket::class, inversedBy: 'provisions')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['availableCatalogue::read', 'availableProvisions::read','catalogue::read'])]
    private ?PeriodBracket $periodBracket = null;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getMinBookingTime(): ?\DateTimeInterface
    {
        return $this->minBookingTime;
    }

    public function setMinBookingTime(?\DateTimeInterface $minBookingTime): self
    {
        $this->minBookingTime = $minBookingTime;

        return $this;
    }

    public function getMaxBookingTime(): ?\DateTimeInterface
    {
        return $this->maxBookingTime;
    }

    public function setMaxBookingTime(?\DateTimeInterface $maxBookingTime): self
    {
        $this->maxBookingTime = $maxBookingTime;

        return $this;
    }

    public function getBookingInterval()
    {
        return $this->BookingInterval;
    }

    public function setBookingInterval($BookingInterval): self
    {
        $this->BookingInterval = $BookingInterval;

        return $this;
    }

    public function getWorkflow(): ?Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(?Workflow $workflow): self
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getMaxBookingDuration()
    {
        return $this->maxBookingDuration;
    }

    public function setMaxBookingDuration($maxBookingDuration): self
    {
        $this->maxBookingDuration = $maxBookingDuration;

        return $this;
    }

    public function getDays(): ?array
    {
        return $this->days;
    }

    public function setDays(?array $days): self
    {
        $this->days = $days ?? [];

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

    public function getMaxBookingByDay(): ?int
    {
        return $this->maxBookingByDay;
    }

    public function setMaxBookingByDay(?int $maxBookingByDay): self
    {
        $this->maxBookingByDay = $maxBookingByDay;

        return $this;
    }

    #[Groups(['availableCatalogue::read', 'availableProvisions::read','catalogue::read'])]
    public function getAllGroups():array {
        $groups = [];
        foreach ($this->groups->getValues() as $group) {
            $groups[] = ['id' => $group->getId(), 'title' => $group->getTitle()];
        }
        return $groups;
    }

    public function removeAllGroups() {
        foreach ($this->getGroups()->getValues() as $group) {
            $this->removeGroup($group);
        }
        return $this;
    }

    public function getMaxBookingByWeek(): ?int
    {
        return $this->maxBookingByWeek;
    }

    public function setMaxBookingByWeek(int $maxBookingByWeek): static
    {
        $this->maxBookingByWeek = $maxBookingByWeek;

        return $this;
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
            $group->addProvision($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): static
    {
        if ($this->groups->removeElement($group)) {
            $group->removeProvision($this);
        }

        return $this;
    }

    public function isAllowMultipleDay(): ?bool
    {
        return $this->allowMultipleDay;
    }

    public function setAllowMultipleDay(?bool $allowMultipleDay): static
    {
        $this->allowMultipleDay = $allowMultipleDay;

        return $this;
    }

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
