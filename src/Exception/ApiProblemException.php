<?php

namespace BaseBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LogLevel;

/**
 * Class ApiProblemException.
 */
class ApiProblemException extends HttpException
{
    private $apiProblem;

    public function __construct(ApiProblem $apiProblem, \Exception $previous = null, array $headers = array(), $code = 0, $level = LogLevel::ERROR)
    {
        $this->apiProblem = $apiProblem;
        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();
        $apiProblem->setLevel($level);

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getApiProblem()
    {
        return $this->apiProblem;
    }

    /***
     * Return exception default ApiProblem type
     * @param        $title
     * @param int    $errorType
     * @param LogLevel $level
     * @param array  $extra
     */
    public static function raise($title, $errorType = 'invalid_body_format', $level = LogLevel::ERROR, $extra = [])
    {
        $problem = new ApiProblem(404, $errorType, $level);
        $problem->setTitle($title);
        $problem->setLevel($level);
        $problem->setType($errorType);

        if (count($extra) > 0) {
            $problem->setExtra($extra);
        }

        throw new self($problem);
    }
}
