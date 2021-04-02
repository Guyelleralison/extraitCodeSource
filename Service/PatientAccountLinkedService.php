<?php

namespace App\Service;

use App\Entity\PatientAccountLinked;
use App\Entity\PatientRelation;
use App\Repository\PatientAccountLinkedRepository;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidOrderParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidPaginationParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\InvalidSortParameterException;
use InnovHealth\ApiCommonBundle\Exception\Pagination\OutOfRangePageException;
use InnovHealth\ApiCommonBundle\Exception\Technical\MissingCodeException;
use InnovHealth\ApiCommonBundle\Service\LoggerService;
use Monolog\Logger;
use Exception;

class PatientAccountLinkedService
{
    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;
    /**
     * @var PatientAccountLinkedRepository
     */
    private PatientAccountLinkedRepository $patientAccountLinkedRepository;
    /**
     * @var PatientService $patientService
     */
    private PatientService $patientService;


    /**
     * GroupService constructor.
     * @param FamilyMemberRepository $familyMemberRepository
     * @param LoggerService $loggerService
     */
    public function __construct(
        LoggerService $loggerService,
        PatientAccountLinkedRepository $patientAccountLinkedRepository,
        PatientService $patientService
    ) {
        $this->loggerService = $loggerService;
        $this->patientAccountLinkedRepository = $patientAccountLinkedRepository;
        $this->patientService = $patientService;
    }

    /**
     * @param  int $idPatient
     * @throws Exception
     */
    public function getPatientAccountLinkedByIdPatient($idPatient): array
    {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $patient = $this->patientService->getPatientById($idPatient);
        $patientAccountLinked = $this->patientAccountLinkedRepository->findAccountLinkedByPatient($patient);
        return $patientAccountLinked;
    }

    /**
     * @param  int $idPatient
     * @param  int $idLinkedPatient
     * @throws Exception
     */
    public function removeLinkedBetweenTwoPatients($idPatient, $idLinkedPatient)
    {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $accountLinked = $this->patientAccountLinkedRepository->
        findAccountLinkedByTwoPatient($idPatient, $idLinkedPatient);
        if ($accountLinked == null) {
            throw new Exception("The two patients does not have any relation between them", 1);
        } else {
            $patient = $this->patientService->getPatientById($idPatient);
            $patientLinkedWith =
                $this->patientService->getPatientById($idLinkedPatient);
            $accountLinked = [];
            //check if the patient has accountLinked
            count($patient->getAccountLinked()) > 0 ? $accountLinked = $patient->getAccountLinked() :
            $accountLinked = $patientLinkedWith->getAccountLinked();

            if ($accountLinked !== null) {
                foreach ($accountLinked as $account) {
                    if ($account->getPatientLinked()->getId() == $idLinkedPatient) {
                        $patient->removeAccountLinked($account);
                        $this->patientService->savePatient($patient);
                    }
                    if ($account->getPatientLinked()->getId() == $idPatient) {
                        $patientLinkedWith->removeAccountLinked($account);
                        $this->patientService->savePatient($patientLinkedWith);
                    }
                }
            } else {
                throw new Exception("The two patients does not have any relation existing", 1);
            }
        }
    }
}
