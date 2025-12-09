<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\SearchWithNullFilter;
use App\Repository\WorkflowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['availableWorkflow::read']], security: "is_granted('moderateService')"),
        new GetCollection(normalizationContext: ['groups' => ['availableWorkflow::read']], security: "is_granted('moderateService')"),
        new Post(securityPostDenormalize: "is_granted('editService', object.getService())"),
        new Patch(security: "is_granted('editService', object.getService())"),
        new Delete(security: "is_granted('editService', object.getService())")
    ]
)]
#[ApiFilter(SearchWithNullFilter::class, properties: ['Service.id'])]
class Workflow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['booking::read','catalogue::read', 'availableWorkflow::read','availableProvisions::read'])]
    private $id;

    #[ORM\Column(type: 'text')]
    #[Groups(['booking::read','catalogue::read','availableWorkflow::read','availableProvisions::read'])]
    private ?string $title;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking::read','availableWorkflow::read', 'availableCatalogue::read'])]
    private ?bool $autoValidation = null;

    #[ORM\Column]
    #[Groups(['booking::read','availableWorkflow::read'])]
    private ?bool $auto_start = null;

    #[ORM\Column]
    #[Groups(['booking::read', 'availableWorkflow::read'])]
    private ?bool $auto_end = null;

    #[ORM\OneToMany(mappedBy: 'Workflow', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\ManyToOne(inversedBy: 'workflows')]
    #[Groups(['availableWorkflow::read', 'availableProvisions::read'])]
    private ?Service $Service = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['booking::read', 'availableWorkflow::read','catalogue::read', 'availableProvisions::read', 'availableCatalogue::read'])]
    private ?array $configuration = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
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

    public function isAutoValidation(): ?bool
    {
        return $this->autoValidation;
    }

    public function setAutoValidation(?bool $autoValidation): self
    {
        $this->autoValidation = $autoValidation;

        return $this;
    }

    public function isAutoStart(): ?bool
    {
        return $this->auto_start;
    }

    public function setAutoStart(bool $auto_start): self
    {
        $this->auto_start = $auto_start;

        return $this;
    }

    public function isAutoEnd(): ?bool
    {
        return $this->auto_end;
    }

    public function setAutoEnd(bool $auto_end): self
    {
        $this->auto_end = $auto_end;

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
            $booking->setWorkflow($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getWorkflow() === $this) {
                $booking->setWorkflow(null);
            }
        }

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->Service;
    }

    public function setService(?Service $Service): self
    {
        $this->Service = $Service;

        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
