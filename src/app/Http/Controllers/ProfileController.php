<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Messages\Message;
use App\Messages\Session as MessageSession;
use App\Traits\CompressImage;
use Exception;
use Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use CompressImage;

    public function edit()
    {
        $user = auth()->user();
        $message = MessageSession::exists('message');
        return view('profile_input', compact('user', 'message'));
    }

    public function update(ProfileRequest $request){
        /*
        * 流れ
        * 1. リクエストから画像を取得
        * 2. 画像がある場合、画像を保存（アップデート）
        * 3. 画像がない場合（nullの場合）、imageの項目を除きアップデート
        * 4. 2の場合アップデート成功後、前の画像を削除
        */

        $user = auth()->user();
        $currentImage = $user->image;
        $validated = $request->validated();
        $image = $validated['image'];

        if ($validated['is_changed']) {
            if (isset($image)) {
                // 新しく画像が登録される場合
                $extension = explode('/', $image->getMimeType())[1];
                $fileName = 'profile_image_'. time().'.'.$extension;

                // 画像を圧縮
                $resizedImage = $this->compressImage(
                    $image->getRealPath(),
                    102,
                    102,
                    $image->getMimeType(),
                    ['jpeg' => 75, 'png' => 75]
                );

                // 画像を保存（storeAsはテスト時に保存先を変更できないため使用しない）
                // メモリ上のImageインスタンスを保存するのでputメソッドを使用
                Storage::disk('public')->put(
                    'profile_images/'.$fileName,
                    $resizedImage,
                );

                $validated['image'] = $fileName;
            } else {
                // 画像が登録されない場合（NO DATAの場合）
                $validated['image'] = null;
            }
        } else {
            // 画像が変更されない場合、imageの項目を除去
            unset($validated['image']);
        }

        try {
            $user->update($validated);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()
                ->route('mypage')
                ->withInput()
                ->with('message', Message::get('profile.failed'));
        }

        if ($user->wasChanged('image') && $currentImage) {
            // 画像が変更された場合、古い画像を削除
            Storage::delete('public/profile_images/' . $currentImage);
        }

        return redirect()
            ->route('mypage')
            ->with('message', Message::get('profile.success'));
    }
}
