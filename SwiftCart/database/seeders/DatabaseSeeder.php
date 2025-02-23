<?php

namespace Database\Seeders;

use App\Models\Grocery;
use App\Models\Household;
use App\Models\User;
use App\Models\Comment;
use App\Models\UserHousehold;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->has(Household::factory(2))->create();

        foreach (Household::all() as $household) {
            $user = User::where('id', '!=', $household->user->id)->inRandomOrder()->first();
            UserHousehold::factory()->create(['user_id' => $household->user->id, 'household_id' => $household->id]);
            UserHousehold::factory()->create(['user_id' => $user->id, 'household_id' => $household->id]);

            for ($i = 0; $i < rand(1, 10); $i++) {
                Grocery::factory()->create(['household_id' => $household->id, 'user_id' => $household->user_households()->inRandomOrder()->first()->user->id]);
            }
        }

        foreach (Grocery::all() as $grocery) {
            if (rand(1, 5) == 1) {
                Comment::factory()->create(['grocery_id' => $grocery->id, 'user_id' => $grocery->household->user_households()->inRandomOrder()->first()->user->id]);
            }
        }

        User::factory()->create([
            'name' => 'Domi',
            'email' => 'domi@gmail.com',
            'password' => config('secrets.password'),
            'admin' => true,
        ]);
        User::factory()->create([
            'name' => 'Dorka',
            'email' => 'dorka@gmail.com',
            'password' => 'password',
            'admin' => false,
        ]);
    }
}
