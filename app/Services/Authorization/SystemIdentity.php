<?php

namespace App\Services\Authorization;

use App\Contracts\OrganizationalServiceIdentity;
use App\Enums\ServiceExecutionChannel;
use InvalidArgumentException;

final readonly class SystemIdentity implements OrganizationalServiceIdentity
{
    /**
     * @param  array<int, int>  $organizationalUnitIds
     */
    private function __construct(
        private ServiceExecutionChannel $executionChannel,
        private string $executionPurpose,
        private array $unitIds,
        private bool $organizationWide,
    ) {}

    public static function scoped(
        ServiceExecutionChannel $channel,
        string $purpose,
        int ...$organizationalUnitIds
    ): self {
        $purpose = trim($purpose);
        $unitIds = array_values(array_unique(array_filter(
            $organizationalUnitIds,
            fn (int $id): bool => $id > 0
        )));

        if ($purpose === '' || $unitIds === []) {
            throw new InvalidArgumentException('A scoped service identity requires a purpose and at least one organizational unit.');
        }

        return new self($channel, $purpose, $unitIds, false);
    }

    public static function organizationWide(
        ServiceExecutionChannel $channel,
        string $purpose
    ): self {
        $purpose = trim($purpose);

        if ($purpose === '') {
            throw new InvalidArgumentException('An organization-wide service identity requires an auditable purpose.');
        }

        return new self($channel, $purpose, [], true);
    }

    public function channel(): ServiceExecutionChannel
    {
        return $this->executionChannel;
    }

    public function purpose(): string
    {
        return $this->executionPurpose;
    }

    public function organizationalUnitIds(): array
    {
        return $this->unitIds;
    }

    public function isOrganizationWide(): bool
    {
        return $this->organizationWide;
    }
}
