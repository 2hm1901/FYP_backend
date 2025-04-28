<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookCourtRequest extends FormRequest
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
            'user_id' => 'required|integer',
            'venue_id' => 'required|integer',
            'venue_name' => 'required|string',
            'venue_location' => 'required|string',
            'renter_name' => 'required|string',
            'renter_email' => 'required|email',
            'renter_phone' => 'required|string',
            'courts_booked' => 'required|array',
            'courts_booked.*.court_number' => 'required|string',
            'courts_booked.*.start_time' => 'required|string',
            'courts_booked.*.end_time' => 'required|string',
            'courts_booked.*.price' => 'required|integer',
            'courts_booked.*.status' => 'required|string|in:awaiting,accepted,cancelled',
            'total_price' => 'required|integer',
            'booking_date' => 'required|string',
            'note' => 'string|nullable',
            'payment_image' => 'nullable|string|regex:/^data:image\/[a-z]+;base64,/',
        ];
    }
} 