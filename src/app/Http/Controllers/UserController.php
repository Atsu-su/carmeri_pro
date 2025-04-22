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
use App\Models\Purchase;
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

        $validItems = Item::query()
            ->filterByUserStatusWithoutSelect('items', 'seller_id')
            ->select('items.id', 'items.seller_id');

        $sellingImtems = Purchase::query()
            ->joinSub($validItems, 'valid_items', function ($join) {
                $join->on('valid_items.id', '=', 'purchases.item_id');
            })
            ->where('status', '!=', 'completed')
            ->where('valid_items.seller_id', $user->id)
            ->get();

        $purchasingItems = Purchase::query()
            ->filterByUserStatus('purchases', 'buyer_id')
            ->where('status', '!=', 'completed')
            ->where('buyer_id', $user->id)
            ->get();

        // 取引中の商品がある場合は退会できない
        if ($sellingImtems->isNotEmpty() || $purchasingItems->isNotEmpty()) {
            return back()
                ->with('message', Message::get('user.deactivate.invalid'));
        }

        try {
            // ユーザを無効化
            $user->update([
                'is_active' => 0,
                'rating_sum' => 0,
                'evaluations' => 0,
                'email_verified_at' => null,
                'user_status_changed_at' => now(),
            ]);

            // ログアウト処理を実行
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
            // ヘッダーの種類を決めるキー（headerType）を削除
            request()->offsetUnset('headerType');

            return view('thanks');
        } catch (Exception $e) {
            Log::error($e->getMessage());
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

    public function activateUser(ActivateUserRequest $activateUserRequest)
    {
        $user = User::where('email', $activateUserRequest->input('email'))
            ->where('is_active', 0)
            ->first();

        event(new Registered($user));

        $user->update([
            'is_active' => 1,
            'user_status_changed_at' => now(),
        ]);
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
