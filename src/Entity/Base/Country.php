<?php

namespace OpenXE\Entity\Base;

use Doctrine\ORM\Mapping as ORM;
use OpenXE\Repository\Base\CountryRepository;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(name: 'laender')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2)]
    private ?string $iso = null;

    #[ORM\Column(length: 3)]
    private ?string $iso3 = null;

    #[ORM\Column('num_code', length: 3)]
    private ?string $isoNum = null;

    #[ORM\Column(length: 255)]
    private ?string $bezeichnung_de = null;

    #[ORM\Column(length: 255)]
    private ?string $bezeichnung_en = null;

    #[ORM\Column]
    private ?bool $eu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIso(): ?string
    {
        return $this->iso;
    }

    public function setIso(string $iso): static
    {
        $this->iso = $iso;

        return $this;
    }

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(string $iso3): static
    {
        $this->iso3 = $iso3;

        return $this;
    }

    public function getIsoNum(): ?string
    {
        return $this->isoNum;
    }

    public function setIsoNum(string $isoNum): static
    {
        $this->isoNum = $isoNum;

        return $this;
    }

    public function getBezeichnungDe(): ?string
    {
        return $this->bezeichnung_de;
    }

    public function setBezeichnungDe(string $bezeichnung_de): static
    {
        $this->bezeichnung_de = $bezeichnung_de;

        return $this;
    }

    public function getBezeichnungEn(): ?string
    {
        return $this->bezeichnung_en;
    }

    public function setBezeichnungEn(string $bezeichnung_en): static
    {
        $this->bezeichnung_en = $bezeichnung_en;

        return $this;
    }

    public function isEu(): ?bool
    {
        return $this->eu;
    }

    public function setEu(bool $eu): static
    {
        $this->eu = $eu;

        return $this;
    }
}
