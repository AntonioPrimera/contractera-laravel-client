<?php

namespace AntonioPrimera\ContracteraLaravelClient\Exceptions;

use RuntimeException;

class MissingApplicationToken extends RuntimeException
{
    public static function make(): self
    {
        return new self('CONTRACTERA_APPLICATION_TOKEN is not configured.');
    }
}
