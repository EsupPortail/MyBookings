<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\LocalizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LocalizationRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['localization::read']],security: "is_granted('moderateService')"),
        new GetCollection(normalizationContext: ['groups' => ['localization::read']], security: "is_granted('ROLE_USER')"),
        new Post(normalizationContext: ['groups' => ['localization::read']], security: "is_granted('ROLE_ADMIN')"),
        new Put(normalizationContext: ['groups' => ['localization::read']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(normalizationContext: ['groups' => ['localization::read']], security: "is_granted('ROLE_ADMIN')"),
        new Delete(normalizationContext: ['groups' => ['localization::read']], security: "is_granted('ROLE_ADMIN')"),
    ],
)]
#[ApiFilter(ExistsFilter::class, properties: ['parent'])]
class Localization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['localization::read', 'availableCatalogue::read', 'catalogue::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['localization::read', 'availableCatalogue::read', 'catalogue::read', 'booking::read'])]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['localization::read', 'availableCatalogue::read', 'catalogue::read'])]
    private ?array $data = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['localization::read', 'availableCatalogue::read', 'catalogue::read'])]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[Groups(['localization::read', 'availableCatalogue::read', 'catalogue::read'])]
    private Collection $childs;

    /**
     * @var Collection<int, CatalogueResource>
     */
    #[ORM\OneToMany(mappedBy: 'localization', targetEntity: CatalogueResource::class)]
    private Collection $Catalog;

    public function __construct()
    {
        $this->childs = new ArrayCollection();
        $this->Catalog = new ArrayCollection();
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

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChilds(): Collection
    {
        return $this->childs;
    }

    public function addChild(self $child): static
    {
        if (!$this->childs->contains($child)) {
            $this->childs->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->childs->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueResource>
     */
    public function getCatalog(): Collection
    {
        return $this->Catalog;
    }

    public function addCatalog(CatalogueResource $catalog): static
    {
        if (!$this->Catalog->contains($catalog)) {
            $this->Catalog->add($catalog);
            $catalog->setLocalization($this);
        }

        return $this;
    }

    public function removeCatalog(CatalogueResource $catalog): static
    {
        if ($this->Catalog->removeElement($catalog)) {
            // set the owning side to null (unless already changed)
            if ($catalog->getLocalization() === $this) {
                $catalog->setLocalization(null);
            }
        }

        return $this;
    }
}
