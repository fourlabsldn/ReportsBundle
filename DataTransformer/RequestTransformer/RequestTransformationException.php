<?php

namespace FL\ReportsBundle\DataTransformer\RequestTransformer;

class RequestTransformationException extends \InvalidArgumentException
{
    /**
     * @var int
     */
    protected $httpErrorCode;

    /**
     * @param string          $message
     * @param int             $httpErrorCode
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message, int $httpErrorCode, int $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->httpErrorCode = $httpErrorCode;
    }

    /**
     * @return int
     */
    public function getHttpErrorCode()
    {
        return $this->httpErrorCode;
    }
}
