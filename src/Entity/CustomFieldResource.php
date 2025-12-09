<?php

namespace App\Entity;

use App\Repository\CustomFieldResourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CustomFieldResourceRepository::class)]
class CustomFieldResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['availableCatalogue::read','catalogue::read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'catalogueTitleResources::read', 'booking::export'])]
    private ?CustomField $CustomField = null;

    #[ORM\ManyToOne(inversedBy: 'customFieldResources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $Resource = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'catalogueTitleResources::read', 'booking::export'])]
    private ?string $Value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->CustomField;
    }

    public function setCustomField(?CustomField $CustomField): static
    {
        $this->CustomField = $CustomField;

        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->Resource;
    }

    public function setResource(?Resource $Resource): static
    {
        $this->Resource = $Resource;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->Value;
    }

    public function setValue(string $Value): static
    {
        $this->Value = $Value;

        return $this;
    }
}
