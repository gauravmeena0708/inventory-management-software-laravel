<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Devcat;
class DevcatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $devcats = array(
            array(
                "id" => 1,
                "name" => "Category 1",
                "salary" => "56000",
                "exp" => "5 Yrs",
                "dev" => 6,
                "collab" => 4,
                "qualification" => "B.E/B.tech/MCA/Mtech/MS(IT)/MSc(CS/IT)"
            ),
            array(
                "id" => 2,
                "name" => "Category 2",
                "salary" => "35000",
                "exp" => "2-5 Yrs",
                "dev" => 0,
                "collab" => 14,
                "qualification" => "B.E/B.tech/MCA/Mtech/MS(IT)/MSc(CS/IT)"
            ),
            array(
                "id" => 3,
                "name" => "Category 3",
                "salary" => "26000",
                "exp" => "0-2  Yrs",
                "dev" => 0,
                "collab" => 15,
                "qualification" => "B.E/B.tech/MCA/Mtech/MS(IT)/MSc(CS/IT)"
            ),
            array(
                "id" => 4,
                "name" => "Category 4",
                "salary" => "85000",
                "exp" => "7  Yrs",
                "dev" => 0,
                "collab" => 1,
                "qualification" => "B.E/B.tech/MCA/Mtech/MS(IT)/MSc(CS/IT)"
            ),
        );

        Devcat::insert($devcats);
    }
}
