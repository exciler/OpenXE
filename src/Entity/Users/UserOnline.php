<?php

namespace OpenXE\Entity\Users;

use Doctrine\ORM\Mapping as ORM;
use OpenXE\Repository\Users\UserOnlineRepository;

#[ORM\Entity(repositoryClass: UserOnlineRepository::class)]
#[ORM\Table(name: 'useronline')]
class UserOnline
{
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $login = null;

    #[ORM\Column(length: 255)]
    #[ORM\Id]
    private ?string $sessionid = null;

    #[ORM\Column(length: 46)]
    private ?string $ip = null;

    #[ORM\Column]
    private ?\DateTime $time = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isLogin(): ?bool
    {
        return $this->login;
    }

    public function setLogin(bool $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getSessionid(): ?string
    {
        return $this->sessionid;
    }

    public function setSessionid(string $sessionid): static
    {
        $this->sessionid = $sessionid;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): static
    {
        $this->time = $time;

        return $this;
    }
}
