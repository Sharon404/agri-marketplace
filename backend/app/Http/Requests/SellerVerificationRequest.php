<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic user info (inherited from registration)
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            
            // Seller-specific (HIGH SCRUTINY)
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            
            // Verification documents
            'tax_id' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/'],
            'national_id' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/'],
            
            // Bank details for payouts
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            
            // Acceptance of terms
            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.regex' => 'Tax ID must contain only letters, numbers, and hyphens.',
            'national_id.regex' => 'National ID must contain only letters and numbers.',
            'bank_account_number.regex' => 'Bank account number must contain only digits.',
            'terms_accepted.accepted' => 'You must accept the seller terms and conditions.',
        ];
    }
}
