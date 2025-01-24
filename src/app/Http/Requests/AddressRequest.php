<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'postal_code' => 'required|regex:/^\d{3}-\d{4}$/',
            'address' => 'required|string|max:50',
            'building_name' => 'nullable|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号はXXX-XXXX（半角ハイフンあり）の形式で入力してください',
            'address.required' => '住所を入力してください',
            'address.max' => '住所は50文字以内で入力してください',
            'building_name.max' => '建物名は50文字以内で入力してください',
        ];
    }
}
