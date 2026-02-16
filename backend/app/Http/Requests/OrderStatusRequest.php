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
        return [
            'status' => ['required', 'in:pending,paid,shipped,delivered,cancelled,refunded'],
            'payment_status' => ['nullable', 'in:unpaid,paid,refunded'],
        ];
    }
}
