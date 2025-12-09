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
use App\Repository\RuleResourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RuleResourceRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
        new Post(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
        new Put(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
        new Delete(normalizationContext: ['groups' => ['ruleRessource::read']], security: "is_granted('ROLE_ADMIN')"),
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['Rule.id' => 'exact'])]
class RuleResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ruleRessource::read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ruleRessource::read'])]
    private ?Resource $Resource = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rules $Rule = null;

    #[ORM\Column]
    #[Groups(['ruleRessource::read'])]
    private array $Arguments = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRule(): ?Rules
    {
        return $this->Rule;
    }

    public function setRule(?Rules $Rule): static
    {
        $this->Rule = $Rule;

        return $this;
    }

    public function getArguments(): array
    {
        return $this->Arguments;
    }

    public function setArguments(array $Arguments): static
    {
        $this->Arguments = $Arguments;

        return $this;
    }
}
