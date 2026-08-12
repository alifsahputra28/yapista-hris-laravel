<?php

namespace App\Support\Imports;

use RuntimeException;

class EmployeeImportRowException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $skipped = false)
    {
        parent::__construct($message);
    }
}
