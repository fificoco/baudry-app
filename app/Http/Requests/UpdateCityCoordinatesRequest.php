<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityCoordinatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls admin et dispatcher peuvent modifier les coordonnées
        return $this->user()?->isDispatcher() ?? false;
    }

    public function rules(): array
    {
        return [
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.between' => 'La latitude doit être comprise entre -90 et 90.',
            'lng.between' => 'La longitude doit être comprise entre -180 et 180.',
        ];
    }
}
