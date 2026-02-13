<?php
namespace OpenXE\Entity\Users;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use OpenXE\Repository\Users\UserRepository;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private string $username;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $repassword = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, length: 65535)]
    private ?string $settings = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childUsers')]
    #[ORM\JoinColumn(name: 'parentuser')]
    private ?self $parentuser = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentuser')]
    private Collection $childUsers;

    #[ORM\Column(nullable: true)]
    private ?bool $activ = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $fehllogins = null;

    #[ORM\Column]
    private ?\DateTime $logdatei = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $startseite = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hwtoken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hwkey = null;

    #[ORM\Column(nullable: true)]
    private ?int $hwcounter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motppin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motpsecret = null;

    #[ORM\Column(nullable: true)]
    private ?bool $externlogin = null;

    #[ORM\Column]
    private ?bool $projekt_bevorzugen = null;

    #[ORM\Column]
    private ?bool $email_bevorzugen = null;

    #[ORM\Column(length: 64)]
    private ?string $rfidtag = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vorlage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kalender_passwort = null;

    #[ORM\Column]
    private ?bool $kalender_ausblenden = null;

    #[ORM\Column(nullable: true)]
    private ?bool $kalender_aktiv = null;

    #[ORM\Column(nullable: true)]
    private ?bool $gpsstechuhr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $internebezeichnung = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hwdatablock = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sprachebevorzugen = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vergessencode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $vergessenzeit = null;

    #[ORM\Column(nullable: true)]
    private ?bool $chat_popup = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $defaultcolor = null;

    #[ORM\Column(nullable: true)]
    private ?bool $docscan_aktiv = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $docscan_passwort = null;

    #[ORM\Column(nullable: true)]
    private ?bool $callcenter_notification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stechuhrdevice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $role = null;

    public function __construct()
    {
        $this->childUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isRepassword(): ?bool
    {
        return $this->repassword;
    }

    public function setRepassword(bool $repassword): static
    {
        $this->repassword = $repassword;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSettings(): ?string
    {
        return $this->settings;
    }

    public function setSettings(string $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function getParentuser(): ?self
    {
        return $this->parentuser;
    }

    public function setParentuser(?self $parentuser): static
    {
        $this->parentuser = $parentuser;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildUsers(): Collection
    {
        return $this->childUsers;
    }

    public function addChildUser(self $childUser): static
    {
        if (!$this->childUsers->contains($childUser)) {
            $this->childUsers->add($childUser);
            $childUser->setParentuser($this);
        }

        return $this;
    }

    public function removeChildUser(self $childUser): static
    {
        if ($this->childUsers->removeElement($childUser)) {
            // set the owning side to null (unless already changed)
            if ($childUser->getParentuser() === $this) {
                $childUser->setParentuser(null);
            }
        }

        return $this;
    }

    public function isActiv(): ?bool
    {
        return $this->activ;
    }

    public function setActiv(?bool $activ): static
    {
        $this->activ = $activ;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getFehllogins(): ?int
    {
        return $this->fehllogins;
    }

    public function setFehllogins(int $fehllogins): static
    {
        $this->fehllogins = $fehllogins;

        return $this;
    }

    public function getLogdatei(): ?\DateTime
    {
        return $this->logdatei;
    }

    public function setLogdatei(\DateTime $logdatei): static
    {
        $this->logdatei = $logdatei;

        return $this;
    }

    public function getStartseite(): ?string
    {
        return $this->startseite;
    }

    public function setStartseite(?string $startseite): static
    {
        $this->startseite = $startseite;

        return $this;
    }

    public function isHwtoken(): ?bool
    {
        return $this->hwtoken;
    }

    public function setHwtoken(?bool $hwtoken): static
    {
        $this->hwtoken = $hwtoken;

        return $this;
    }

    public function getHwkey(): ?string
    {
        return $this->hwkey;
    }

    public function setHwkey(?string $hwkey): static
    {
        $this->hwkey = $hwkey;

        return $this;
    }

    public function getHwcounter(): ?int
    {
        return $this->hwcounter;
    }

    public function setHwcounter(?int $hwcounter): static
    {
        $this->hwcounter = $hwcounter;

        return $this;
    }

    public function getMotppin(): ?string
    {
        return $this->motppin;
    }

    public function setMotppin(?string $motppin): static
    {
        $this->motppin = $motppin;

        return $this;
    }

    public function getMotpsecret(): ?string
    {
        return $this->motpsecret;
    }

    public function setMotpsecret(?string $motpsecret): static
    {
        $this->motpsecret = $motpsecret;

        return $this;
    }

    public function isExternlogin(): ?bool
    {
        return $this->externlogin;
    }

    public function setExternlogin(?bool $externlogin): static
    {
        $this->externlogin = $externlogin;

        return $this;
    }

    public function isProjektBevorzugen(): ?bool
    {
        return $this->projekt_bevorzugen;
    }

    public function setProjektBevorzugen(bool $projekt_bevorzugen): static
    {
        $this->projekt_bevorzugen = $projekt_bevorzugen;

        return $this;
    }

    public function isEmailBevorzugen(): ?bool
    {
        return $this->email_bevorzugen;
    }

    public function setEmailBevorzugen(bool $email_bevorzugen): static
    {
        $this->email_bevorzugen = $email_bevorzugen;

        return $this;
    }

    public function getRfidtag(): ?string
    {
        return $this->rfidtag;
    }

    public function setRfidtag(string $rfidtag): static
    {
        $this->rfidtag = $rfidtag;

        return $this;
    }

    public function getVorlage(): ?string
    {
        return $this->vorlage;
    }

    public function setVorlage(?string $vorlage): static
    {
        $this->vorlage = $vorlage;

        return $this;
    }

    public function getKalenderPasswort(): ?string
    {
        return $this->kalender_passwort;
    }

    public function setKalenderPasswort(?string $kalender_passwort): static
    {
        $this->kalender_passwort = $kalender_passwort;

        return $this;
    }

    public function isKalenderAusblenden(): ?bool
    {
        return $this->kalender_ausblenden;
    }

    public function setKalenderAusblenden(bool $kalender_ausblenden): static
    {
        $this->kalender_ausblenden = $kalender_ausblenden;

        return $this;
    }

    public function isKalenderAktiv(): ?bool
    {
        return $this->kalender_aktiv;
    }

    public function setKalenderAktiv(?bool $kalender_aktiv): static
    {
        $this->kalender_aktiv = $kalender_aktiv;

        return $this;
    }

    public function isGpsstechuhr(): ?bool
    {
        return $this->gpsstechuhr;
    }

    public function setGpsstechuhr(?bool $gpsstechuhr): static
    {
        $this->gpsstechuhr = $gpsstechuhr;

        return $this;
    }

    public function getInternebezeichnung(): ?string
    {
        return $this->internebezeichnung;
    }

    public function setInternebezeichnung(?string $internebezeichnung): static
    {
        $this->internebezeichnung = $internebezeichnung;

        return $this;
    }

    public function getHwdatablock(): ?string
    {
        return $this->hwdatablock;
    }

    public function setHwdatablock(?string $hwdatablock): static
    {
        $this->hwdatablock = $hwdatablock;

        return $this;
    }

    public function getSprachebevorzugen(): ?string
    {
        return $this->sprachebevorzugen;
    }

    public function setSprachebevorzugen(?string $sprachebevorzugen): static
    {
        $this->sprachebevorzugen = $sprachebevorzugen;

        return $this;
    }

    public function getVergessencode(): ?string
    {
        return $this->vergessencode;
    }

    public function setVergessencode(?string $vergessencode): static
    {
        $this->vergessencode = $vergessencode;

        return $this;
    }

    public function getVergessenzeit(): ?\DateTime
    {
        return $this->vergessenzeit;
    }

    public function setVergessenzeit(?\DateTime $vergessenzeit): static
    {
        $this->vergessenzeit = $vergessenzeit;

        return $this;
    }

    public function isChatPopup(): ?bool
    {
        return $this->chat_popup;
    }

    public function setChatPopup(?bool $chat_popup): static
    {
        $this->chat_popup = $chat_popup;

        return $this;
    }

    public function getDefaultcolor(): ?string
    {
        return $this->defaultcolor;
    }

    public function setDefaultcolor(?string $defaultcolor): static
    {
        $this->defaultcolor = $defaultcolor;

        return $this;
    }

    public function isDocscanAktiv(): ?bool
    {
        return $this->docscan_aktiv;
    }

    public function setDocscanAktiv(?bool $docscan_aktiv): static
    {
        $this->docscan_aktiv = $docscan_aktiv;

        return $this;
    }

    public function getDocscanPasswort(): ?string
    {
        return $this->docscan_passwort;
    }

    public function setDocscanPasswort(?string $docscan_passwort): static
    {
        $this->docscan_passwort = $docscan_passwort;

        return $this;
    }

    public function isCallcenterNotification(): ?bool
    {
        return $this->callcenter_notification;
    }

    public function setCallcenterNotification(?bool $callcenter_notification): static
    {
        $this->callcenter_notification = $callcenter_notification;

        return $this;
    }

    public function getStechuhrdevice(): ?string
    {
        return $this->stechuhrdevice;
    }

    public function setStechuhrdevice(?string $stechuhrdevice): static
    {
        $this->stechuhrdevice = $stechuhrdevice;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }


}