<?php

namespace App\Controller;

use DateTime;
use Exception;
use Monolog\Logger;
use App\Entity\PatientRelation;
use App\Entity\PatientAccountLinked;
use App\Service\ActorService;
use App\Service\PatientService;
use Swagger\Annotations as SWG;
use App\Service\RelationService;
use App\Service\PatientRelationService;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Service\PatientAccountLinkedService;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use InnovHealth\ApiCommonBundle\Entity\Api\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use InnovHealth\ApiCommonBundle\Enum\RelationTypeEnum;
use InnovHealth\ApiCommonBundle\Service\LoggerService;
use InnovHealth\ApiCommonBundle\Entity\Error\ErrorType;
use InnovHealth\ApiCommonBundle\Exception\ApiException;
use InnovHealth\ApiCommonBundle\Message\NotifierMessage;
use InnovHealth\ApiCommonBundle\Service\Api\CardService;
use InnovHealth\ApiCommonBundle\Service\NotifierService;
use InnovHealth\ApiCommonBundle\Service\Api\FolderService;
use InnovHealth\ApiCommonBundle\Controller\AbstractController;
use InnovHealth\ApiCommonBundle\Service\Api\NotifierApiService;
use InnovHealth\ApiCommonBundle\Service\ParametersValidatorService;

/**
 * Class PatientAccountLinkedController
 * @package App\Controller
 */
class PatientAccountLinkedController extends AbstractController
{
    /**
     * @var LoggerService
     */
    private LoggerService $loggerService;

    /**
     * PatientController constructor.
     * @param LoggerService $loggerService
     */
    public function __construct(
        LoggerService $loggerService
    ) {
        $this->loggerService = $loggerService;
    }

