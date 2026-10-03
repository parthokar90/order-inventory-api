<?php

namespace App\Exceptions;

use App\Models\Order;
use Exception;

class DuplicateOrderException extends Exception
{
    public function __construct(public readonly Order $existingOrder)
    {
        parent::__construct('Duplicate order request.', 409);
    }
}