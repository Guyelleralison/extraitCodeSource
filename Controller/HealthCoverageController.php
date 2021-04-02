<?php

namespace App\Controller;

use App\Service\HealthCoverageService;
use App\Entity\HealthCoverage;
use App\Entity\Patient;
use App\Service\PatientService;
use Exception;
use InnovHealth\ApiCommonBundle\Controller\AbstractController;
use InnovHealth\ApiCommonBundle\Entity\Error\ErrorType;
use InnovHealth\ApiCommonBundle\Exception\ApiException;
use InnovHealth\ApiCommonBundle\Service\LoggerService;
use InnovHealth\ApiCommonBundle\Service\ParametersValidatorService;
use Monolog\Logger;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use DateTime;
use InnovHealth\ApiCommonBundle\Enum\HealthCoverageTypeEnum;

/**
 * Class HealthCoverageController
 * @package App\Controller
 */
class HealthCoverageController extends AbstractController
{
    /**
     * @var LoggerService
     */
    private LoggerService $loggerService;

    /**
     * AdminController constructor.
     * @param LoggerService $loggerService
     */
    public function __construct(
        LoggerService $loggerService
    ) {
        $this->loggerService = $loggerService;
    }

    /**
     * @Route(name="get_health_coverages", path="/health-coverages/patient/{patientId}", methods={"GET"})
     * @Areas({"default"})
     * * @Operation(
     *     tags={"Health-Coverage"},
     *     produces={"application/json"},
     *     description="Get all health coverages",
     *     summary="Get all health coverages",
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
     *          name="patientId",
     *          type= "integer",
     *          in= "path",
     *          required=true,
     *          description= "Id of patient who has health coverage."
     *      ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_CREATED,
     *          description="List of health coverage result",
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
     *                  @Model(type=HealthCoverage::class)
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
     * @param int $patientId
     * @param HealthCoverageService $healthCoverageService
     * @param PatientService $patientService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function getHealthCoverages(
        int $patientId,
        HealthCoverageService $healthCoverageService,
        PatientService $patientService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $this->checkAdminPatient();
                $this->checkIdAccess($patientId);
                $patient = $patientService->getPatientById($patientId);
                $healthCoverages = $healthCoverageService->getHealthCoverages($patient);

            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_OK, $healthCoverages);
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
     * @Route(name="post_health_coverage", path="/health-coverage/patient/{patientId}", methods={"POST"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Coverage"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Add a new health coverage to a patient as mutual or supplemental.",
     *     summary="Post a new health coverage.",
     *      @SWG\Parameter(
     *          name="patientId",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          description="Patient id."
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
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Create data request. The name and the type are required.",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "name",
     *                  "type"
     *              },
     *              @SWG\Property(
     *                  property="name",
     *                  type= "string",
     *                  description= "The name of the health coverage."
     *              ),
     *              @SWG\Property(
     *                  property="identity_number",
     *                  type= "string",
     *                  description= "The identity number of the  health coverage."
     *              ),
     *              @SWG\Property(
     *                  property="type",
     *                  type= "integer",
     *                  description= "Health coverage type. Put 0 for MUTUAL or 1 for SUPPLEMENTAL.",
     *                  enum={0, 1}
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_CREATED,
     *          description="New admin result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  @Model(type=HealthCoverage::class, groups={"public"})
     *              ),
     *          )
     *      )
     * )
     * @param Request $request
     * @param HealthCoverageService $healthCoverageService
     * @param LoggerService $loggerService
     * @return JsonResponse
     * @throws Exception
     */
    public function createHealthCoverage(
        Request $request,
        int $patientId,
        HealthCoverageService $healthCoverageService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $response = [];
        try {
            $this->checkAdminPatient();
            $data = json_decode($request->getContent(), true);
            $this->checkIdAccess($patientId);
            $healthCoverage = $healthCoverageService->createHealthCoverage(
                $patientId,
                HealthCoverageTypeEnum::GET_TYPE[$data['type']],
                ($data !== null && key_exists('name', $data)) ? $data['name'] : null,
                ($data !== null && key_exists('identity_number', $data)) ? $data['identity_number'] : null
            );

            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_CREATED, $healthCoverage);
        } catch (ApiException $e) {
            $e->setTraceId($loggerService->getLoggerId());
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            $out = new ApiException($e->getMessage(), JsonResponse::HTTP_IM_USED, $e->getParams());
            return $this->getJsonResponse($out->getHttpCode(), $out, $response);
        } catch (Exception $e) {
            $loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }

