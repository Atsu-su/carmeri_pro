<?php

namespace Database\Seeders;

use App\Models\Purchase;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Purchase::create([
            'session_id' => 'cs_'.Str::random(20),
            'item_id' => 1,
            'buyer_id' => 2,
            'payment_method_id' => 1,
            'status' => key(Purchase::PROCESSING),
            'is_chat_enabled' => false,
            'shipped_at' => null,
        ]);

        Purchase::create([
            'session_id' => 'cs_'.Str::random(20),
            'item_id' => 2,
            'buyer_id' => 1,
            'payment_method_id' => 2,
            'status' => key(Purchase::PAID),
            'is_chat_enabled' => true,
            'shipped_at' => null,
        ]);

        Purchase::create([
            'session_id' => 'cs_'.Str::random(20),
            'item_id' => 6,
            'buyer_id' => 2,
            'payment_method_id' => 1,
            'status' => key(Purchase::SHIPPED),
            'is_chat_enabled' => true,
            'shipped_at' => now()->addDay(2),
        ]);

        Purchase::create([
            'session_id' => 'cs_'.Str::random(20),
            'item_id' => 7,
            'buyer_id' => 1,
            'payment_method_id' => 2,
            'status' => key(Purchase::COMPLETED),
            'is_chat_enabled' => false,
            'shipped_at' => now()->addDay(7),
        ]);

        // $faker = Faker::create();

        // for ($i = 1; $i <= 5; ++$i) {
        //     Purchase::create([
        //         'item_id' => $i,
        //         'buyer_id' => $i + 1,
        //         'payment_method_id' => $faker->numberBetween(1, 2),
        //         'status' => 'processing',
        // ]);}

        // for ($i = 6; $i <= 10; ++$i) {
        //     Purchase::create([
        //         'item_id' => $i,
        //         'buyer_id' => $i - 4,
        //         'payment_method_id' => $faker->numberBetween(1, 2),
        //         'status' => 'processing',
        // ]);}
    }
}
