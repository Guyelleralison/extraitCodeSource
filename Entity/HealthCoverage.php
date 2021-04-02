<?php

namespace App\Entity;

use App\Repository\HealthCoverageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InnovHealth\ApiCommonBundle\Entity\AbstractEntity;
use JsonSerializable;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=HealthCoverageRepository::class)
 */
class HealthCoverage extends AbstractEntity implements JsonSerializable
{
    /**
     *@ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @SWG\Property(description="Health coverage name.")
     * @Groups("public")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @SWG\Property(description="Health coverage identity number.")
     * @Groups("public")
     */
    private $idendityNumber;

    /**
     * @ORM\Column(type="integer")
     * @SWG\Property(description="Health coverage type. 0 = MUTUAL ; 1 = SUPPLEMENTAL")
     * @Groups("public")
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=20)
     * @SWG\Property(description="Health coverage string. Mutual or Supplemental.")
     * @Groups("public")
     */
    private $stype;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class, inversedBy="healthCoverages")
     * @SWG\Property(description="Patient linked with health coverage.")
     * @Groups("public")
     */
    private $patient;

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

    public function getIdendityNumber(): ?string
    {
        return $this->idendityNumber;
    }

    public function setIdendityNumber(?string $idendityNumber): self
    {
        $this->idendityNumber = $idendityNumber;

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
            'identity_number' => $this->getIdendityNumber(),
            'type' => $this->getType(),
            'stype' => $this->getStype()
        ];
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStype(): ?string
    {
        return $this->stype;
    }

    public function setStype(string $stype): self
    {
        $this->stype = $stype;

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
