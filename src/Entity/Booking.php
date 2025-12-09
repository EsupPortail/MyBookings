<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Get;
use App\ApiPlatform\CustomDateFilter;
use App\ApiPlatform\Provider\BookingsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\BookingRepository;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\ApiPlatform\TargetIdFilter;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(
        operations: [
        new Get(normalizationContext: ['groups' => ['booking::read']]),
        new GetCollection(
            paginationEnabled: true,
            paginationClientItemsPerPage: true, // Permet au client de choisir itemsPerPage
            paginationItemsPerPage: 20,         // Valeur par défaut
            paginationMaximumItemsPerPage: 100, // (optionnel) Limite max
            normalizationContext: ['groups' => ['booking::read']]
        ),
        new GetCollection(uriTemplate: '/myBookings', normalizationContext: ['groups' => ['booking::read']], provider: BookingsProvider::class),
        new GetCollection(uriTemplate: '/countBookings', normalizationContext: ['groups' => ['booking::read']], provider: BookingsProvider::class),
        new GetCollection(uriTemplate:'/getBookings', normalizationContext: ['groups' => ['booking-user::read']]),
        new GetCollection(uriTemplate:'/effectBookings', normalizationContext: ['groups' => ['effect-bookings::read']]),
        new GetCollection(uriTemplate:'/getAnonymousBookings', normalizationContext: ['groups' => ['booking-anonymous::read']], provider: BookingsProvider::class),
    ],
    order: ['dateStart' => 'DESC'],
)]
#[ApiFilter(CustomDateFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['catalogueResource.id' => 'exact', 'user.username' => 'exact', 'user.displayUserName' => 'partial', 'catalogueResource.service.id' => 'exact', 'Resource.id' => 'exact','status' => 'exact', 'title' => 'partial', 'targetId' => 'exact', 'catalogueResource.service.type' => 'exact'])]
#[ApiFilter(dateFilter::class, properties: ['dateStart', 'dateEnd'])]
#[ApiFilter(orderFilter::class, properties: ['dateStart'])]
#[ApiFilter(TargetIdFilter::class)]

class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['booking::read', 'booking-user::read', 'effect-bookings::read'])]
    private $id;

    #[ORM\Column(type: 'datetime')]
	#[Groups(['booking::read', 'user::read', 'availableCatalogue::read', 'booking-user::read', 'booking-anonymous::read', 'effect-bookings::read', 'booking::export'])]
    private $dateStart;

    #[ORM\Column(type: 'datetime')]
	#[Groups(['booking::read', 'user::read', 'availableCatalogue::read', 'booking-user::read', 'booking-anonymous::read', 'effect-bookings::read', 'booking::export'])]
    private $dateEnd;

    #[ORM\Column(type: 'integer')]
	#[Groups(['booking::read', 'availableCatalogue::read', 'booking-user::read',  'booking::export'])]
    private $number;

    #[ORM\Column(type: 'string', length: 255)]
	#[Groups(['booking::read', 'user::read', 'availableCatalogue::read', 'booking-user::read', 'effect-bookings::read', 'booking::export'])]
    private $status;

    #[ORM\ManyToOne(targetEntity: CatalogueResource::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking::read', 'booking-user::read','effect-bookings::read', 'booking::export' ])]
    private $catalogueResource;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'bookings')]
	#[Groups(['booking::read', 'booking-user::read', 'effect-bookings::read', 'booking::export'])]
    private Collection $user;

    #[Groups(['booking::read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userComment = null;

    #[Groups(['booking::read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $confirmComment = null;

    #[ORM\ManyToMany(targetEntity: Resource::class, inversedBy: 'Bookings')]
    #[Groups(['booking::read', 'booking-user::read', 'booking-anonymous::read', 'effect-bookings::read', 'booking::export' ])]
    private Collection $Resource;

    #[ORM\OneToMany(mappedBy: 'booking', targetEntity: WorkflowLog::class, orphanRemoval: true)]
    private Collection $workflowLogs;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['booking::read'])]
    private ?Workflow $Workflow = null;

    #[ORM\ManyToMany(targetEntity: CustomField::class)]
    #[Groups(['booking::read'])]
    private Collection $Options;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['booking::read', 'effect-bookings::read'])]
    private ?string $title = null;


    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->Resource = new ArrayCollection();
        $this->workflowLogs = new ArrayCollection();
        $this->Options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->user->removeElement($user);

        return $this;
    }

    public function getUserComment(): ?string
    {
        return $this->userComment;
    }

    public function setUserComment(?string $userComment): self
    {
        $this->userComment = $userComment;

        return $this;
    }

    public function getConfirmComment(): ?string
    {
        return $this->confirmComment;
    }

    public function setConfirmComment(?string $confirmComment): self
    {
        $this->confirmComment = $confirmComment;

        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResource(): Collection
    {
        return $this->Resource;
    }

    public function addResource(Resource $resource): self
    {
        if (!$this->Resource->contains($resource)) {
            $this->Resource->add($resource);
        }

        return $this;
    }

    public function removeResource(Resource $resource): self
    {
        $this->Resource->removeElement($resource);

        return $this;
    }

    /**
     * @return Collection<int, WorkflowLog>
     */
    public function getWorkflowLogs(): Collection
    {
        return $this->workflowLogs;
    }

    public function addWorkflowLog(WorkflowLog $workflowLog): self
    {
        if (!$this->workflowLogs->contains($workflowLog)) {
            $this->workflowLogs->add($workflowLog);
            $workflowLog->setBooking($this);
        }

        return $this;
    }

    public function removeWorkflowLog(WorkflowLog $workflowLog): self
    {
        if ($this->workflowLogs->removeElement($workflowLog)) {
            // set the owning side to null (unless already changed)
            if ($workflowLog->getBooking() === $this) {
                $workflowLog->setBooking(null);
            }
        }

        return $this;
    }

    public function getWorkflow(): ?Workflow
    {
        return $this->Workflow;
    }

    public function setWorkflow(?Workflow $Workflow): self
    {
        $this->Workflow = $Workflow;

        return $this;
    }

    /**
     * @return Collection<int, CustomField>
     */
    public function getOptions(): Collection
    {
        return $this->Options;
    }

    public function addOption(CustomField $option): static
    {
        if (!$this->Options->contains($option)) {
            $this->Options->add($option);
        }

        return $this;
    }

    public function removeOption(CustomField $option): static
    {
        $this->Options->removeElement($option);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

}
