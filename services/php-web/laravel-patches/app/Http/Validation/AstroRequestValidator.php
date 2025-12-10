<?php

namespace App\Http\Validation;

use Illuminate\Http\Request;

class AstroRequestValidator
{
    public static function validateEventsRequest(Request $request): array
    {
        return $request->validate([
            'lat' => 'sometimes|numeric|between:-90,90',
            'lon' => 'sometimes|numeric|between:-180,180',
            'days' => 'sometimes|integer|min:1|max:30',
        ]);
    }
}


