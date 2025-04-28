<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
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
            'owner_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|size:10',
            'location' => 'sometimes|string',
            'court_count' => 'sometimes|integer|min:1',
            'open_time' => 'sometimes|date_format:H:i',
            'close_time' => 'sometimes|date_format:H:i|after:open_time',
            'price_slots' => 'sometimes|array|min:1',
            'price_slots.*.start_time' => 'required_with:price_slots|date_format:H:i',
            'price_slots.*.end_time' => 'required_with:price_slots|date_format:H:i|after:price_slots.*.start_time',
            'price_slots.*.price' => 'required_with:price_slots|integer|min:1',
        ];
    }
} 