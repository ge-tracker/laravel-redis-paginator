<?php

namespace GeTracker\LaravelRedisPaginator\Exceptions;

use Throwable;

class InvalidKeyException extends \InvalidArgumentException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct('A valid sorted set key must be specified', $code, $previous);
    }
}
