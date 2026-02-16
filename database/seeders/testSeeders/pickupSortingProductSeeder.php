<?php

namespace Database\Seeders\testSeeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class pickupSortingProductSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $products = [
            ['id' => 1, 'name' => 'Product A',  'orderID' => '1'],
            ['id' => 2, 'name' => 'Product B',  'orderID' => '2'],
        ];
        $pickupSorting = [
            ['id' => 1, 'user' => 'userA', 'units' => 1 , 'product' => 'Product A',
               'date' => '2026-02-10', 'picked_timestamp' => 1770756148 ,
                'company' => 'Company A', 'status' => 1, 'idempotency' => '123456789', ],
            ['id' => 2, 'user' => 'userA', 'units' => 1 ,'product' => 'Product A',
                 'date' => '2026-01-30', 'picked_timestamp' => 1769811338 ,'company' => 'Company A',
                'status' => 1, 'idempotency' => '987654321'],
            ['id' => 3, 'user' => 'userB', 'units' => 3 , 'product' => 'Product b',
                'date' => '2026-02-09', 'picked_timestamp' => 1770672421 ,'company' => 'Company B',
                'status' => 1, 'idempotency' => '678912345', ],
        ];
        DB::table('pickup_sorting_product')
            ->insert($products);
        DB::table('pickup_Sorting')
            ->insert($pickupSorting);

    }
}
