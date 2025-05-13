<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aquí puedes colocar tu lógica de autorización;
        // si no la necesitas, devuelve true.
        return true;
    }

    public function rules(): array
    {
        return [
            'topic'        => 'required|string',
            'payload'      => 'required|array',
        ];
    }
}
