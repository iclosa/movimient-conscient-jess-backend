<?php

namespace App\Exceptions;

use RuntimeException;

class BookingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'booking_error',
        public readonly int $httpStatus = 422,
    ) {
        parent::__construct($message);
    }
}
