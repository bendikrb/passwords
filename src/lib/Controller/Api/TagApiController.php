<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:45
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Helper\ApiObjects\TagObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class TagApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class TagApiController extends AbstractObjectApiController {

    /**
     * @var TagService
     */
    protected $modelService;

    /**
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * @var TagObjectHelper
     */
    protected $objectHelper;

    /**
     * TagApiController constructor.
     *
     * @param string             $appName
     * @param IRequest           $request
     * @param TagService         $modelService
     * @param TagRevisionService $revisionService
     * @param TagObjectHelper    $objectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        TagService $modelService,
        TagRevisionService $revisionService,
        TagObjectHelper $objectHelper
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $color
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     */
    public function create(
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        try {
            $folder   = $this->modelService->create();
            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $color, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(
                ['id' => $folder->getUuid(), 'revision' => $revision->getUuid()],
                Http::STATUS_CREATED
            );
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $label
     * @param string $color
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return JSONResponse
     * @TODO check is system trash
     *
     */
    public function update(
        string $id,
        string $label,
        string $color,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {

        try {
            $folder = $this->modelService->findByUuid($id);

            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $color, $cseType, $sseType, $hidden, false, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $revision->getUuid()]);
        } catch (\Throwable $e) {

            return $this->createErrorResponse($e);
        }
    }
}