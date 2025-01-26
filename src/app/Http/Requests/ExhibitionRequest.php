<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function validationData()
    {
        $all = parent::validationData();

        if ($this->input('file_base64')) {
            // base64をデコード。プレフィックスに「data:image/jpeg;base64,」のような文字列がついている場合は除去して処理する
            $data = explode(',', $this->input('file_base64'));
            if (isset($data[1])) {
                $fileData = base64_decode($data[1]);
            } else {
                $fileData = base64_decode($data[0]);
            }

            // tmp領域に画像ファイルとして保存しFileでラップする
            $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();  // 一時ファイルパス（ファイル名を含む）
            file_put_contents($tmpFilePath, $fileData); // ファイル保存
            $tmpFile = new File($tmpFilePath);

            $filename = $tmpFile->getFilename();

            // 画像ファイル以外が送信された場合の対策
            if (strpos($tmpFile->getMimeType(), 'image') !== false) {
                $file = new UploadedFile(
                    $tmpFile->getPathname(),
                    $filename,
                    $tmpFile->getMimeType(),
                    0,
                    true
                );

                $all['image'] = $file;
            } else {
                // 画像ファイルではない場合（jsで処理できなかった場合）
                $all['image'] = 'NOT_IMAGE';
            }
        } else {
            // 画像ファイルではない場合（jsで処理済み）またはデータが空の場合
            $all['image'] = 'NOT_IMAGE';
        }

        return $all;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:30',
            'brand' => 'nullable|string|max:30',
            'category_id' => 'required|array',
            'category_id.*' => 'required|integer',
            'price' => 'required|integer|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:3072',
            'condition_id' => 'required',
            'description' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '商品名を入力して下さい',
            'name.max' => '商品名は30文字以内で入力して下さい',
            'brand.max' => 'ブランド名は30文字以内で入力して下さい',
            'category_id.required' => 'カテゴリーを選択して下さい',
            'price.required' => '価格を入力して下さい',
            'price.integer' => '価格は数字で入力して下さい',
            'price.min' => '価格は0円以上として下さい',
            'image.required' => '画像を選択して下さい',
            'image.image' => '画像ファイル（jpeg/jpg, png）を選択して下さい',
            'image.mimes' => 'jpeg（jpg）またはpng形式の画像ファイルを選択して下さい',
            'image.max' => 'ファイルサイズは3MB以内にして下さい',
            'condition_id.required' => '商品の状態を選択して下さい',
            'description.required' => '商品の説明を入力して下さい',
            'description.max' => '商品の説明は255文字以内で入力して下さい',
        ];
    }
}
