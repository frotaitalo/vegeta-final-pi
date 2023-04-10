<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
           'name' => ['required', 'string'],
           'category_id' => ['required', 'int', Rule::in(array_column(Category::all()->toArray(), 'id'))],
           'description' => ['required', 'string'],    
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Informe o nome',
            'category_id.required' => 'Selecione uma categoria',
            'description.required' => 'Informe a descrição',
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}