<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

class CreateVenueRequest extends FormRequest
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
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|size:10',
            'location' => 'required|string',
            'court_count' => 'required|integer|min:1',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'price_slots' => 'required|array|min:1',
            'price_slots.*.start_time' => 'required|date_format:H:i',
            'price_slots.*.end_time' => 'required|date_format:H:i|after:price_slots.*.start_time',
            'price_slots.*.price' => 'required|integer|min:1',
        ];
    }
} 