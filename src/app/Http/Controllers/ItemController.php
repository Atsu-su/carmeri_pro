<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use App\Messages\Message;
use App\Models\Category;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\Like;
use App\Models\Condition;
use App\Messages\Session as MessageSession;
use App\Models\Comment;
use App\Models\User;
use App\Traits\CompressImage;
use App\Traits\DeleteItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    use CompressImage;
    use DeleteItem;

    public function saveItemImage($image, $fileName): void
    {
        $resizedImage = $this->compressImage(
            $image->getRealPath(),
            700,
            700,
            $image->getMimeType(),
            ['jpeg' => 75, 'png' => 75]
        );

        // メモリ上のImageインスタンスを保存するのでputメソッドを使用
        Storage::put('item_images/'.$fileName, $resizedImage);
    }

    public function checkUser(Item $item, User $user)
    {
        if ($item->seller_id !== $user->id) {
            abort(403);
        }
    }

    public function show($item_id)
    {
        $item = Item::query()
        ->with(['categoryItems.category', 'condition'])
        ->withCount('likes')
        ->withCount('comments')
        ->find($item_id);

        // ログインしていなくても商品情報は表示可能なため確認する
        if (auth()->check()) {
            $user = auth()->user();

            // ログインしているユーザのコメント
            $myComment = Comment::query()
                ->with('user')
                ->where('item_id', $item_id)
                ->where('user_id', $user->id)
                ->first();

            // 他のユーザのコメント
            $comments = Comment::query()
                ->with('user')
                ->where('item_id', $item_id)
                ->where('user_id', '!=', $user->id)
                ->get();

            // いいねしているかどうかを判定（true or false）
            $like = Like::query()
                ->where('item_id', $item_id)
                ->where('user_id', $user->id)
                ->exists();
        } else {
            // ログインしていない場合
            $myComment = null;
            $comments = Comment::query()
                ->with('user')
                ->where('item_id', $item_id)
                ->get();

            // ログインしていないのでいいねの表示は行わない
            $like = false;
        }

        // リダイレクトされた場合に存在する可能性のあるメッセージを処理
        $message = MessageSession::exists('message');

        return view('item', compact('item', 'like', 'message', 'myComment', 'comments'));
    }

    public function create()
    {
        $categories = Category::all();
        $conditions = Condition::all();
        return view('item_input', compact('categories', 'conditions'));
    }

    public function edit($item_id)
    {
        $user = auth()->user();
        $categories = Category::all();
        $conditions = Condition::all();
        $item = Item::query()
            ->with('categoryItems')
            ->where('id', $item_id)
            ->first();

        // 出品者のみ編集可能
        $this->checkUser($item, $user);

        $categoryIdArray = $item->categoryItems->pluck('category_id')->toArray();

        return view('item_input', compact('categories', 'conditions', 'item', 'categoryIdArray'));
    }

    public function update(ExhibitionRequest $request, $item_id)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // itemsテーブル更新のための準備
        if ($validated['is_changed'] === 'true') {
            // $validated['image']を使う
            $image = $validated['image'];
            $extension = $image->extension();
            $fileName = 'item_image_'. time() . '.' . $extension;

            // 画像を圧縮・保存
            $this->saveItemImage($image, $fileName);

            $itemData =array_merge($validated, [
                'image' => $fileName,
            ]);
        } else {
            $itemData = $validated;
        }

        // category_itemテーブル更新のための準備
        $oldCategory = CategoryItem::where('item_id', $item_id)
            ->pluck('category_id')->toArray();
        $newCategory = $validated['category_id'];

        // 削除対象と追加対象を取得
        $toDelete = array_diff($oldCategory, $newCategory);
        $toInsert = array_diff($newCategory, $oldCategory);

        try {
            DB::beginTransaction();

            // itemsテーブル更新
            $item = Item::find($item_id);

            // 出品者のみ編集可能
            $this->checkUser($item, $user);

            $item->fill($itemData)->save();

            // category_itemテーブル更新
            // カテゴリーID削除
            foreach ($toDelete as $id) {
                CategoryItem::where('item_id', $item_id)
                    ->where('category_id', $id)
                    ->delete();
            }

            foreach ($toInsert as $id) {
                CategoryItem::create([
                    'item_id' => $item_id,
                    'category_id' => $id,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('item.show', ['item_id' => $item_id])
                ->with('message', Message::get('list.update.success'));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return redirect()
                ->route('item.show', ['item_id' => $item_id])
                ->with('message', Message::get('list.update.failed'));
        }
    }

    public function store(ExhibitionRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // $validated['image']を使う
        $image = $validated['image'];
        $extension = $image->extension();
        $fileName = 'item_image_'. time() . '.' . $extension;

        // 画像を圧縮
        $this->saveItemImage($image, $fileName);

        $itemData = array_merge($validated, [
            'seller_id' => $user->id,
            'image' => $fileName,
        ]);

        try {
            DB::beginTransaction();

            $item = Item::create($itemData);

            foreach($itemData['category_id'] as $category_id) {
                CategoryItem::create([
                    'item_id' => $item->id,
                    'category_id' => $category_id,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('mypage')
                ->with('message', Message::get('list.create.success'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Storage::delete('item_images/' . $fileName);
            DB::rollBack();
            return redirect()
                ->route('mypage')
                ->with('message', Message::get('list.create.failed'));
        }
    }

    public function delete($item_id)
    {
        // $resultはItemインスタンスかfalseを返す
        $result = $this->deleteItem($item_id);

        if ($result) {
            Storage::delete('item_images/' . $result->image);
            return redirect()
                ->route('mypage')
                ->with('message', Message::get('list.delete.success'));
        } else {
            return redirect()
                ->route('mypage')
                ->with('message', Message::get('list.delete.failed'));
        }
    }
}
