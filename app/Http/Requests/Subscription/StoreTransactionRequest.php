<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_id' => 'required|exists:subscriptions,id',
        ];
    }

    /**
     * Customize the error messages for the validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'subscription_id.required' => 'The subscription_id field is required.',
            'price.numeric' => 'price field format is invalid. Must be numeric',
        ];
    }
}
