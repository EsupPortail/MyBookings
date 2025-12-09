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
use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['getAvailableGroup']],security: "is_granted('moderateService')"),
        new GetCollection(uriTemplate:'/service_groups',normalizationContext: ['groups' => ['availableGroupService']], security: "is_granted('moderateService')"),
        new GetCollection(uriTemplate:'/admin_groups',normalizationContext: ['groups' => ['availableGroup']], security: "is_granted('ROLE_ADMIN')"),
        new Get(normalizationContext: ['groups' => ['getAvailableGroup']], security: "is_granted('ROLE_ADMIN')"),
        new Get(uriTemplate:'/admin_group/{id}',normalizationContext: ['groups' => ['availableGroup']],security: "is_granted('moderateService')"),
        new Post(uriTemplate:'/admin_group', securityPostDenormalize: "is_granted('ROLE_ADMIN')"),
        new Patch(uriTemplate:'/admin_group/{id}', security: "is_granted('ROLE_ADMIN')"),
        new Delete(uriTemplate:'/admin_group/{id}',security: "is_granted('ROLE_ADMIN')"),
        new Post(securityPostDenormalize: "is_granted('editService', object.getService())"),
        new Put(normalizationContext: ['groups' => ['availableGroup']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(normalizationContext: ['groups' => ['availableGroup']], security: "is_granted('editService', object.getService())"),
        new Delete(security: "is_granted('editService', object.getService())"),
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['Service.id' => 'exact'])]
#[ApiFilter(ExistsFilter::class, properties: ['Service'])]

class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['availableGroup','getAvailableGroup', 'availableProvisions::read','catalogue::read', 'availableGroupService'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['availableGroup', 'availableGroupService'])]
    private ?string $provider = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['availableGroup', 'availableGroupService'])]
    private ?string $query = null;

    #[ORM\Column(length: 255)]
    #[Groups(['availableGroup', 'getAvailableGroup','availableProvisions::read','catalogue::read', 'availableGroupService'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['availableGroup'])]
    private ?string $users = null;

    /**
     * @var Collection<int, Provision>
     */
    #[ORM\ManyToMany(targetEntity: Provision::class, inversedBy: 'groups')]
    private Collection $provisions;

    #[ORM\ManyToOne(inversedBy: 'groups')]
    private ?Service $Service = null;

    public function __construct()
    {
        $this->provisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(string $query): static
    {
        $this->query = $query;

        return $this;
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

    public function getUsers(): ?string
    {
        return $this->users;
    }

    public function setUsers(?string $users): static
    {
        $this->users = $users;

        return $this;
    }

    #[Groups(['availableGroup', 'availableProvisions::read','catalogue::read', 'availableGroupService'])]
    public function getNumberOfUser(): ?int
    {
        $users = json_decode($this->users);
        if(!empty($users)) {
            return sizeof($users);
        } else {
            return 0;
        }
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

    public function getService(): ?Service
    {
        return $this->Service;
    }

    public function setService(?Service $Service): static
    {
        $this->Service = $Service;

        return $this;
    }
}
