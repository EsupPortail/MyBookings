<?php

namespace App\Entity;

use App\Repository\AclRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AclRepository::class)]
class Acl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service::read', 'ressourcerie::read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'acls')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['service::read', 'ressourcerie::read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'acls')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user::read'])]
    private ?Service $service = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user::read', 'service::read'])]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

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
