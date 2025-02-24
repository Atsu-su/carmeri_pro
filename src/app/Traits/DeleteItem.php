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
     * @return boolean
     */

    public function deleteItem($itemId): bool
    {
        $user = auth()->user();

        $item = Item::where('seller_id', $user->id)
            ->where('on_sale', true)
            ->where('id', $itemId)
            ->first();

        try {
            $item->delete();
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}