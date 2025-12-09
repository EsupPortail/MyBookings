<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\RulesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RulesRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate: 'rules/{id}', normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(uriTemplate: 'rules',normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
        new Post(uriTemplate: 'rules',normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
        new Put(uriTemplate: 'rules/{id}',normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
        new Patch(uriTemplate: 'rules/{id}',normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
        new Delete(uriTemplate: 'rules/{id}',normalizationContext: ['groups' => ['rule::read']], security: "is_granted('ROLE_ADMIN')"),
    ],
)]
class Rules
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rule::read', 'ruleRessource::read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['rule::read', 'ruleRessource::read'])]
    private ?string $Title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['rule::read', 'ruleRessource::read'])]
    private ?string $Description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['rule::read', 'ruleRessource::read'])]
    private ?string $Method = null;

    #[ORM\Column]
    #[Groups(['rule::read', 'ruleRessource::read'])]
    private array $Arguments = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): static
    {
        $this->Title = $Title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->Method;
    }

    public function setMethod(string $Method): static
    {
        $this->Method = $Method;

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