    /**
     * @Route(name="patch_health_coverage", path="/health-coverage/{id}/patient/{patientId}", methods={"PATCH"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Coverage"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Update an health professional linked with a patient.",
     *     summary="Update an health professional.",
     *      @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          description="Health coverage Id."
     *     ),
     *      @SWG\Parameter(
     *          name="patientId",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          description="Patient Id."
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
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Update data request",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *              },
     *              @SWG\Property(
     *                  property="name",
     *                  type= "string",
     *                  description= "Health coverage name."
     *              ),
     *              @SWG\Property(
     *                  property="identity_number",
     *                  type= "string",
     *                  description= "Health coverage identity number."
     *              ),
     *              @SWG\Property(
     *                  property="type",
     *                  type= "integer",
     *                  description= "Health coverage type. Put 0 for MUTUAL or 1 for SUPPLEMENTAL.",
     *                  enum={0, 1}
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_OK,
     *          description="HealthCoverage result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  @Model(type=HealthCoverage::class, groups={"public"})
     *              ),
     *          ),
     *     @SWG\Response(response=JsonResponse::HTTP_BAD_REQUEST,
     *          description="Bad request error",
     *          @SWG\Schema(
     *                  ref="#/definitions/badResponse"
     *          )
     *      ),
     *      @SWG\Response(response=JsonResponse::HTTP_NOT_FOUND,
     *          description="Object not found",
     *          @SWG\Schema(
     *                  ref="#/definitions/notFoundResponse"
     *          )
     *      ),
     *      @SWG\Response(
     *          response=JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
     *          description="Internal error",
     *          @SWG\Schema(
     *                  ref="#/definitions/internalErrorResponse"
     *          )
     *      )
     * )
     * )
     * @param int $id
     * @param int $patientId
     * @param Request $request
     * @param HealthCoverageService $healthCoverageService
     * @return JsonResponse
     */
    public function updateHealthProfessional(
        int $id,
        int $patientId,
        Request $request,
        HealthCoverageService $healthCoverageService
    ): JsonResponse {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $this->checkAdminPatient();
        try {
            $data = json_decode($request->getContent(), true);
            $this->checkIdAccess($patientId);
            $healthCoverage = $healthCoverageService->updateHealthCoverage(
                $id,
                $patientId,
                ($data !== null && key_exists('type', $data)) ? HealthCoverageTypeEnum::GET_TYPE[$data['type']] : null,
                ($data !== null && key_exists('name', $data)) ? $data['name'] : null,
                ($data !== null && key_exists('identity_number', $data)) ?
                    $data['identity_number'] : null
            );
            $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_CREATED, $healthCoverage);
        } catch (ApiException $e) {
            $e->setTraceId($this->loggerService->getLoggerId());
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            return $this->getJsonResponse($e->getHttpCode(), $e);
        } catch (Exception $e) {
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }

    /**
     * @Route(name="delete_health_coverage", path="/health-coverage/{id}/patient/{patientId}",  methods={"DELETE"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Coverage"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Delete an health coverage linked with a patient.",
     *     summary="Delete an health coverage.",
     *     @SWG\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          description="Health coverage id to delete."
     *     ),
     *      @SWG\Parameter(
     *          name="patientId",
     *          in="path",
     *          required=true,
     *          type="integer",
     *          description="Patient id linked with the Health coverage to delete."
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
     *          description="Health Coverage result",
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "success",
     *              },
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean",
     *                  description="boolean of request success",
     *                  example={"true"}
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  @Model(type=HealthCoverage::class, groups={"public"})
     *              ),
     *          ),
     *     @SWG\Response(response=JsonResponse::HTTP_BAD_REQUEST,
     *          description="Bad request error",
     *          @SWG\Schema(
     *                  ref="#/definitions/badResponse"
     *          )
     *      ),
     *      @SWG\Response(response=JsonResponse::HTTP_NOT_FOUND,
     *          description="Object not found",
     *          @SWG\Schema(
     *                  ref="#/definitions/notFoundResponse"
     *          )
     *      ),
     *      @SWG\Response(
     *          response=JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
     *          description="Internal error",
     *          @SWG\Schema(
     *                  ref="#/definitions/internalErrorResponse"
     *          )
     *      )
     * )
     * )
     * @param $id
     * @param $patientId
     * @param Request $request
     * @param HealthCoverageService $healthCoverageService
     * @param PatientService $patientService
     * @return JsonResponse
     */
    public function deleteHealthCoverage(
        Request $request,
        int $id,
        int $patientId,
        HealthCoverageService $healthCoverageService,
        PatientService $patientService
    ): JsonResponse {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $this->checkAdminPatient();
        try {
            $this->checkIdAccess($patientId);
            $patient = $patientService->getPatientById($request->get('patientId'));
            $healthCoverage = $healthCoverageService->getHealthCoverageById($id);
            $healthCoverageDelete = false;
            foreach ($patient->getHealthCoverages() as $healthCo) {
                if ($healthCo->getId() == $healthCoverage->getId()) {
                    $healthCoverageDelete = $healthCoverageService->deleteHealthCoverage(
                        $id,
                        $request->get('patientId')
                    );
                }
            }
            $result = [];
            if ($healthCoverageDelete) {
                array_push($result, "Health Professional with id " . $id . " is deleted");
            } else {
                array_push($result, "Health Professional with id " . $id . " cannot be deleted.");
            }
            $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_CREATED, $result);
        } catch (ApiException $e) {
            $e->setTraceId($this->loggerService->getLoggerId());
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getLogMessage(), Logger::ERROR);
            return $this->getJsonResponse($e->getHttpCode(), $e);
        } catch (Exception $e) {
            $this->loggerService->log(__CLASS__, __FUNCTION__, $e->getMessage(), Logger::ERROR);
            $out = new ApiException(ErrorType::INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            return $this->getJsonResponse($out->getHttpCode(), $out);
        }
    }
}
