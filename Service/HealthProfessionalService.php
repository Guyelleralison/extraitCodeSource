<?php

namespace App\Service;

use App\Entity\HealthProfessional;
use App\Entity\Patient;
use App\Exception\HealthProfessionalNotFoundException;
use App\Exception\HealthProfessionalNotMatchPatientException;
use App\Exception\IndividualActorNotFoundException;
use App\Repository\HealthProfessionalRepository;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidOrderParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidPaginationParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidSortParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\OutOfRangePageException;
use InnovHealth\ApiCommonBundle\Exception\Technical\MissingCodeException;
use InnovHealth\ApiCommonBundle\Service\LoggerService;
use Monolog\Logger;
use InnovHealth\ApiCommonBundle\Entity\Api\Referential\ConsultationSpeciality;
use InnovHealth\ApiCommonBundle\Exception\ApiException;
use InnovHealth\ApiCommonBundle\Exception\File\ConsultationSpecialityException;
use InnovHealth\ApiCommonBundle\Service\Api\ReferentialService;

/**
 * Class HealthProfessionalService
 * @package App\Service
 */
class HealthProfessionalService
{
    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;
    /**
     * @var HealthProfessionalRepository
     */
    private HealthProfessionalRepository $healthProfessionalRepository;
    /**
     * @var PatientService $patientService
     */
    private PatientService $patientService;
    /**
     * @var DoctorService $doctorService
     */
    private DoctorService $doctorService;
    /**
     * @var SpecialityService $specialityService
     */
    private SpecialityService $specialityService;
    /**
     * @var ReferentialService $referentialService
     */
    private ReferentialService $referentialService;

    /**
     * HealthProfessionalService constructor.
     * @param HealthProfessionalRepository $healthProfessionalRepository
     * @param LoggerService $loggerService
     */
    public function __construct(
        HealthProfessionalRepository $healthProfessionalRepository,
        LoggerService $loggerService,
        PatientService $patientService,
        DoctorService $doctorService,
        SpecialityService $specialityService,
        ReferentialService $referentialService
    ) {
        $this->healthProfessionalRepository = $healthProfessionalRepository;
        $this->loggerService = $loggerService;
        $this->patientService = $patientService;
        $this->doctorService = $doctorService;
        $this->specialityService = $specialityService;
        $this->referentialService = $referentialService;
    }

