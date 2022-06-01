<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Consumable;
class ConsumableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Consumable::factory()->count(82)->create();
    }
}
