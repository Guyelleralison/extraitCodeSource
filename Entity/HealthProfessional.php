<?php

namespace App\Entity;

use App\Repository\HealthProfessionalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InnovHealth\ApiCommonBundle\Entity\AbstractEntity;
use JsonSerializable;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class HealthProfessional
 * @package App\Entity
 * @ORM\Table("health_professional_contact")
 * @ORM\Entity(repositoryClass=HealthProfessionalRepository::class)
 */
class HealthProfessional extends AbstractEntity implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Doctor $doctor
     * @ORM\OneToOne(targetEntity=Doctor::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="doctor_id", referencedColumnName="id", onDelete="CASCADE")
     * @SWG\Property(description="The doctor id registered on PassCare")
     * @Groups("public")
     */
    private $doctor;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups("public")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Google map Id")
     * @Groups("public")
     */
    private $googlePlaceId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @SWG\Property(description="Id health pro on pro database.")
     * @Groups("public")
     */
    private $franceHealthProfessionnalId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @SWG\Property(description="Speciality of the health pro")
     * @Groups("public")
     */
    private $speciality;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @SWG\Property(description="Personnal Phone number")
     * @Groups("public")
     */
    private $phoneNumber1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Label of phone number 1")
     * @Groups("public")
     */
    private $phoneLabel1;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @SWG\Property(description="Another Phone number.")
     * @Groups("public")
     */
    private $phoneNumber2;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @SWG\Property(description="Label of phone number 2.")
     * @Groups("public")
     */
    private $phoneLabel2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Comment.")
     * @Groups("public")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class, inversedBy="healthProfessionals")
     */
    private $patient;

    public function __construct()
    {
        parent::__construct();
        $this->patients = new ArrayCollection();
    }

    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): self
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $lastName): self
    {
        $this->name = $lastName;

        return $this;
    }

    public function getGooglePlaceId(): ?string
    {
        return $this->googlePlaceId;
    }

    public function setGooglePlaceId(?string $googlePlaceId): self
    {
        $this->googlePlaceId = $googlePlaceId;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'doctor' => $this->getDoctor(),
            'googlePlaceId' => $this->getGooglePlaceId(),
            'france_health_professional_id' => $this->getFranceHealthProId(),
            'speciality' => $this->getSpeciality(),
            'phoneNumber 1' => $this->getPhoneNumber1(),
            'phoneLabel 1' => $this->getPhoneLabel1(),
            'phoneNumber 2' => $this->getPhoneNumber2(),
            'phoneLabel 2' => $this->getPhoneLabel2(),
            'comment' => $this->getComment(),
            'patient' => $this->getPatient()
        ];
    }

    public function getFranceHealthProId(): ?int
    {
        return $this->franceHealthProfessionnalId;
    }

    public function setFranceHealthProId(?int $france_health_pro_id): self
    {
        $this->franceHealthProfessionnalId = $france_health_pro_id;

        return $this;
    }

    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }

    public function setSpeciality(?string $speciality): self
    {
        $this->speciality = $speciality;

        return $this;
    }

    public function getPhoneNumber1(): ?string
    {
        return $this->phoneNumber1;
    }

    public function setPhoneNumber1(?string $phoneNumber1): self
    {
        $this->phoneNumber1 = $phoneNumber1;

        return $this;
    }

    public function getPhoneLabel1(): ?string
    {
        return $this->phoneLabel1;
    }

    public function setPhoneLabel1(?string $phoneLabel1): self
    {
        $this->phoneLabel1 = $phoneLabel1;

        return $this;
    }

    public function getPhoneNumber2(): ?string
    {
        return $this->phoneNumber2;
    }

    public function setPhoneNumber2(?string $phoneNumber2): self
    {
        $this->phoneNumber2 = $phoneNumber2;

        return $this;
    }

    public function getPhoneLabel2(): ?string
    {
        return $this->phoneLabel2;
    }

    public function setPhoneLabel2(?string $phoneLabel2): self
    {
        $this->phoneLabel2 = $phoneLabel2;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;

        return $this;
    }
}
