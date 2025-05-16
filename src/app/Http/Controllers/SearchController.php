<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Like;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private $searchArray;

    public function __construct(Request $request)
    {
        // ページネーションに渡すために名称はformのものと合わせる
        $this->searchArray = [
            'keyword' => $request->input('keyword') ?? null,
            'category_id' => $request->input('category_id') ?? null,
            'brand' => $request->input('brand') ?? null,
            'condition_id' => $request->input('condition_id') ?? null,
            'min_price' => $request->input('min_price') ?? null,
            'max_price' => $request->input('max_price') ?? null,
            'on_sale' => $request->input('on_sale') ?? 0,
        ];
    }

    public function advancedSearchQuery($query, $searchArray)
    {
        $keyword = $searchArray['keyword'];
        $categoryId = $searchArray['category_id'];
        $brand = $searchArray['brand'];
        $conditionId = $searchArray['condition_id'];
        $minPrice = $searchArray['min_price'];
        $maxPrice = $searchArray['max_price'];
        $onSale = $searchArray['on_sale'];

        return $query
            ->when($keyword, function ($query, $keyword) {
                return $query->where('items.name', 'like', "%$keyword%");
            })
            ->when($categoryId, function ($query, $categoryId) {
                return $query->join('category_item', 'items.id', 'category_item.item_id')
                    ->where('category_item.category_id', $categoryId);
            })
            ->when($brand, function ($query, $brand) {
                return $query->where('items.brand', 'like', "%$brand%");
            })
            ->when($conditionId, function ($query, $conditionId) {
                return $query->where('items.condition_id', $conditionId);
            })
            ->when($minPrice, function ($query, $minPrice) {
                return $query->where('items.price', '>=', $minPrice);
            })
            ->when($maxPrice, function ($query, $maxPrice) {
                return $query->where('items.price', '<=', $maxPrice);
            })
            ->when($onSale, function ($query) {
                return $query->where('items.on_sale', true);
            });
    }

    public function search(Request $request)
    {
        // 検索の場合はスクロールなし、ページネーションありにする
        $searchFlag = true;
        $keyword = $request->input('keyword');

        if (auth()->check()) {
            $user = auth()->user();

            $items = Item::query()
                ->filterByUserStatus('items', 'seller_id')
                ->where('items.name', 'like', "%$keyword%")
                ->where('items.seller_id', '!=', $user->id)
                ->orderBy('id', 'desc')
                ->paginate(10);

            $likedItems = Like::query()
                ->with('item')
                ->filterByUserStatus('likes')
                ->where('likes.user_id', $user->id)
                ->whereHas('item', function ($query) use ($keyword, $user) {
                    $query->where('items.name', 'like', "%$keyword%")
                          ->where('items.seller_id', '!=', $user->id);
                })
                ->orderBy('item_id', 'desc')
                ->paginate(10);

                return view('index', compact('items', 'likedItems', 'keyword', 'searchFlag'));
            } else {
                $items = Item::query()
                    ->filterByUserStatus('items', 'seller_id')
                    ->where('items.name', 'like', "%$keyword%")
                    ->orderBy('items.id', 'desc')
                    ->paginate(10);

                return view('index', compact('items', 'keyword', 'searchFlag'));
        }
    }

    public function advancedSearch(Request $request)
    {
        $advancedSearchFlag = true;
        $searchArray = $this->searchArray;

        $items = $this->advancedSearchQuery(Item::query(), $this->searchArray)
            ->filterByUserStatus('items', 'seller_id')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('index', compact('advancedSearchFlag', 'searchArray', 'items'));
    }

    public function favoriteAdvancedSearch(Request $request)
    {
        $advancedSearchFlag = true;
        $user = auth()->user();
        $searchArray = $this->searchArray;

        $likedItems = $this->advancedSearchQuery(Like::query(), $this->searchArray)
            ->with('item')
            ->filterByUserStatus('likes')
            ->join('items', 'likes.item_id', 'items.id')
            ->where('likes.user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('favorite', compact('advancedSearchFlag', 'searchArray', 'likedItems'));
    }
}
