<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\EpfoOfficeDirectorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EpfoOfficeDirectorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_complete_non_guest_house_directory_and_office_managers(): void
    {
        $this->seed(EpfoOfficeDirectorySeeder::class);
        $firstCounts = [
            OrganizationalUnit::count(),
            Site::count(),
            Location::count(),
            User::count(),
        ];

        $this->seed(EpfoOfficeDirectorySeeder::class);

        $this->assertSame($firstCounts, [
            OrganizationalUnit::count(),
            Site::count(),
            Location::count(),
            User::count(),
        ]);

        $directoryUnits = OrganizationalUnit::query()->get()
            ->filter(fn (OrganizationalUnit $unit): bool => data_get($unit->metadata, 'directory_source') === 'pf-contacts');

        $this->assertCount(304, $directoryUnits);
        $this->assertSame([
            'district_office' => 118,
            'head_office' => 1,
            'holiday_home' => 1,
            'internal_audit_wing' => 1,
            'national_data_centre' => 1,
            'pdunass_natrss' => 1,
            'regional_office' => 153,
            'special_state_office' => 1,
            'sub_zonal_training_institute' => 1,
            'vigilance_wing' => 1,
            'zonal_office' => 21,
            'zonal_training_institute' => 4,
        ], $directoryUnits->countBy(fn (OrganizationalUnit $unit): string => $unit->metadata['directory_category'])->sortKeys()->all());

        $this->assertSame(304, User::query()->where('role', UserRole::INVENTORY_MANAGER->value)->count());
        $this->assertSame(304, DB::table('organizational_unit_user')->count());
        $this->assertSame(304, $directoryUnits->filter(fn (OrganizationalUnit $unit): bool => $unit->sites()->exists())->count());
        $this->assertSame(304, Location::query()->where('code', 'like', '%-OFFICE')->count());

        $amravati = User::query()->where('email', 'do.amravati@epfindia.gov.in')->firstOrFail();
        $barbil = User::query()->where('email', 'do.barbil@epfindia.gov.in')->firstOrFail();
        $this->assertTrue(Hash::check('password', $amravati->password));
        $this->assertTrue(Hash::check('password', $barbil->password));

        $regionalManagers = User::query()
            ->whereHas('organizationalUnits', fn ($query) => $query->where('unit_type', OrganizationalUnitType::REGIONAL_OFFICE->value))
            ->pluck('email');
        $this->assertCount(153, $regionalManagers);
        $this->assertTrue($regionalManagers->every(fn (string $email): bool => str_starts_with($email, 'ro.')));

        $districtManagers = User::query()
            ->whereHas('organizationalUnits', fn ($query) => $query->where('unit_type', OrganizationalUnitType::DISTRICT_OFFICE->value))
            ->pluck('email');
        $this->assertCount(118, $districtManagers);
        $this->assertTrue($districtManagers->every(fn (string $email): bool => str_starts_with($email, 'do.')));

        $gangtok = OrganizationalUnit::query()->where('name', 'SSO - GANGTOK')->firstOrFail();
        $this->assertSame('SILIGURI', $gangtok->parent->name);
        $this->assertSame(OrganizationalUnitType::SPECIAL_STATE_OFFICE, $gangtok->unit_type);

        $this->assertFalse(User::query()->where('email', 'prashant.sinha@epfindia.gov.in')->exists());
        $this->assertFalse($directoryUnits->contains(
            fn (OrganizationalUnit $unit): bool => data_get($unit->metadata, 'directory_category') === 'guest_house'
        ));
    }
}
