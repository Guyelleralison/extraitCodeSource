<?php

namespace App\Entity;

use App\Repository\PatientAccountLinkedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=PatientAccountLinkedRepository::class)
 */
class PatientAccountLinked implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PatientRelation::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $relation;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $patientLinked;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasBloodLink;

    /**
     * @ORM\ManyToOne(targetEntity=Patient::class, inversedBy="AccountLinked")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $parent_patient;


    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelation(): ?PatientRelation
    {
        return $this->relation;
    }

    public function setRelation(?PatientRelation $relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    public function getPatientLinked(): ?Patient
    {
        return $this->patientLinked;
    }

    public function setPatientLinked(?Patient $patientLinked): self
    {
        $this->patientLinked = $patientLinked;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $return = [
            'relation' => $this->getRelation(),
            'patient_parent' => $this->getParentPatient(),
            'patient_linked' => $this->getPatientLinked(),
            'hasBloodLink' => $this->getHasBloodLink(),
            'id' => $this->getId()
        ];
        return $return;
    }

    public function getHasBloodLink(): ?bool
    {
        return $this->hasBloodLink;
    }

    public function setHasBloodLink(?bool $hasBloodLink): self
    {
        $this->hasBloodLink = $hasBloodLink;

        return $this;
    }

    public function getParentPatient(): ?Patient
    {
        return $this->parent_patient;
    }

    public function setParentPatient(?Patient $parent_patient): self
    {
        $this->parent_patient = $parent_patient;

        return $this;
    }
}
