<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Controller\PageController;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\ILogger;
use OCP\IRequest;

/**
 * Class ApiSecurityMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSecurityMiddleware extends Middleware {

    /**
     * @var ILogger
     */
    protected $logger;

    /**
     * @var IRequest
     */
    protected $request;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ApiSecurityMiddleware constructor.
     *
     * @param ILogger              $logger
     * @param ConfigurationService $config
     * @param IRequest             $request
     */
    public function __construct(ILogger $logger, ConfigurationService $config, IRequest $request) {
        $this->logger = $logger;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @param \OCP\AppFramework\Controller $controller
     * @param string                       $methodName
     *
     * @throws ApiException
     */
    public function beforeController($controller, $methodName): void {

        if(get_class($controller) !== PageController::class && $this->request->getServerProtocol() !== 'https') {
            throw new ApiException('HTTPS required', 400);
        }

        parent::beforeController($controller, $methodName);
    }

    /**
     * @param \OCP\AppFramework\Controller $controller
     * @param string                       $methodName
     * @param \Exception                   $exception
     *
     * @return null|JSONResponse
     */
    public function afterException($controller, $methodName, \Exception $exception): ?JSONResponse {
        if(substr(get_class($controller), 0, 28) !== 'OCA\Passwords\Controller\Api') {
            return null;
        }

        $message    = 'Unable to complete request';
        $id         = 0;
        $statusCode = Http::STATUS_SERVICE_UNAVAILABLE;

        $this->logger->logException($exception, ['app' => Application::APP_NAME]);

        if(get_class($exception) === ApiException::class || is_subclass_of($exception, ApiException::class)) {
            /** @var ApiException $exception */
            $id         = $exception->getId();
            $message    = $exception->getMessage();
            $statusCode = $exception->getHttpCode();
        }

        if(get_class($exception) === DoesNotExistException::class) {
            $id         = 404;
            $message    = 'Resource not found';
            $statusCode = 404;
        }

        $response = new JSONResponse(
            [
                'status'  => 'error',
                'id'      => $id,
                'message' => $message
            ], $statusCode
        );

        return $response;
    }
}