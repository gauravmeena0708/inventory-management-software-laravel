<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Gaurav Meena',
            'email' => 'gauravmeena0708@gmail.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$vOfuq5OlnIfkr0DY.VS.HuDOIrr6eIHbuB3bp2mmlSIrPt6pKGUki', // password
            'remember_token' => Str::random(10),
        ]);
        User::factory()->count(5)->create();
    }
}
