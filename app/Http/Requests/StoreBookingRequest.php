<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'                 => ['required', 'exists:rooms,id'],
            'hotel_id'                => ['required', 'exists:hotels,id'],
            'check_in_date'           => ['required', 'date', 'after_or_equal:today'],
            'check_out_date'          => ['required', 'date', 'after:check_in_date'],
            'guest_name'              => ['required', 'string', 'max:255'],
            'guest_email'             => ['required', 'email', 'max:255'],
            'guest_phone'             => ['required', 'string', 'max:20'],
            'guests_count'            => ['required', 'integer', 'min:1'],
            'rooms_count'             => ['integer', 'min:1'],
            'guests_details'          => ['nullable', 'array'],
            'guests_details.*.name'   => ['required_with:guests_details', 'string', 'max:255'],
            'guests_details.*.email'  => ['required_with:guests_details', 'email', 'max:255'],
            'guests_details.*.phone'  => ['required_with:guests_details', 'string', 'max:20'],
            'special_requests'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
