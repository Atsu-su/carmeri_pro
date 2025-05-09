<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Like;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class LikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (range(1, 5) as $index) {
            for ($i = 0; $i < 3; ++$i) {
                $item = Item::query()->find($index);
                if ($item->seller_id == $i + $index) {
                    Like::create([
                        'item_id' => $index,
                        'user_id' => $i + $index + 3,
                    ]);
                } else {
                    Like::create([
                        'item_id' => $index,
                        'user_id' => $i + $index,
                ]);
                }

            }
        }
    }
}
