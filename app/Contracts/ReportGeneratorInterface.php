<?php

namespace App\Contracts;

use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\User;

interface ReportGeneratorInterface
{
    /**
     * Generate snapshot data array for the specified report definition and organizational scope.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array;
}
