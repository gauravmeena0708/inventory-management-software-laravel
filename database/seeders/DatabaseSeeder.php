<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(AgreementSeeder::class);
        $this->call(ConsumableSeeder::class);
        $this->call(EntrySeeder::class);
        $this->call(DevcatSeeder::class);
        $this->call(DeveloperSeeder::class);
        $this->call(ServerSeeder::class);
        $this->call(FileSeeder::class);
        $this->call(TaskSeeder::class);
        $this->call(DeviceSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(DesktopSeeder::class);
        $this->call(ManufacturerSeeder::class);  
        $this->call(OfficialSeeder::class);
        $this->call(StorageSeeder::class);
    }
}
