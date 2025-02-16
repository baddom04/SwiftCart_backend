<?php

namespace Database\Seeders;

use App\Models\Grocery;
use App\Models\Household;
use App\Models\User;
use App\Models\UserHousehold;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->has(Household::factory(2))->create();

        foreach (Household::all() as $household) {

            for ($i = 0; $i < rand(1, 10); $i++) {
                Grocery::factory()->create(['household_id' => $household->id]);
            }

            $user = User::where('id', '!=', $household->user->id)->inRandomOrder()->first();
            UserHousehold::factory()->create(['user_id' => $household->user->id, 'household_id' => $household->id]);
            UserHousehold::factory()->create(['user_id' => $user->id, 'household_id' => $household->id]);
        }
    }
}