    /**
     * @return array
     * @throws InvalidOrderParameterException
     * @throws InvalidPaginationParameterException
     * @throws InvalidSortParameterException
     * @throws MissingCodeException
     * @throws OutOfRangePageException
     * @throws HealthProfessionalNotFoundException
     */
    public function getHealthProfessionals(): array
    {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');

        $healthProfessionals = $this->healthProfessionalRepository->findAll();

        if (count($healthProfessionals) === 0) {
            $e = new HealthProfessionalNotFoundException();
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
        return $healthProfessionals;
    }

    /**
     * @param int $id
     * @return HealthProfessional
     * @throws MissingCodeException
     * @throws HealthProfessionalNotFoundException
     */
    public function getHealthProfessionalById(int $id): HealthProfessional
    {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');

        /**
         * @var HealthProfessional $healthProfessional
         */
        $healthProfessional = $this->healthProfessionalRepository->find($id);
        if ($healthProfessional === null) {
            $e = new HealthProfessionalNotFoundException($id);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
        return $healthProfessional;
    }

    /**
     * @param int $doctorId
     * @param int $specialityId
     * @param string $googlePlaceId
     * @param string $firstName
     * @param string $lastName
     * @param string $phoneNumber
     * @return HealthProfessional
     * @throws MissingCodeException
     * @throws DoctorNotFoundException
     * @throws ConsultationSpecialityException
     */
    public function createHealthProfessional(
        int $patientId,
        ?int $doctorId,
        ?string $speciality,
        ?int $franceHealthProId,
        ?string $name,
        ?string $phoneNumber1,
        ?string $phoneLabel1,
        ?string $phoneNumber2,
        ?string $phoneLabel2,
        ?string $googlePlaceId,
        ?string $comment
    ): HealthProfessional {
        $healthProfessional = new HealthProfessional();
        $patient = $this->patientService->getPatientById($patientId);
        $healthProfessional
            ->setName(
                $name !== null ? $name : null
            )
            ->setPhoneNumber1(
                $phoneNumber1 !== null ? $phoneNumber1 : null
            )
            ->setPhoneLabel1(
                $phoneLabel1 !== null ? $phoneLabel1 : null
            )
            ->setPhoneNumber2(
                $phoneNumber2 !== null ? $phoneNumber2 : null
            )
            ->setPhoneLabel2(
                $phoneLabel2 !== null ? $phoneLabel2 : null
            )
            ->setComment(
                $comment !== null ? $comment : null
            )
            ->setSpeciality(
                $speciality !== null ? $speciality : null
            )
            ->setPatient($patient);

        if ($doctorId !== null && $doctorId !== 0) {
            $doctor = $this->doctorService->getDoctorById($doctorId);
            $healthProfessional
                ->setDoctor($doctor)
                ->setName(
                    $doctor->getFirstName() . ' ' . $doctor->getLastName()
                )
                ->setPhoneNumber1(
                    $doctor->getMobilePhone() !== null ?
                    $doctor->getMobilePhone() : $phoneNumber1
                );
        }
        if ($googlePlaceId !== null) {
            $healthProfessional
                ->setGooglePlaceId($googlePlaceId);
            //auto complete informations from this id
            #code here...
        }
        if ($franceHealthProId !== null && $franceHealthProId !== 0) {
            $healthProfessional->setFranceHealthProId($franceHealthProId);
            //auto complete informations from this id
            #code here...
        }
        return $this->healthProfessionalRepository->store($healthProfessional);
    }

    /**
     * @param int $id
     * @param int $doctorId
     * @param int $specialityId
     * @param string $googlePlaceId
     * @param string $firstName
     * @param string $lastName
     * @param string $phoneNumber
     * @return HealthProfessional
     * @throws MissingCodeException
     * @throws DoctorNotFoundException
     * @throws PatientNotFoundException
     */
    public function updateHealthProfessional(
        int $id,
        int $patientId,
        ?int $doctorId,
        ?string $speciality,
        ?int $franceHealthProId,
        ?string $name,
        ?string $phoneNumber1,
        ?string $phoneLabel1,
        ?string $phoneNumber2,
        ?string $phoneLabel2,
        ?string $googlePlaceId,
        ?string $comment
    ): HealthProfessional {
        $healthProfessional = $this->healthProfessionalRepository->findOneBy(['id' => $id]);

        $patient = $this->patientService->getPatientById($patientId);

        if ($healthProfessional->getPatient() == $patient) {
            $healthProfessional
                ->setName(
                    $name !== null ? $name : $healthProfessional->getName()
                )
                ->setPhoneNumber1(
                    $phoneNumber1 !== null ? $phoneNumber1 : $healthProfessional->getPhoneNumber1()
                )
                ->setPhoneLabel1(
                    $phoneLabel1 !== null ? $phoneLabel1 : $healthProfessional->getPhoneLabel1()
                )
                ->setPhoneNumber2(
                    $phoneNumber2 !== null ? $phoneNumber2 : $healthProfessional->getPhoneNumber2()
                )
                ->setPhoneLabel2(
                    $phoneLabel2 !== null ? $phoneLabel2 : $healthProfessional->getPhoneLabel2()
                )
                ->setComment(
                    $comment !== null ? $comment : $healthProfessional->getComment()
                )
                ->setSpeciality(
                    $speciality !== null ? $speciality : $healthProfessional->getSpeciality()
                );

            if ($doctorId !== null) {
                $doctor = $this->doctorService->getDoctorById($doctorId);
                $healthProfessional
                    ->setDoctor($doctor)
                    ->setName($doctor->getFirstName() . ' ' . $doctor->getLastName())
                    ->setPhoneNumber1($doctor->getMobilePhone());
            }
            if ($googlePlaceId !== null) {
                $healthProfessional
                    ->setGooglePlaceId($googlePlaceId);
            }
            if ($franceHealthProId !== null) {
                $healthProfessional->setFranceHealthProId($franceHealthProId);
            }
            $healthProfessional = $this->healthProfessionalRepository->store($healthProfessional);
        } else {
            $e = new HealthProfessionalNotMatchPatientException($id, $patientId);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        return $healthProfessional;
    }

    /**
     * @param int $id
     * @param int $patientId
     * @return bool
     * @throws MissingCodeException
     * @throws DoctorNotFoundException
     * @throws PatientNotFoundException
     * @throws HealthProfessionalNotMatchPatientException
     * @throws HealthProfessionalNotFoundException
     */
    public function deleteHealthProfessional(
        int $id,
        int $patientId
    ): bool {
        $healthProfessional = $this->healthProfessionalRepository->findOneBy(['id' => $id]);

        if ($healthProfessional->getPatient()->getId() == $patientId) {
            $this->healthProfessionalRepository->delete($id);
            $healthProfessional = $this->healthProfessionalRepository->findOneBy(['id' => $id]);
            if (!$healthProfessional) {
                return true;
            }
        } else {
            $e = new HealthProfessionalNotMatchPatientException($id, $patientId);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        return false;
    }

    public function saveHealthProfessional(
        HealthProfessional $healthProfessional
    ): HealthProfessional {
        return $this->healthProfessionalRepository->store($healthProfessional);
    }

    public function getHealthProfessionalByPatient(
        Patient $patient
    ): array {
        return $this->healthProfessionalRepository->findBy([
            'patient' => $patient
        ]);
    }
}
