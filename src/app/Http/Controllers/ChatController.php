<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Library\Message;
use App\Models\Chat;
use App\Models\Item;
use App\Models\Purchase;
use App\Traits\CompressImage;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;

class ChatController extends Controller
{
    use CompressImage;

    public function decodeBase64($base64)
    {
        if ($base64) {
            // base64をデコード。プレフィックスに「data:image/jpeg;base64,」のような文字列がついている場合は除去して処理する
            $data = explode(',', $base64);
            if (isset($data[1])) {
                $fileData = base64_decode($data[1]);
            } else {
                $fileData = base64_decode($data[0]);
            }

            // tmp領域に画像ファイルとして保存してUploadedFileとして扱う
            $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
            file_put_contents($tmpFilePath, $fileData);
            $tmpFile = new File($tmpFilePath);
            $filename = $tmpFile->getFilename();
            $file = new UploadedFile(
                $tmpFile->getPathname(),
                $filename,
                $tmpFile->getMimeType(),
                0,
                true
            );
        } else {
            return null;
        }
        return $file;
    }

    public function updateToRead($chats, $purchaseId, $receiverId, $user)
    {
        // チャットを既読に変更する
        $filteredChats = $chats
            ->filter(function ($chat) use ($user) {
                // 送信者のチャットかつ未読の場合のみ残す
                return $chat->sender_id != $user->id && !$chat->is_read;
            });

        $chatIds = $filteredChats
            ->pluck('id')
            ->toArray();
        $isTextArray = $filteredChats
            ->pluck('is_text')
            ->toArray();

        try {
            Chat::whereIn('id', $chatIds)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '既読フラグの変更に失敗'
            ], 500);
        }

        // メッセージオブジェクトの作成
        $message = new Message;
        $message->purchaseId = $purchaseId;
        $message->receiverId = $receiverId;
        $message->chatId = $chatIds;
        $message->isText = $isTextArray;
        $message->method = 'read';

        broadcast(new MessageSent($message))->toOthers();
    }

    public function index($purchase_id)
    {
        $user = auth()->user();
        $isBuyer = false; // true: 購入者, false: 出品者
        $isSeller = false;  // true: 出品者, false: 購入者

        // 購入IDが不正な場合

        // このユーザが出品者かどうかを判定
        try {
            // 出品者
            Purchase::query()
                ->whereHas('item', function ($query) use ($user) {
                    $query->where('seller_id', $user->id);
                })
                ->where('id', $purchase_id)
                ->firstOrFail();
        } catch (Exception $e) {
            // 購入者
            $isBuyer = true;
        }

        // このユーザが購入者かどうか判定
        try {
            // 購入者
            Purchase::query()
                ->where('buyer_id', $user->id)
                ->where('id', $purchase_id)
                ->firstOrFail();
        } catch (Exception $e) {
            // 出品者
            $isSeller = true;
        }

        // このユーザが出品者または購入者ではない場合はアクセスを拒否
        if ($isBuyer && $isSeller || !$isBuyer && !$isSeller) {
            abort(403);
        }

        // 表示されていない商品にアクセスした際は404を返す（findOrFail）
        if (!$isBuyer && $isSeller) {
            $validItems = Item::query()
                ->filterByUserStatus('items', 'seller_id');
            Purchase::query()
                ->joinSub($validItems, 'valid_items', function ($query) {
                    $query->on('purchases.item_id', '=', 'valid_items.id');
                })
                ->select('purchases.*')
                ->findOrFail($purchase_id);
        } elseif ($isBuyer && !$isSeller) {
            Purchase::query()
                ->filterByUserStatus('purchases', 'buyer_id')
                ->findOrFail($purchase_id);
        }

        // チャット情報を取得
        $chats = Chat::query()
            ->with('user:id,name,image')
            ->where('purchase_id', $purchase_id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 取引相手の情報を取得
        $purchase = null;
        $receiverId = null;

        if (!$isBuyer && $isSeller) {
            // （出品者の場合）購入者の情報を取得
            $purchase = Purchase::query()
                ->with(['item:id,name,price,image', 'user:id,name,image'])
                ->where('id', $purchase_id)
                ->select('id', 'item_id', 'buyer_id')
                ->first();
            $receiverId = $purchase->buyer_id;
        } elseif ($isBuyer && !$isSeller) {
            // （購入者の場合）出品者の情報を取得
            $purchase = Purchase::query()
                ->with(['item:id,seller_id,name,price,image', 'item.user:id,name,image'])
                ->where('id', $purchase_id)
                ->select('id', 'item_id')
                ->first();
            $receiverId = $purchase->item->seller_id;
        }
        $purchaseId = $purchase->id;

        // 未読かどうかの判定はupdateToReadメソッド内で行う
        $this->updateToRead($chats, $purchaseId, $receiverId, $user);

        // 現在取引中の出品商品
        $sellingItems = Purchase::query()
            ->with('item:id,name')
            ->whereHas('item', function ($query) use ($user) {
                $query->filterByUserStatusWithoutSelect('items', 'seller_id')
                    ->where('seller_id', $user->id);
            })
            ->where('status', Purchase::PROCESSING)
            ->where('id', '!=', $purchase_id)
            ->select('id', 'item_id')
            ->get();

        // 商品名が長い場合は省略する
        $sellingItems->map(function ($purchase) {
            $purchase->item->name = mb_strlen($purchase->item->name) > 7
                ? mb_substr($purchase->item->name, 0, 7) . '...'
                : $purchase->item->name;
            return $purchase;});

        // 現在取引中の購入商品
        $purchasingItems = Purchase::query()
            ->with('item:id,name')
            ->filterByUserStatusWithoutSelect('purchases', 'buyer_id')
            ->where('purchases.buyer_id', $user->id)
            ->where('purchases.status', Purchase::PROCESSING)
            ->where('purchases.id', '!=', $purchase_id)
            ->select('purchases.id', 'purchases.item_id')
            ->get();

        // 商品名が長い場合は省略する
        $purchasingItems->map(function ($purchase) {
            $purchase->item->name = mb_strlen($purchase->item->name) > 7
                ? mb_substr($purchase->item->name, 0, 7) . '...'
                : $purchase->item->name;
            return $purchase;
        });

        return view('chat',compact(
            'chats',
            'purchase',
            'isSeller',
            'sellingItems',
            'purchasingItems'
        ));
    }

    public function sendMessage(Request $request, $purchase_id, $receiver_id)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $validator = Validator::make(
            $request->all(),
            ['message' => 'required|string|max:400'],
            [
                'message.required' => 'メッセージを入力してください',
                'message.string' => 'メッセージの形式が不正です',
                'message.max' => 'メッセージは400文字以内で入力してください'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 422);
        }

        // メッセージをテーブルに保存
        try {
            $chat= Chat::create([
                'purchase_id' => $purchase_id,
                'sender_id' => $user->id,
                'is_read' => false,
                'is_text' => true,
                'message' => $request->input('message'),
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'メッセージの保存に失敗'
            ], 500);
        }

        // メッセージオブジェクトの作成
        $message = new Message;
        $message->chatId = $chat->id;          // チャットID
        $message->receiverId = $receiver_id;   // 受信者のユーザID
        $message->purchaseId = $purchase_id;   // 購入ID
        $message->username = $user->name;
        $message->message = $request->input('message');
        $message->datetime = $now->format('Y/m/d H:i');
        $message->image = $user->image;
        $message->isText = true;               // テキスト送信の場合はtrue
        $message->method = 'create';

        // メッセージ送信イベントを送信
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function read($chat_id, $purchase_id, $receiver_id)
    {
        $user = auth()->user();
        $chat = Chat::where('id', $chat_id)->get();
        $this->updateToRead($chat, $purchase_id, $receiver_id, $user);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $chat_id, $receiver_id)
    {
        $validator = Validator::make(
            $request->all(),
            ['updated-message' => 'required|string|max:400'],
            [
                'updated-message.required' => 'メッセージを入力してください',
                'updated-message.string' => 'メッセージの形式が不正です',
                'updated-message.max' => 'メッセージは400文字以内で入力してください'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chat = Chat::find($chat_id);
            $chat->message = $request->input('updated-message');
            $chat->save();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'メッセージの更新に失敗'
            ], 500);
        }

        // メッセージオブジェクトの作成
        $message = new Message;
        $message->purchaseId = $chat->purchase_id; // 購入ID
        $message->receiverId = $receiver_id;
        $message->chatId = $chat->id;
        $message->isText = true;
        $message->message = $request->input('updated-message');
        $message->method = 'update';

        // メッセージの更新イベントを送信
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'chatId' => $chat_id,
            'updatedMessage' =>  $request->input('updated-message'),
        ]);
    }

    public function delete($chat_id, $receiver_id)
    {
        try {
            $chat = Chat::find($chat_id);
            $chat->update(['is_deleted' => true]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'メッセージの削除に失敗'
            ], 500);
        }

        // メッセージオブジェクトの作成
        $message = new Message;
        $message->purchaseId = $chat->purchase_id; // 購入ID
        $message->receiverId = $receiver_id;
        $message->chatId = $chat->id;
        $message->isText = $chat->is_text;
        $message->method = 'delete';

        // メッセージの更新イベントを送信
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'chatId' => $chat_id,
            'isText' => $chat->is_text,
        ]);
    }

    public function sendImage(Request $request, $purchase_id, $receiver_id)
    {
        $user = auth()->user();
        $now = Carbon::now();
        $file = $this->decodeBase64($request->input('base64'));

        $validator = Validator::make(
            ['image' => $file],
            ['image' => 'required|image|mimes:jpeg,png,jpg|max:2048'],
            [
                'image.required' => '画像を選択してください',
                'image.image' => '画像の形式が不正です',
                'image.mimes' => 'png または jpeg形式でアップロードしてください',
                'image.max' => '2MB以下の画像を選択してください'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 圧縮処理を追加

        try {
            // ファイルの保存
            $extension = $file->extension();
            $fileName = 'chat_image_'. time() . '.' . $extension;

            // 画像を圧縮・保存
            $compressedFile = $this->compressImage(
                $file->getRealPath(),
                380,
                380,
                $file->getMimeType(),
                ['jpeg' => 75, 'png' => 75]
            );
            Storage::put('chat_images/'.$fileName, $compressedFile,);

            // chatsテーブルへ情報を追加
            $chat= Chat::create([
                'purchase_id' => $purchase_id,
                'sender_id' => $user->id,
                'is_read' => false,
                'is_text' => false,
                'message' => $fileName,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '画像の保存及び送信に失敗'
            ], 500);
        }

        // メッセージの作成と送信
        $message = new Message;
        $message->chatId = $chat->id;          // チャットID
        $message->receiverId = $receiver_id;   // 受信者のユーザID
        $message->purchaseId = $purchase_id;   // 購入ID
        $message->username = $user->name;
        $message->message = Storage::disk('public')->url('chat_images/' . $fileName);
        $message->datetime = $now->format('Y/m/d H:i');
        $message->image = $user->image;
        $message->isText = false;              // 画像送信の場合はfalse
        $message->method = 'create';

        // メッセージ送信イベントを送信
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