    /**
     * @Route(name="get_account_linked", path="/account-linked/{idPatient}", methods={"GET"})
     * @Areas({"default"})
     * * @Operation(
     *     tags={"Patient's account linked"},
     *     produces={"application/json"},
     *     description="Get all existing Patient's account linked",
     *     summary="Get all existing Patient's account linked",
     *     @SWG\Parameter(
     *          name="idPatient",
     *          type= "integer",
     *          in= "path",
     *          required=true,
     *          description= "Unique id of patient"
     *     ),
     *      @SWG\Parameter(
     *          name="x-account-id",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Parameter(
     *          name="x-account-role",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_OK,
     *          description="List of group result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *                  "data"
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  @Model(type=PatientAccountLinked::class)
     *              ),
     *          )
     *      ),
     *     @SWG\Response(response=JsonResponse::HTTP_BAD_REQUEST,
     *          description="Bad request error",
     *          @SWG\Schema(
     *              ref="#/definitions/badResponse"
     *          )
     *      ),
     *      @SWG\Response(response=JsonResponse::HTTP_NOT_FOUND,
     *          description="Object not found",
     *          @SWG\Schema(
     *              ref="#/definitions/notFoundResponse"
     *          )
     *      ),
     *      @SWG\Response(
     *          response=JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
     *          description="Internal error",
     *          @SWG\Schema(
     *              ref="#/definitions/internalErrorResponse"
     *          )
     *      )
     * )
     * @param PatientAccountLinkedService $patientAccountLinkedService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function getPatientAccountLinked(
        int $idPatient,
        PatientAccountLinkedService $patientAccountLinkedService,
        CardService $cardService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $this->checkAdminDoctorPatient();
            $accountLinked = $patientAccountLinkedService->getPatientAccountLinkedByIdPatient($idPatient);
            $result = [];
            if ($accountLinked) {
                $hasRelation = false;
                if (!empty($accountLinked)) {
                    $hasRelation = true;
                }
                foreach ($accountLinked as $accLinked) {
                    $out = new \stdClass();
                    $headersCard['x-account-id'] = $accLinked->getParentPatient()->getAccountId();
                    $headersCard['x-account-role'] = "PATIENT";
                    $cardPatient = $cardService->getcardPatients(
                        $accLinked->getPatientLinked()->getId(),
                        $hasRelation,
                        $headersCard
                    );
                    $out->id = $accLinked->getId();
                    $out->relation = $accLinked->getRelation();
                    $out->patientParent = $accLinked->getParentPatient();
                    $out->patientLinked = $accLinked->getPatientLinked();
                    $out->cardPatientLinked = $cardPatient;
                    $out->hasBloodLink = $accLinked->getHasBloodLink();
                    array_push($result, $out);
                }
            }
            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_OK, $result);
        } catch (ApiException $e) {
            $e->setTraceId($loggerService->getLoggerId());
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            return $this->getJsonResponse($e->getHttpCode(), $e);
        } catch (Exception $e) {
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }

    /**
     * @Route(name="check_account_linked", path="/check-account-linked/{idPatient}", methods={"GET"})
     * @param PatientAccountLinkedService $patientAccountLinkedService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function checkPatientAccountLinked(
        int $idPatient,
        PatientAccountLinkedService $patientAccountLinkedService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $accountLinked = $patientAccountLinkedService->getPatientAccountLinkedByIdPatient($idPatient);
            $hasRelation            = false;
            $arrPatientLinkedId     = [];
            if ($accountLinked) {
                $hasRelation = true;
                foreach ($accountLinked as $accLnk) {
                    array_push($arrPatientLinkedId, $accLnk->getPatientLinked()->getId());
                }
            }
            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            $response = array(
                "hasRelation"       => $hasRelation,
                "patientLinkedId"   => $arrPatientLinkedId
            );
            return $this->getJsonResponse(JsonResponse::HTTP_OK, $response);
        } catch (ApiException $e) {
            $e->setTraceId($loggerService->getLoggerId());
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            return $this->getJsonResponse($e->getHttpCode(), $e);
        } catch (Exception $e) {
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }



    /**
     * @Route(name="remove_relation_link",
     * path="/account-linked/patients/{idPatient}/remove/{idPatientLinked}",
     * methods={"DELETE"})
     * @Areas({"default"})
     * * @Operation(
     *     tags={"Patient's account linked"},
     *     produces={"application/json"},
     *     description="Remove relation link between two patients",
     *     summary="Remove relation link between two patients",
     *     @SWG\Parameter(
     *          name="idPatient",
     *          type= "integer",
     *          in= "path",
     *          required=true,
     *          description= "Unique id of patient who wants remove the link."
     *     ),
     *     @SWG\Parameter(
     *          name="idPatientLinked",
     *          type= "integer",
     *          in= "path",
     *          required=true,
     *          description= "Unique id of patient linked."
     *     ),
     *     @SWG\Parameter(
     *          name="x-account-id",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Parameter(
     *          name="x-account-role",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_OK,
     *          description="List of group result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *                  "data"
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *          )
     *     ),
     *     @SWG\Response(response=JsonResponse::HTTP_BAD_REQUEST,
     *          description="Bad request error",
     *          @SWG\Schema(
     *              ref="#/definitions/badResponse"
     *          )
     *      ),
     *      @SWG\Response(response=JsonResponse::HTTP_NOT_FOUND,
     *          description="Object not found",
     *          @SWG\Schema(
     *              ref="#/definitions/notFoundResponse"
     *          )
     *      ),
     *      @SWG\Response(
     *          response=JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
     *          description="Internal error",
     *          @SWG\Schema(
     *              ref="#/definitions/internalErrorResponse"
     *          )
     *      )
     * )
     * @param int $idPatient
     * @param int $idPatientLinked
     * @param PatientService $patientService
     * @param PatientAccountLinkedService $patientAccountLinkedService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function removeRelationLink(
        int $idPatient,
        int $idPatientLinked,
        PatientAccountLinkedService $patientAccountLinkedService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $this->checkAdminPatient();
            $this->checkIdAccess($idPatient);
            $patientAccountLinkedService->removeLinkedBetweenTwoPatients($idPatient, $idPatientLinked);
            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_OK);
        } catch (ApiException $e) {
            $e->setTraceId($loggerService->getLoggerId());
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
        }
    }

    /**
     * @Route(name="create_relation_link",
     * path="/account-linked",
     * methods={"POST"})
     * @Areas({"default"})
     * * @Operation(
     *     tags={"Patient's account linked"},
     *     produces={"application/json"},
     *     description="Create relation link between two patients",
     *     summary="Create relation link between two patients",
     *     @SWG\Parameter(
     *          name="x-account-id",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Parameter(
     *          name="x-account-role",
     *          in="header",
     *          required=true,
     *          type="string",
     *          description="Authorization"
     *     ),
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Create realtion link data request",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "idPatient",
     *                  "idPatientLinked",
     *                  "patientRelation"
     *              },
     *              @SWG\Property(
     *                  property="idPatient",
     *                  type= "string",
     *                  description= "Unique id of patient who wants create the link."
     *              ),
     *              @SWG\Property(
     *                  property="idPatientLinked",
     *                  type= "string",
     *                  description= "Unique id of patient linked."
     *              ),
     *              @SWG\Property(
     *                  property="patientRelation",
     *                  type= "string",
     *                  description= "the relation link with the patient.
     *                  {1 = PARENT, 2 = CHILD, 3 = CONJOINT, 4 = SPOUSE/HUSBAND,
     *                  5 = GD PARENT, 6 = AUNT/UNCLE, 7 = COUSIN, 8 = TUTORSHIP, 9 = BROTHER/SISTER}"
     *              ),
     *              @SWG\Property(
     *                  property="hasBloodLink",
     *                  type= "boolean",
     *                  description= "Does the child patient (this new patient) has blood link with the parent patient?"
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_OK,
     *          description="List of group result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *                  "data"
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *          )
     *      ),
     *     @SWG\Response(response=JsonResponse::HTTP_BAD_REQUEST,
     *          description="Bad request error",
     *          @SWG\Schema(
     *              ref="#/definitions/badResponse"
     *          )
     *      ),
     *      @SWG\Response(response=JsonResponse::HTTP_NOT_FOUND,
     *          description="Object not found",
     *          @SWG\Schema(
     *              ref="#/definitions/notFoundResponse"
     *          )
     *      ),
     *      @SWG\Response(
     *          response=JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
     *          description="Internal error",
     *          @SWG\Schema(
     *              ref="#/definitions/internalErrorResponse"
     *          )
     *      )
     * )
     * @param int $idPatient
     * @param int $idPatientLinked
     * @param PatientService $patientService
     * @param PatientAccountLinkedService $patientAccountLinkedService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function createRelationLink(
        Request $request,
        PatientService $patientService,
        PatientRelationService $patientRelationService,
        RelationService $relationService,
        ActorService $actorService,
        LoggerService $loggerService,
        ParametersValidatorService $parametersValidatorService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $this->checkAdminPatient();
            $data = json_decode($request->getContent(), true);
            $parametersValidatorService->checkData(
                $data,
                [
                    'idPatient',
                    'idPatientLinked',
                    'patientRelation',
                    'hasBloodLink'
                ],
                [
                    'idPatient' => $parametersValidatorService::STRING,
                    'idPatientLinked' => $parametersValidatorService::STRING,
                    'patientRelation' => $parametersValidatorService::STRING,
                    'hasBloodLink' => $parametersValidatorService::BOOLEAN
                ]
            );

            $patient = $actorService->getActorByAccountId($data['idPatient']);
            $patientParent = $actorService->getActorByAccountId($data['idPatientLinked']);
            $patientRelation = $patientRelationService->getPatientRelationByName($data['patientRelation']);

            $patient = $patientService->getPatientById($patient->getId());
            $patientParent = $patientService->getPatientById($patientParent->getId());

            $patientService->addRelationMember(
                $patient,
                $patientParent,
                $patientRelation,
                (key_exists('hasBloodLink', $data) && $data['hasBloodLink'] !== null) ? $data['hasBloodLink'] : true
            );

            $relationService->createRelation(
                $patientParent->getId(),
                new DateTime(),
                $patient->getId(),
                null
            );

            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_OK);
        } catch (ApiException $e) {
            $e->setTraceId($loggerService->getLoggerId());
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            return $this->getJsonResponse($e->getHttpCode(), $e);
        } catch (Exception $e) {
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }
}
