<?php

namespace App\Contracts;

use App\Enums\ServiceExecutionChannel;

/**
 * Explicit organizational identity for work without an authenticated user.
 *
 * Console commands, queued jobs, service API clients, and imports must carry
 * one of these identities instead of relying on Auth or silently bypassing
 * organizational visibility.
 */
interface OrganizationalServiceIdentity
{
    public function channel(): ServiceExecutionChannel;

    public function purpose(): string;

    /** @return array<int, int> */
    public function organizationalUnitIds(): array;

    public function isOrganizationWide(): bool;
}
