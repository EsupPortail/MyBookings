<?php

namespace App\Entity;

use App\Repository\StatisticsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticsRepository::class)]
class Statistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $participants = null;

    #[ORM\Column]
    private ?\DateTime $date_start = null;

    #[ORM\Column]
    private ?\DateTime $date_end = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $catalog = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resources = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $service = null;

    #[ORM\Column(length: 255)]
    private ?string $localization = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $workflow = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $custom_field = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $booking = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getParticipants(): ?string
    {
        return $this->participants;
    }

    public function setParticipants(?string $participants): static
    {
        $this->participants = $participants;

        return $this;
    }

    public function getDateStart(): ?\DateTime
    {
        return $this->date_start;
    }

    public function setDateStart(\DateTime $date_start): static
    {
        $this->date_start = $date_start;

        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->date_end;
    }

    public function setDateEnd(\DateTime $date_end): static
    {
        $this->date_end = $date_end;

        return $this;
    }

    public function getCatalog(): ?string
    {
        return $this->catalog;
    }

    public function setCatalog(?string $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }

    public function getResources(): ?string
    {
        return $this->resources;
    }

    public function setResources(?string $resources): static
    {
        $this->resources = $resources;
        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    public function setLocalization(string $localization): static
    {
        $this->localization = $localization;

        return $this;
    }

    public function getWorkflow(): ?string
    {
        return $this->workflow;
    }

    public function setWorkflow(?string $workflow): static
    {
        $this->workflow = $workflow;
        return $this;
    }

    public function getCustomField(): ?string
    {
        return $this->custom_field;
    }

    public function setCustomField(?string $custom_field): static
    {
        $this->custom_field = $custom_field;

        return $this;
    }

    public function getBooking(): ?string
    {
        return $this->booking;
    }

    public function setBooking(?string $booking): static
    {
        $this->booking = $booking;
        return $this;
    }
}
