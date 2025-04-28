<?php

namespace App\Entity;

use App\Repository\EraTimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserEraTimeRepository::class)]
class UserEraTime  implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $login = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $lastActivityDate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(nullable: true)]
    private array $category = [];

    #[ORM\Column(nullable: true)]
    private array $roles = [];

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'userEraTimes')]
    private ?self $superior = null;

    #[ORM\OneToMany(mappedBy: 'superior', targetEntity: self::class)]
    private Collection $userEraTimes;

    #[ORM\OneToMany(mappedBy: 'users', targetEntity: Holiday::class)]
    private Collection $holidays;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $superior2 = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $superior3 = null;


    public function __construct()
    {
        $this->userEraTimes = new ArrayCollection();
        $this->holidays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getLastActivityDate(): ?string
    {
        return $this->lastActivityDate;
    }

    public function setLastActivityDate(string $lastActivityDate): self
    {
        $this->lastActivityDate = $lastActivityDate;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials() {

    }

    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    public function getCategory(): array
    {
        return $this->category;
    }

    public function setCategory(?array $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSuperior(): ?self
    {
        return $this->superior;
    }

    public function setSuperior(?self $superior): self
    {
        $this->superior = $superior;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUserEraTimes(): Collection
    {
        return $this->userEraTimes;
    }

    public function addUserEraTime(self $userEraTime): self
    {
        if (!$this->userEraTimes->contains($userEraTime)) {
            $this->userEraTimes->add($userEraTime);
            $userEraTime->setSuperior($this);
        }

        return $this;
    }

    public function removeUserEraTime(self $userEraTime): self
    {
        if ($this->userEraTimes->removeElement($userEraTime)) {
            // set the owning side to null (unless already changed)
            if ($userEraTime->getSuperior() === $this) {
                $userEraTime->setSuperior(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function getHolidays(): Collection
    {
        return $this->holidays;
    }

    public function addHoliday(Holiday $holiday): self
    {
        if (!$this->holidays->contains($holiday)) {
            $this->holidays->add($holiday);
            $holiday->setUsers($this);
        }

        return $this;
    }

    public function removeHoliday(Holiday $holiday): self
    {
        if ($this->holidays->removeElement($holiday)) {
            // set the owning side to null (unless already changed)
            if ($holiday->getUsers() === $this) {
                $holiday->setUsers(null);
            }
        }

        return $this;
    }

    public function getSuperior2(): ?self
    {
        return $this->superior2;
    }

    public function setSuperior2(?self $superior2): static
    {
        $this->superior2 = $superior2;

        return $this;
    }

    public function getSuperior3(): ?self
    {
        return $this->superior3;
    }

    public function setSuperior3(?self $superior3): static
    {
        $this->superior3 = $superior3;

        return $this;
    }
}
