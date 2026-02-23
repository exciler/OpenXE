<?php

namespace OpenXE\Entity\Master;

use Doctrine\ORM\Mapping as ORM;
use OpenXE\Repository\Master\AddressRepository;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table('adresse')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $typ = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $sprache = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $anschreiben = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTyp(): ?string
    {
        return $this->typ;
    }

    public function setTyp(string $typ): static
    {
        $this->typ = $typ;

        return $this;
    }

    public function getSprache(): ?string
    {
        return $this->sprache;
    }

    public function setSprache(?string $sprache): static
    {
        $this->sprache = $sprache;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAnschreiben(): ?string
    {
        return $this->anschreiben;
    }

    public function setAnschreiben(?string $anschreiben): static
    {
        $this->anschreiben = $anschreiben;

        return $this;
    }
}
