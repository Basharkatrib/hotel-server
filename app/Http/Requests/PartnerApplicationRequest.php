<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerApplicationRequest extends FormRequest
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
            'hotel_name'        => 'required|string|max:255',
            'property_address'  => 'required|string',
            'property_type'     => 'required|string',
            'legal_name'        => 'required|string',
            'job_title'         => 'required|string',
            'contact_email'     => 'required|email',
            'contact_phone'     => 'required|string',
            'business_license'         => 'required|file|mimes:pdf,jpg,png|max:5120',
            'vat_certificate'          => 'required|file|mimes:pdf,jpg,png|max:5120',
            'insurance_certificate'    => 'required|file|mimes:pdf,jpg,png|max:5120',
            'representative_id'        => 'required|file|mimes:pdf,jpg,png|max:5120',
        ];
    }
}
