<?php

namespace Database\Seeders\testSeeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class userSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        User::factory()->create([
            'name' => 'recycle',
            'email' => 'recycle@example.com',
        ]);
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $userNames =['id' => 4, 'userNames' => 'Mike,Brent,Johnny,Carlos,Christian,Winston' ];
        DB::table('pickup_user_names')->insert($userNames);
    }
}
