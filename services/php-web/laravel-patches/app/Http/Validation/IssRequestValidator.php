<?php

namespace App\Http\Validation;

use Illuminate\Http\Request;

class IssRequestValidator
{
    public static function validateLastRequest(Request $request): array
    {
        return $request->validate([
            'limit' => 'sometimes|integer|min:1|max:1000',
        ]);
    }

    public static function validateTrendRequest(Request $request): array
    {
        return $request->validate([
            'limit' => 'sometimes|integer|min:1|max:1000',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
        ]);
    }
}


