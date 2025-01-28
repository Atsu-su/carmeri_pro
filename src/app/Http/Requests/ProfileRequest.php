<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

class ProfileRequest extends FormRequest
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

    public function prepareForValidation()
    {
        $this->merge([
            'is_changed' => $this->boolean('is_changed'),
        ]);
    }

    public function validationData()
    {
        $all = parent::validationData();

        if ($this->get('file_base64')) {
            // base64をデコード。プレフィックスに「data:image/jpeg;base64,」のような文字列がついている場合は除去して処理する
            $data = explode(',', $this->get('file_base64'));
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

            if ($this->get('file_name_base64')) {
                // ファイル名の指定があればセット
                $filename = $this->get('file_name_base64');
            }

            $file = new UploadedFile(
                $tmpFile->getPathname(),
                $filename,
                $tmpFile->getMimeType(),
                0,
                true
            );

            $all['image'] = $file;
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
            'is_changed' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required|string|max:30',
            'postal_code' => 'required|regex:/^\d{3}-\d{4}$/',
            'address' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            'image.image' => '画像ファイルを選択してください',
            'image.mimes' => 'jpegまたはpng形式の画像ファイルを選択してください',
            'image.max' => 'ファイルサイズは2MB以内にしてください',
            'name.required' => '名前を入力してください',
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号はXXX-XXXX（半角ハイフンあり）の形式で入力してください',
            'address.required' => '住所を入力してください',
            'address.max' => '住所は50文字以内で入力してください',
            'building_name.max' => '建物名は50文字以内で入力してください',
        ];
    }
}
