<?php

namespace Database\Seeders\testSeeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class pickupBinSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $bins = [
            [ 'binNumber' => 'B1', 'company' => 'Company A','yards' => 2.0,'location' => 'prep', 'orderID' => '1'],
            ['binNumber' => 'S-1', 'company' => 'Company A','yards' => 1.0,'location' => 'VM', 'orderID' => '2'],
        ];

        DB::table('pickupbin')
            ->insert($bins);


    }
}
