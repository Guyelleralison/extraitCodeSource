<?php

namespace App\Controller;

use App\Service\HealthProfessionalService;
use App\Entity\HealthProfessional;
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

/**
 * Class HealthProfessionalController
 * @package App\Controller
 */
class HealthProfessionalController extends AbstractController
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
     * @Route(name="get_health_professionals", path="/health-professional-contacts", methods={"GET"})
     * @Areas({"default"})
     * * @Operation(
     *     tags={"Health-Professional-Contact"},
     *     produces={"application/json"},
     *     description="Get all health professionals.
     * The admin can fetch all health professionals contact by patient or all.
     * The patient can fetch his own professionnals contact only.",
     *     summary="Get all health professionals",
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
     *          in= "query",
     *          required=false,
     *          description= "Id of patient who have health professional.
     * Not required if you are admin and want to get all the health professionals."
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_CREATED,
     *          description="List of health professional result",
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
     *                  @Model(type=HealthProfessional::class)
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
     * @param HealthProfessionalService $healthProfessionalService
     * @param PatientService $patientService
     * @param LoggerService $loggerService
     * @return JsonResponse
     */
    public function getHealthProfessionals(
        Request $request,
        HealthProfessionalService $healthProfessionalService,
        PatientService $patientService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        try {
            $this->checkAdminPatient();
            $healthProfessionals = [];
            if ($request->get('patientId') && $request->get('patientId') !== null) {
                $this->checkIdAccess($request->get('patientId'));
                $patient = $patientService->getPatientById($request->get('patientId'));
                $healthProfessionals = $healthProfessionalService->getHealthProfessionalByPatient($patient);
            } else {
                $this->checkAdmin();
                $healthProfessionals = $healthProfessionalService->getHealthProfessionals();
            }

            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_OK, $healthProfessionals);
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
     * @Route(name="post_health_professional", path="/health-professional-contact", methods={"POST"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Professional-Contact"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Post a new health professional.
     * It could be set with the id of the doctor registered on passcare
     * OR the id of the professionnal on the professional database
     * OR the id of the google place Id.
     * If one of those id is set, the rest of the fields are set automatically
     * (name, phone numbers if exists).
     * ALL PARAMS is OPTIONNAL excepts the patientId which is REQUIRED.",
     *     summary="Post a new health professional.",
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
     *          description="Create data request",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              required={
     *                  "patientId"
     *              },
     *              @SWG\Property(
     *                  property="patientId",
     *                  type= "integer",
     *                  description= "Patient id who add his professional. is REQUIRED."
     *              ),
     *              @SWG\Property(
     *                  property="doctorId",
     *                  type= "integer",
     *                  description= "Doctor id registered on passcare.
     * Set to null or 0 if it is not a doctor registered on passcare or remove the param."
     *              ),
     *              @SWG\Property(
     *                  property="franceHealthProfessionalId",
     *                  type= "integer",
     *                  description= "id registered on profesionnal database on api referential.
     * Set to null or 0 if it is not a professional registered on pro database or remove this param."
     *              ),
     *              @SWG\Property(
     *                  property="speciality",
     *                  type= "string",
     *                  description= "Speciality of the health pro. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="googlePlaceId",
     *                  type= "string",
     *                  description= "Professional google place id. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="name",
     *                  type= "string",
     *                  description= "Professional name. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="phoneNumber1",
     *                  type= "string",
     *                  description= "Professional phone number 1. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="phoneLabel1",
     *                  type= "string",
     *                  description= "Professional label phone number 1. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="phoneNumber2",
     *                  type= "string",
     *                  description= "Professional phone number 2. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="phoneLabel2",
     *                  type= "string",
     *                  description= "Professional label phone number 2. Can be set to null or blank."
     *              ),
     *              @SWG\Property(
     *                  property="comment",
     *                  type= "string",
     *                  description= "Comment. Can be set to null or blank."
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
     *                  @Model(type=HealthProfessional::class, groups={"public"})
     *              ),
     *          )
     *      )
     * )
     * @param Request $request
     * @param HealthProfessionalService $healthProfessionalService
     * @param LoggerService $loggerService
     * @return JsonResponse
     * @throws Exception
     */
    public function createHealthProfessional(
        Request $request,
        HealthProfessionalService $healthProfessionalService,
        LoggerService $loggerService
    ): JsonResponse {
        $loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $response = [];
        try {
            $this->checkAdminPatient();
            $data = json_decode($request->getContent(), true);
            $this->checkIdAccess($data['patientId']);
            $healthProfessional = $healthProfessionalService->createHealthProfessional(
                $data['patientId'],
                ($data !== null && key_exists('doctorId', $data)) ? $data['doctorId'] : null,
                ($data !== null && key_exists('speciality', $data)) ? $data['speciality'] : null,
                ($data !== null && key_exists('franceHealthProfessionalId', $data)) ?
                $data['franceHealthProfessionalId'] : null,
                ($data !== null && key_exists('name', $data)) ? $data['name'] : null,
                ($data !== null && key_exists('phoneNumber1', $data)) ? $data['phoneNumber1'] : null,
                ($data !== null && key_exists('phoneLabel1', $data)) ? $data['phoneLabel1'] : null,
                ($data !== null && key_exists('phoneNumber2', $data)) ? $data['phoneNumber2'] : null,
                ($data !== null && key_exists('phoneLabel2', $data)) ? $data['phoneLabel2'] : null,
                ($data !== null && key_exists('googlePlaceId', $data)) ? $data['googlePlaceId'] : null,
                ($data !== null && key_exists('comment', $data)) ? $data['comment'] : null
            );

            $loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_CREATED, $healthProfessional);
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
     * @Route(name="patch_health_professional", path="/health-professional-contact/{id}", methods={"PATCH"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Professional-Contact"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Update an health professional by setting only the params to update.",
     *     summary="Update an health professional.",
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
     *                   "patientId"
     *              },
     *              @SWG\Property(
     *                  property="patientId",
     *                  type= "integer",
     *                  description= "Patient id who add his professional. REQUIRED"
     *              ),
     *              @SWG\Property(
     *                  property="doctorId",
     *                  type= "integer",
     *                  description= "Doctor id registered on passcare. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="franceHealthProfessionalId",
     *                  type= "integer",
     *                  description= "id registred on profesionnal database. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="speciality",
     *                  type= "string",
     *                  description= "Speciality of the health pro. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="googlePlaceId",
     *                  type= "string",
     *                  description= "Professional google place id. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="name",
     *                  type= "string",
     *                  description= "Professional name. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="phoneNumber1",
     *                  type= "string",
     *                  description= "Professional phone number 1. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="phoneLabel1",
     *                  type= "string",
     *                  description= "Professional label phone number 1. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="phoneNumber2",
     *                  type= "string",
     *                  description= "Professional phone number 2. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="phoneLabel2",
     *                  type= "string",
     *                  description= "Professional label phone number 2. OPTIONNAL. Remove this param if not set."
     *              ),
     *              @SWG\Property(
     *                  property="comment",
     *                  type= "string",
     *                  description= "Comment. OPTIONNAL. Remove this param if not set."
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *          response=JsonResponse::HTTP_OK,
     *          description="HealthProfessional result",
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
     *                  @Model(type=HealthProfessional::class, groups={"public"})
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
     * @param Request $request
     * @param HealthProfessionalService $healthProfessionalService
     * @return JsonResponse
     */
    public function updateHealthProfessional(
        int $id,
        Request $request,
        HealthProfessionalService $healthProfessionalService
    ): JsonResponse {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $this->checkAdminPatient();
        try {
            $data = json_decode($request->getContent(), true);
            $this->checkIdAccess($data['patientId']);
            $healthProfessional = $healthProfessionalService->updateHealthProfessional(
                $id,
                $data['patientId'],
                ($data !== null && key_exists('doctorId', $data)) ? $data['doctorId'] : null,
                ($data !== null && key_exists('specialityId', $data)) ?
                $data['specialityId'] : null,
                ($data !== null && key_exists('franceHealthProfessionalId', $data)) ?
                $data['franceHealthProfessionalId'] : null,
                ($data !== null && key_exists('name', $data)) ? $data['name'] : null,
                ($data !== null && key_exists('phoneNumber1', $data)) ? $data['phoneNumber1'] : null,
                ($data !== null && key_exists('phoneLabel1', $data)) ? $data['phoneLabel1'] : null,
                ($data !== null && key_exists('phoneNumber2', $data)) ? $data['phoneNumber2'] : null,
                ($data !== null && key_exists('phoneLabel2', $data)) ? $data['phoneLabel2'] : null,
                ($data !== null && key_exists('googlePlaceId', $data)) ? $data['googlePlaceId'] : null,
                ($data !== null && key_exists('comment', $data)) ? $data['comment'] : null
            );
            $this->loggerService->log(__CLASS__, __FUNCTION__, 'End');
            return $this->getJsonResponse(JsonResponse::HTTP_CREATED, $healthProfessional);
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
     * @Route(name="delete_health_professional", path="/health-professional-contact/{id}",  methods={"DELETE"})
     * @Areas({"default"})
     * @Operation(
     *     tags={"Health-Professional-Contact"},
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     description="Delete an health professional.",
     *     summary="Delete an health professional.",
     *     @SWG\Parameter(
     *          name="patientId",
     *          in="query",
     *          required=true,
     *          type="integer",
     *          description="Id patient who want delete his pro. REQUIRED."
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
     *          description="Health Professional result",
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
     *                  @Model(type=HealthProfessional::class, groups={"public"})
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
     * @param Request $request
     * @param HealthProfessionalService $healthProfessionalService
     * @param PatientService $patientService
     * @return JsonResponse
     */
    public function deleteHealthProfessional(
        Request $request,
        int $id,
        HealthProfessionalService $healthProfessionalService,
        PatientService $patientService
    ): JsonResponse {
        $this->loggerService->log(__CLASS__, __FUNCTION__, 'Begin');
        $this->checkAdminPatient();
        try {
            $this->checkIdAccess($request->get('patientId'));
            $patient = $patientService->getPatientById($request->get('patientId'));
            $healthProfessional = $healthProfessionalService->getHealthProfessionalById($id);
            $healthProfessionalDelete = false;
            foreach ($patient->getHealthProfessionals() as $healthPro) {
                if ($healthPro->getId() == $healthProfessional->getId()) {
                    $healthProfessionalDelete = $healthProfessionalService->deleteHealthProfessional(
                        $id,
                        $request->get('patientId')
                    );
                }
            }
            $this->loggerService->log(__CLASS__, __FUNCTION__, 'DELETEE : ' . $healthProfessionalDelete);
            $result = [];
            if ($healthProfessionalDelete) {
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
