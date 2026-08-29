<?php

namespace App\Exceptions;

use App\Models\Consumable;
use Exception;
use Throwable;

class InsufficientStockException extends Exception
{
    /**
     * Create a new InsufficientStockException instance.
     */
    public function __construct(
        public readonly ?Consumable $consumable = null,
        public readonly int $requested = 0,
        public readonly int $available = 0,
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        if (empty($message)) {
            $name = $consumable ? $consumable->name : 'Consumable';
            $message = "Insufficient stock for '{$name}'. Requested: {$requested}, Available: {$available}.";
        }

        parent::__construct($message, $code, $previous);
    }
}
