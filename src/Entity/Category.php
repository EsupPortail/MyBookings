<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['category::read']]),
        new GetCollection(normalizationContext: ['groups' => ['category::read']]),
        new Post(normalizationContext: ['groups' => ['category::read']], security: "is_granted('ROLE_ADMIN')"),
        new Put(normalizationContext: ['groups' => ['category::read']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(normalizationContext: ['groups' => ['category::read']], security: "is_granted('ROLE_ADMIN')"),
        new Delete(normalizationContext: ['groups' => ['category::read']], security: "is_granted('ROLE_ADMIN')"),
    ],
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category::read', 'catalogue::read', 'availableCatalogue::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category::read', 'availableCatalogue::read', 'catalogue::read', 'booking::read', 'ressourcerie::read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category::read'])]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['category::read'])]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[Groups(['category::read'])]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private Collection $enfants;


    #[ORM\OneToMany(mappedBy: 'type', targetEntity: CatalogueResource::class)]
    private Collection $catalogueResourcesType;

    #[ORM\OneToMany(mappedBy: 'subType', targetEntity: CatalogueResource::class)]
    private Collection $catalogueResourcesSubType;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['category::read'])]
    private ?string $view = null;

    #[ORM\ManyToMany(targetEntity: CustomField::class)]
    private Collection $CustomField;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->catalogueResourcesType = new ArrayCollection();
        $this->catalogueResourcesSubType = new ArrayCollection();
        $this->CustomField = new ArrayCollection();
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(self $enfant): self
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): self
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueResource>
     */
    public function getCatalogueResourcesType(): Collection
    {
        return $this->catalogueResourcesType;
    }

    public function addCatalogueResourcesType(CatalogueResource $catalogueResourcesType): self
    {
        if (!$this->catalogueResourcesType->contains($catalogueResourcesType)) {
            $this->catalogueResourcesType->add($catalogueResourcesType);
            $catalogueResourcesType->setType($this);
        }

        return $this;
    }

    public function removeCatalogueResourcesType(CatalogueResource $catalogueResourcesType): self
    {
        if ($this->catalogueResourcesType->removeElement($catalogueResourcesType)) {
            // set the owning side to null (unless already changed)
            if ($catalogueResourcesType->getType() === $this) {
                $catalogueResourcesType->setType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueResource>
     */
    public function getCatalogueResourcesSubType(): Collection
    {
        return $this->catalogueResourcesSubType;
    }

    public function addCatalogueResourcesSubType(CatalogueResource $catalogueResourcesSubType): self
    {
        if (!$this->catalogueResourcesSubType->contains($catalogueResourcesSubType)) {
            $this->catalogueResourcesSubType->add($catalogueResourcesSubType);
            $catalogueResourcesSubType->setSubType($this);
        }

        return $this;
    }

    public function removeCatalogueResourcesSubType(CatalogueResource $catalogueResourcesSubType): self
    {
        if ($this->catalogueResourcesSubType->removeElement($catalogueResourcesSubType)) {
            // set the owning side to null (unless already changed)
            if ($catalogueResourcesSubType->getSubType() === $this) {
                $catalogueResourcesSubType->setSubType(null);
            }
        }

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
    public function getCustomField(): Collection
    {
        return $this->CustomField;
    }

    public function addCustomField(CustomField $customField): static
    {
        if (!$this->CustomField->contains($customField)) {
            $this->CustomField->add($customField);
        }

        return $this;
    }

    public function removeCustomField(CustomField $customField): static
    {
        $this->CustomField->removeElement($customField);

        return $this;
    }
}
