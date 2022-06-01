<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Agreement;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $counter =1;
        $agreements = Agreement::get();
        $payments = Payment::count();
        if ($payments) {
            return "Already Generated";
        }
        $update_array = array();
        foreach($agreements as $agreement){
            if ($agreement['frequency']>0) {
                $payment_numbers= 12/$agreement['frequency'];
                foreach(range(1, $payment_numbers) as $i) {
                    $push_array=array(
                            "id" => $counter,
                            "name" => $agreement['name']."_".$i,
                            "agreement_id" => $agreement['id'],
                            "due_date" => "2020-".$agreement['frequency']*$i."-01",
                            "amount" => $agreement['annual_cost']/$agreement['frequency'],
                            "remark" => NULL,
                            "pending" => 1
                    );
                    array_push($update_array,$push_array);
                    $counter++;
                }
            }
        }
        
        Payment::insert($update_array);
        /*$payments = array(
            array(
                "id" => 1,
                "name" => "BSNL MPLS Q1",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-04-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 2,
                "name" => "BSNL MPLS Q2",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-07-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 3,
                "name" => "BSNL MPLS Q3",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-10-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 4,
                "name" => "BSNL MPLS Q4",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2021-01-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 5,
                "name" => "Railtel MPLS Q1",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-04-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 6,
                "name" => "Railtel MPLS Q2",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-07-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 7,
                "name" => "Railtel MPLS Q3",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-10-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 8,
                "name" => "Railtel MPLS Q4",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2021-01-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 9,
                "name" => "Railtel DC Services Q1",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-04-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 10,
                "name" => "Railtel DC Services Q2",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-07-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 11,
                "name" => "Railtel DC Services Q3",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2020-10-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 12,
                "name" => "Railtel DC Services Q4",
                "file_id" => 1,
                "agreement_id" => 1,
                "due_date" => "2021-01-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 13,
                "name" => "Housekeeping-Jan",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-02-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 14,
                "name" => "Housekeeping-Feb",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-03-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 15,
                "name" => "Housekeeping-Mar",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-04-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 16,
                "name" => "Housekeeping-Apr",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-05-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 17,
                "name" => "Housekeeping-May",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-06-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 18,
                "name" => "Housekeeping-Jun",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-07-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 19,
                "name" => "Housekeeping-Jul",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-08-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 20,
                "name" => "Housekeeping-Aug",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-09-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 21,
                "name" => "Housekeeping-Sep",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-10-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 22,
                "name" => "Housekeeping-Oct",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-11-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 23,
                "name" => "Housekeeping-Nov",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2020-12-01",
                "remark" => NULL,
                "pending" => 0,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
            array(
                "id" => 24,
                "name" => "Housekeeping-Dec",
                "file_id" => 2,
                "agreement_id" => 2,
                "due_date" => "2021-01-01",
                "remark" => NULL,
                "pending" => 1,
                "created_at" => NULL,
                "updated_at" => NULL,
            ),
        );
        Payment::insert($payments);
        */
    }
}
