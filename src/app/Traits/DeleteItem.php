<?php

namespace App\Traits;

use App\Models\Item;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait DeleteItem
{
    /**
     * Delete item
     *
     * @param int $itemId
     * @return mix
     */

    public function deleteItem($itemId)
    {
        $user = auth()->user();

        $item = Item::where('seller_id', $user->id)
            ->where('on_sale', true)
            ->where('id', $itemId)
            ->first();

        try {
            $item->delete();
            return $item;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}