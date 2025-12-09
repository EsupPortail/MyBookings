<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CustomFieldRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['custom_field::read']], security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(normalizationContext: ['groups' => ['custom_field::read']], security: "is_granted('ROLE_ADMIN')"),
        new Post(securityPostDenormalize: "is_granted('editService', object.getCatalog().getService())"),
        new Put(normalizationContext: ['groups' => ['custom_field::read']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(denormalizationContext: ['groups' => ['custom_field::read']], security: "is_granted('editService', object.getCatalog().getService())"),
        new Delete(normalizationContext: ['groups' => ['custom_field::read']], security: "is_granted('editService', object.getCatalog().getService())"),
    ],
)]
class CustomField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['availableCatalogue::read','catalogue::read', 'custom_field::read', 'booking::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'custom_field::read','booking::read', 'booking::export'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'custom_field::read', 'booking::read', 'booking::export'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'customFields')]
    #[Groups(['custom_field::read'])]
    private ?CatalogueResource $Catalog = null;

    #[ORM\Column]
    #[Groups(['custom_field::read', 'catalogue::read', 'availableCatalogue::read', 'booking::read'])]
    private ?bool $isAttribute = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['availableCatalogue::read','catalogue::read', 'custom_field::read','booking::read'])]
    private ?string $label = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCatalog(): ?CatalogueResource
    {
        return $this->Catalog;
    }

    public function setCatalog(?CatalogueResource $Catalog): static
    {
        $this->Catalog = $Catalog;

        return $this;
    }

    public function isIsAttribute(): ?bool
    {
        return $this->isAttribute;
    }

    public function setIsAttribute(bool $isAttribute): static
    {
        $this->isAttribute = $isAttribute;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    #[Groups(['catalogueTitleResources::read'])]
    public function getLabelGeneral() {
        if($this->getCatalog() === null) {
            return $this->getLabel();
        }
        return null;
    }
}
