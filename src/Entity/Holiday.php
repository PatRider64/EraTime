<?php

namespace App\Entity;

use App\Repository\HolidayRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HolidayRepository::class)]
class Holiday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\ManyToOne(inversedBy: 'holidays')]
    private ?UserEraTime $users = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDemande = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(nullable: true)]
    private ?float $nbTotalDays = null;

    #[ORM\Column(nullable: true)]
    private ?bool $halfHolidayAfternoonStart = null;

    #[ORM\Column(nullable: true)]
    private ?bool $halfHolidayMorningEnd = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $halfHolidaySingle = null;

    #[ORM\ManyToOne(inversedBy: 'holidays')]
    private ?HolidayType $type = null;

    #[ORM\Column(nullable: true)]
    private ?bool $administration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getUsers(): ?UserEraTime
    {
        return $this->users;
    }

    public function setUsers(?UserEraTime $users): self
    {
        $this->users = $users;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(?\DateTimeInterface $dateDemande): self
    {
        $this->dateDemande = $dateDemande;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): self
    {
        $this->observation = $observation;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getNbTotalDays(): ?float
    {
        return $this->nbTotalDays;
    }

    public function setNbTotalDays(?float $nbTotalDays): self
    {
        $this->nbTotalDays = $nbTotalDays;

        return $this;
    }

    public function isHalfHolidayAfternoonStart(): ?bool
    {
        return $this->halfHolidayAfternoonStart;
    }

    public function setHalfHolidayAfternoonStart(?bool $halfHolidayAfternoonStart): self
    {
        $this->halfHolidayAfternoonStart = $halfHolidayAfternoonStart;

        return $this;
    }

    public function isHalfHolidayMorningEnd(): ?bool
    {
        return $this->halfHolidayMorningEnd;
    }

    public function setHalfHolidayMorningEnd(?bool $halfHolidayMorningEnd): self
    {
        $this->halfHolidayMorningEnd = $halfHolidayMorningEnd;

        return $this;
    }

    public function getHalfHolidaySingle(): ?string
    {
        return $this->halfHolidaySingle;
    }

    public function setHalfHolidaySingle(?string $halfHolidaySingle): self
    {
        $this->halfHolidaySingle = $halfHolidaySingle;

        return $this;
    }

    public function getType(): ?HolidayType
    {
        return $this->type;
    }

    public function setType(?HolidayType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isAdministration(): ?bool
    {
        return $this->administration;
    }

    public function setAdministration(?bool $administration): static
    {
        $this->administration = $administration;

        return $this;
    }
}
