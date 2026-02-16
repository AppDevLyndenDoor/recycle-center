<?php

namespace Database\Seeders\testSeeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class pickupProductSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $products = [
            ['id' => 1, 'name' => 'Product A', 'uom' => 'each', 'company' => 'Company A', 'orderID' => '1'],
            ['id' => 2, 'name' => 'Product B', 'uom' => 'yards', 'company' => 'Company B', 'orderID' => '2'],
        ];
        $pickupUinits = [
            ['id' => 1, 'user' => 'userA', 'units' => 1 ,'uom' => 'each', 'product' => 'Product A',
                'length' => 0 , 'width' => 0, 'height' => 0, 'bin' => '','date' => '2026-02-10',
                'picked_timestamp' => 1770756148 ,'company' => 'Company A', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '123456789', 'comment' => 'test comment 1'],
            ['id' => 2, 'user' => 'userA', 'units' => 1 ,'uom' => 'each', 'product' => 'Product A',
                'length' => 0 , 'width' => 0, 'height' => 0,'bin' => '', 'date' => '2026-01-30',
                'picked_timestamp' => 1769811338 ,'company' => 'Company A', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '987654321', 'comment' => 'test comment 2'],
            ['id' => 3, 'user' => 'userB', 'units' => 3 ,'uom' => 'yards', 'product' => 'Product b',
                'length' => 1 , 'width' => 1, 'height' => 1,'bin' => 'B1', 'date' => '2026-02-09',
                'picked_timestamp' => 1770672421 ,'company' => 'Company B', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '678912345', 'comment' => 'test comment 3'],
        ];
        DB::table('pickupproduct')
            ->insert($products);
        DB::table('pickupunit')
            ->insert($pickupUinits);

    }
}
