<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\PeriodBracketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PeriodBracketRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['periodBracket::read']],
        ),
        new GetCollection(
            uriTemplate: '/period_brackets/service/{serviceId}',
            uriVariables: [
                'serviceId' => new Link(
                    toProperty: 'service',
                    fromClass: Service::class
                ),
            ],
            normalizationContext: ['groups' => ['periodBracket::read']]
        ),
        new Post(),
        new Delete()
    ],
    security: "is_granted('moderateService')"
)]
class PeriodBracket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['periodBracket::read', 'catalogue::read', 'availableProvisions::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['periodBracket::read', 'catalogue::read', 'availableProvisions::read'])]
    private ?string $title = null;

    /**
     * @var Collection<int, Provision>
     */
    #[ORM\OneToMany(targetEntity: Provision::class, mappedBy: 'periodBracket')]
    #[Groups(['periodBracket::read'])]
    private Collection $provisions;

    /**
     * @var Collection<int, Period>
     */
    #[ORM\OneToMany(targetEntity: Period::class, mappedBy: 'periodBracket', orphanRemoval: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private Collection $periods;

    #[ORM\ManyToOne(inversedBy: 'periodBrackets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    /**
     * @var Collection<int, Service>
     */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'periodBracket')]
    private Collection $services;

    public function __construct()
    {
        $this->provisions = new ArrayCollection();
        $this->periods = new ArrayCollection();
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Provision>
     */
    public function getProvisions(): Collection
    {
        return $this->provisions;
    }

    public function addProvision(Provision $provision): static
    {
        if (!$this->provisions->contains($provision)) {
            $this->provisions->add($provision);
        }

        return $this;
    }

    public function removeProvision(Provision $provision): static
    {
        $this->provisions->removeElement($provision);

        return $this;
    }

    /**
     * @return Collection<int, Period>
     */
    public function getPeriods(): Collection
    {
        return $this->periods;
    }

    public function addPeriod(Period $period): static
    {
        if (!$this->periods->contains($period)) {
            $this->periods->add($period);
            $period->setPeriodBracket($this);
        }

        return $this;
    }

    public function removePeriod(Period $period): static
    {
        if ($this->periods->removeElement($period)) {
            // set the owning side to null (unless already changed)
            if ($period->getPeriodBracket() === $this) {
                $period->setPeriodBracket(null);
            }
        }

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setPeriodBracket($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getPeriodBracket() === $this) {
                $service->setPeriodBracket(null);
            }
        }

        return $this;
    }
}
