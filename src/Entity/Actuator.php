<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ActuatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ActuatorRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
)]
class Actuator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['catalogue::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['catalogue::read', 'booking::export'])]
    private ?string $title = null;

    #[ORM\OneToMany(mappedBy: 'actuator', targetEntity: CatalogueResource::class)]
    private Collection $catalogue;

    #[ORM\Column(length: 255)]
    #[Groups(['catalogue::read', 'booking::export'])]
    private ?string $type = null;

    public function __construct()
    {
        $this->catalogue = new ArrayCollection();
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

    /**
     * @return Collection<int, CatalogueResource>
     */
    public function getCatalogue(): Collection
    {
        return $this->catalogue;
    }

    public function addCatalogue(CatalogueResource $catalogue): self
    {
        if (!$this->catalogue->contains($catalogue)) {
            $this->catalogue->add($catalogue);
            $catalogue->setActuator($this);
        }

        return $this;
    }

    public function removeCatalogue(CatalogueResource $catalogue): self
    {
        if ($this->catalogue->removeElement($catalogue)) {
            // set the owning side to null (unless already changed)
            if ($catalogue->getActuator() === $this) {
                $catalogue->setActuator(null);
            }
        }

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
}
