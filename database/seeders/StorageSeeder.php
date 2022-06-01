<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Storage;
class StorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storages = array(
            array(
                "id" => 1,
                "description" => "HNAS",
                "serial" => "M2SEKW1444863",
                "warranty_end" => "2020-09-30",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 2,
                "description" => "G 1000",
                "serial" => "50735",
                "warranty_end" => "2020-09-30",
                "location" => "Gurgaon",
                "location_id" => NULL,
                "type" => "storage box"
            ),
            array(
                "id" => 3,
                "description" => "G 1000",
                "serial" => "50868",
                "warranty_end" => "2020-09-30",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "storage box"
            ),
            array(
                "id" => 4,
                "description" => "HUS150",
                "serial" => "93043509",
                "warranty_end" => "2020-09-30",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 5,
                "description" => "HNAS",
                "serial" => "M2SEKW1444873",
                "warranty_end" => "2020-09-30",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 6,
                "description" => "Brocade 6510",
                "serial" => "BRW2538L08M",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/ RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 7,
                "description" => "Brocade 6510",
                "serial" => "BRW2538L08N",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 8,
                "description" => "Brocade 6510",
                "serial" => "BRW2538L08K",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 9,
                "description" => "Brocade 6510",
                "serial" => "BRW2537L04Y",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 10,
                "description" => "Brocade 6510",
                "serial" => "BRW2537L04V",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 11,
                "description" => "Brocade 6510",
                "serial" => "BRW2537L04X",
                "warranty_end" => "2020-09-30",
                "location" => "NDC Dwarka/RailTel DC",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 12,
                "description" => "G 1000",
                "serial" => "51174",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "storage box"
            ),
            array(
                "id" => 13,
                "description" => "HUS150",
                "serial" => "93043495",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 14,
                "description" => "HNAS",
                "serial" => "M2SEKW1514541",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 15,
                "description" => "HNAS",
                "serial" => "M2SEKW1514542",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "unified storage"
            ),
            array(
                "id" => 16,
                "description" => "SW 7800",
                "serial" => "ASS2550L00G",
                "warranty_end" => "2020-12-31",
                "location" => "Gurgaon",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 17,
                "description" => "SW 7800",
                "serial" => "ASS2550L00J",
                "warranty_end" => "2020-12-31",
                "location" => "Gurgaon",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 18,
                "description" => "SW 7800",
                "serial" => "ASS2550L00L",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 19,
                "description" => "SW 7800",
                "serial" => "ASS2550L00P",
                "warranty_end" => "2020-12-31",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 20,
                "description" => "SW 7800",
                "serial" => "ASS2550L00T",
                "warranty_end" => "2020-12-31",
                "location" => "Secunderabad",
                "location_id" => NULL,
                "type" => "switch"
            ),
            array(
                "id" => 21,
                "description" => "SW 7800",
                "serial" => "ASS2550L00X",
                "warranty_end" => "2020-12-31",
                "location" => "Dwarka",
                "location_id" => NULL,
                "type" => "switch"
            ),
        );
        Storage::insert($storages);
    }
}
