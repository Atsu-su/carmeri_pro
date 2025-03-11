<?php

namespace App\Http\Controllers;

// use App\Auth\Events\Registered;
use App\Messages\Message;
use App\Messages\Session as MessageSession;
use App\Models\Comment;
use App\Models\Item;
use App\Models\Like;
use App\Models\User;
use App\Http\Requests\ActivateUserRequest;
use App\Http\Requests\PasswordRequest;
use App\Http\Requests\RatingRequest;
use App\Traits\DeleteItem;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\RegisterResponse;

class UserController extends Controller
{
    use DeleteItem;

    public function deactivateUser()
    {
        $user = auth()->user();

        try {
            DB::beginTransaction();

            // ユーザを無効化
            $user->update([
                'is_active' => 0,
                'email_verified_at' => null,
            ]);

            // 出品商品（未購入）の全削除
            // カテゴリー、コメント、いいねも同時に削除される
            $items = Item::where('seller_id', $user->id)
                ->where('on_sale', true)
                ->get();
            $itemIds = $items->pluck('id')
                ->toArray();

            foreach ($itemIds as $itemId) {
                $result = $this->deleteItem($itemId);
                if (!$result) {
                    throw new Exception('Failed to delete item. The item ID is '.$itemId);
                }
            }
            Comment::where('user_id', $user->id)->delete();
            Like::where('user_id', $user->id)->delete();

            DB::commit();

            // 画像削除
            foreach ($items as $item) {
                Storage::delete('item_images/'.$item->image);
            }

            // ログアウト処理を実行
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
            // ヘッダーの種類を決めるキー（headerType）を削除
            request()->offsetUnset('headerType');

            return view('thanks');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return redirect()
                ->route('mypage')
                ->with('message', Message::get('user.deactivate.failed'));
        }
    }

    public function inputEmail()
    {
        request()->offsetUnset('headerType');
        return view('auth.input_email');
    }

    public function activateUser(
            Request $request,
            ActivateUserRequest $activateUserRequest
        )
    {
        $request->merge(['activate' => true]);
        $user = User::where('email', $activateUserRequest->input('email'))
            ->where('is_active', 0)
            ->first();

        event(new Registered($user));

        $user->update(['is_active' => 1]);
        Auth::guard('web')->login($user);

        return app(RegisterResponse::class);
    }

    public function editPassword()
    {
        request()->offsetUnset('headerType');
        return view('auth.input_password');
    }

    public function updatePassword(PasswordRequest $request)
    {
        $user = auth()->user();
        $password = Hash::make($request->input('password'));
        try {
            $user->fill(['password' => $password])->save();
            return redirect()
                ->route('register.profile.edit')
                ->with('message', Message::get('profile.password.updated.success'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()
                ->route('index')
                ->with('message', Message::get('profile.password.updated.failed'));
        }
    }

    // （chat view）
    // ・[done] ボタンクリックでモーダルが開く
    // ・送信で評価を送る
    //  (purchasecontroller)
    // ・[done] 取引完了ボタン押下でAPIでpurchaseテーブルのstatusをcompleteに変更する
    //  (usercontroller)
    // ・評価を送るボタンで評価を保存する（total_rating, total_evaluations）
    //  (homecontroller)
    // ・purchasesテーブルのstatusがprocessingの時表示
    //  (mypage view)
    // ・評価を計算する

    public function rating(RatingRequest $request, $seller_id)
    {
        // $seller_idは出品者のID
        $user = User::find($seller_id);

        try {
            $user->update([
                'rating_sum' => $user->rating_sum + $request->input('rating'),
                'evaluations' => $user->evaluations + 1,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        } finally {
            return redirect()->route('index');
        }
    }
}
