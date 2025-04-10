<?php

namespace Database\Seeders;

use App\Models\Grocery;
use App\Models\Household;
use App\Models\User;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Product;
use App\Models\Section;
use App\Models\Store;
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

        $stores = Store::factory(5)->create()->each(function ($store) {

            Location::factory()->create([
                'store_id' => $store->id,
            ]);

            $map = Map::factory()->create([
                'store_id' => $store->id,
            ]);

            $sections = Section::factory(8)->create([
                'map_id' => $map->id,
            ]);


            for ($x = 0; $x < $map->x_size; $x++) {
                for ($y = 0; $y < $map->y_size; $y++) {
                    $segment = MapSegment::factory()->make();
                    $segment->x = $x;
                    $segment->y = $y;
                    $segment->map_id = $map->id;
                    $segment->section_id = rand(0, 1) ? $sections->random()->id : null;
                    $segment->save();

                    $productCount = rand(1, 3);
                    Product::factory($productCount)->create([
                        'map_segment_id' => $segment->id,
                    ]);
                }
            }
        });
        foreach ($stores as $store) {
            $store->published = true;
            $store->save();
        }
    }
}
