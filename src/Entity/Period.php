<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\PeriodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PeriodRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            security: "is_granted('moderateService')"
        ),
        new Delete(
            security: "is_granted('moderateService')"
        ),
        new Patch(
            security: "is_granted('moderateService')",
        ),
    ]
)]
class Period
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?array $day = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?\DateTime $dateStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?\DateTime $dateEnd = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?\DateTime $timeStart = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['periodBracket::read', 'availableProvisions::read'])]
    private ?\DateTime $timeEnd = null;

    #[ORM\ManyToOne(inversedBy: 'periods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeriodBracket $periodBracket = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDay(): ?array
    {
        return $this->day;
    }

    public function setDay(?array $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function getDateStart(): ?\DateTime
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTime $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        $dateEnd = $this->dateEnd;
        if( $dateEnd instanceof \DateTime)
            $dateEnd->setTime(23, 59, 59);
        return $dateEnd;
    }

    public function setDateEnd(?\DateTime $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getTimeStart(): ?\DateTime
    {
        return $this->timeStart;
    }

    public function setTimeStart(?\DateTime $timeStart): static
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getTimeEnd(): ?\DateTime
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(?\DateTime $timeEnd): static
    {
        $this->timeEnd = $timeEnd;

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
