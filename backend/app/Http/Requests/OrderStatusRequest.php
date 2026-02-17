<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $order = $this->route('order');

        if (!$user || !$order) {
            return ['status' => ['required']];
        }

        // Buyer can only cancel orders
        if ($user->role === 'buyer') {
            return [
                'status' => ['required', 'in:cancelled'],
                'payment_status' => ['nullable', 'in:unpaid,paid,refunded'],
            ];
        }

        // Sellers can transition items they own; admins can do anything
        return [
            'status' => ['required', 'in:pending,paid,shipped,delivered,cancelled,refunded'],
            'payment_status' => ['nullable', 'in:unpaid,paid,refunded'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'status' => ['Unauthorized order status transition for your role.'],
        ]);
    }
}
