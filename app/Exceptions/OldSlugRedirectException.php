<?php

namespace App\Exceptions;

use App\Models\Organization;
use RuntimeException;

class OldSlugRedirectException extends RuntimeException
{
    public function __construct(public readonly Organization $organization)
    {
        parent::__construct('Organization slug has changed.');
    }
}
