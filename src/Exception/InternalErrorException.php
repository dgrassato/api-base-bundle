<?php

namespace Templum\PrmApi\V1Bundle\Exception;

use BaseBundle\Exception\ApiProblem;
use BaseBundle\Exception\ApiProblemException;
use Exception;
use Psr\Log\LogLevel;

/**
 * Class InternalErrorException.
 */
class InternalErrorException extends ApiProblemException
{
    public function __construct(ApiProblem $apiProblem, Exception $previous = null, array $headers = array(), $code = 0, $level = LogLevel::CRITICAL)
    {
        parent::__construct($apiProblem, $previous, $headers, $code, $level);
    }
}
