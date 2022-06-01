<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entry;
class EntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Entry::factory()->count(82)->create();
    }
}
