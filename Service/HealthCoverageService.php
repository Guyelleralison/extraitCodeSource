<?php

namespace App\Service;

use App\Entity\HealthCoverage;
use App\Entity\HealthProfessional;
use App\Entity\Patient;
use App\Exception\HealthCoverageNotFoundException;
use App\Exception\HealthCoverageListNotFoundException;
use App\Exception\HealthCoverageNotMatchPatientException;
use App\Exception\HealthMutualNotFoundException;
use App\Exception\HealthSupplementalNotFoundException;
use App\Repository\HealthCoverageRepository;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidOrderParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidPaginationParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidSortParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\OutOfRangePageException;
use InnovHealth\ApiCommonBundle\Exception\Technical\MissingCodeException;
use InnovHealth\ApiCommonBundle\Service\LoggerService;
use Monolog\Logger;
use InnovHealth\ApiCommonBundle\Entity\Api\Referential\ConsultationSpeciality;
use InnovHealth\ApiCommonBundle\Enum\HealthCoverageTypeEnum;
use InnovHealth\ApiCommonBundle\Exception\ApiException;
use InnovHealth\ApiCommonBundle\Exception\File\ConsultationSpecialityException;
use InnovHealth\ApiCommonBundle\Service\Api\ReferentialService;
use stdClass;

/**
 * Class HealthCoverageService
 * @package App\Service
 */
class HealthCoverageService
{
    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;
    /**
     * @var HealthCoverageRepository
     */
    private HealthCoverageRepository $healthCoverageRepository;
    /**
     * @var PatientService $patientService
     */
    private PatientService $patientService;

    /**
     * HealthCoverageService constructor.
     * @param HealthCoverageRepository $healthCoverageRepository
     * @param LoggerService $loggerService
     */
    public function __construct(
        HealthCoverageRepository $healthCoverageRepository,
        LoggerService $loggerService,
        PatientService $patientService
    ) {
        $this->healthCoverageRepository = $healthCoverageRepository;
        $this->loggerService = $loggerService;
        $this->patientService = $patientService;
    }

    /**
     * @param Patient $patient
     * @return array
     * @throws InvalidOrderParameterException
     * @throws InvalidPaginationParameterException
     * @throws InvalidSortParameterException
     * @throws MissingCodeException
     * @throws OutOfRangePageException
     * @throws HealthCoverageListNotFoundException
     */
    public function getHealthCoverages(
        Patient $patient
    ): stdClass {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');

        $out = new stdClass();
        $mutuals = [];
        $supplementals = [];

        $healthCoverages = $this->healthCoverageRepository->findBy([
            'patient' => $patient
        ]);

        foreach ($healthCoverages as $coverage) {
            if ($coverage->getType() == HealthCoverageTypeEnum::MUTUAL) {
                $mutuals[] = $coverage;
            } elseif ($coverage->getType() == HealthCoverageTypeEnum::SUPPLEMENTAL) {
                $supplementals[] = $coverage;
            }
        }

        $out->mutuals = $mutuals;
        $out->supplementals = $supplementals;

        if (count($healthCoverages) === 0) {
            $e = new HealthCoverageListNotFoundException();
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
        return $out;
    }

    /**
     * @param int $id
     * @return HealthCoverage
     * @throws MissingCodeException
     * @throws HealthCoverageNotFoundException
     */
    public function getHealthCoverageById(int $id): HealthCoverage
    {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');

        /**
         * @var HealthCoverage $healthCoverage
         */
        $healthCoverage = $this->healthCoverageRepository->find($id);
        if ($healthCoverage === null) {
            $e = new HealthCoverageNotFoundException($id);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
        return $healthCoverage;
    }

    /**
     * @param int $patientId
     * @param string $name
     * @param string $identity_number
     * @param int $type
     * @return HealthCoverage
     * @throws MissingCodeException
     */
    public function createHealthCoverage(
        int $patientId,
        string $type,
        ?string $name,
        ?string $identity_number
    ): HealthCoverage {
        $healthCoverage = new HealthCoverage();
        $patient = $this->patientService->getPatientById($patientId);
        $healthCoverage
            ->setName(
                $name !== null ? $name : null
            )
            ->setIdendityNumber(
                $identity_number !== null ? $identity_number : null
            )
            ->setType(
                $type == 'MUTUAL' ? 0 : 1
            )
            ->setStype(
                $type
            )
            ->setPatient(
                $patient
            );
        return $this->healthCoverageRepository->store($healthCoverage);
    }

    /**
     * @param int $id
     * @param int $type
     * @param string $name
     * @param string $identity_number
     * @return HealthCoverage
     * @throws MissingCodeException
     */
    public function updateHealthCoverage(
        int $id,
        int $patientId,
        ?string $type,
        ?string $name,
        ?string $identity_number
    ): HealthCoverage {
        $healthCoverage = $this->healthCoverageRepository->findOneBy(['id' => $id]);

        $patient = $this->patientService->getPatientById($patientId);

        if ($healthCoverage->getPatient() == $patient) {
            $healthCoverage
                ->setName(
                    $name !== null ? $name : $healthCoverage->getName()
                )
                ->setIdendityNumber(
                    $identity_number !== null ? $identity_number : $healthCoverage->getIdendityNumber()
                )
                ->setStype(
                    $type !== null ? $type : $healthCoverage->getSType()
                )
                ->setType(
                    $healthCoverage->getSType() == 'MUTUAL' ? 0 : 1
                );
        } else {
            $e = new HealthCoverageNotMatchPatientException($id, $patientId);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        return $this->healthCoverageRepository->store($healthCoverage);
    }

    /**
     * @param int $id
     * @param int $patientId
     * @return bool
     * @throws MissingCodeException
     * @throws PatientNotFoundException
     * @throws HealthCoverageNotMatchPatientException
     * @throws HealthCoverageNotFoundException
     */
    public function deleteHealthCoverage(
        int $id,
        int $patientId
    ): bool {
        $healthCoverage = $this->healthCoverageRepository->findOneBy(['id' => $id]);

        if ($healthCoverage->getPatient()->getId() == $patientId) {
            $this->healthCoverageRepository->delete($id);
            $healthCoverage = $this->healthCoverageRepository->findOneBy(['id' => $id]);
            if (!$healthCoverage) {
                return true;
            }
        } else {
            $e = new HealthCoverageNotMatchPatientException($id, $patientId);
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            throw $e;
        }
        return false;
    }

    public function getHealthCoverageByPatient(
        Patient $patient
    ): array {
        return $this->healthProfessionalRepository->findBy([
            'patient' => $patient
        ]);
    }
}
