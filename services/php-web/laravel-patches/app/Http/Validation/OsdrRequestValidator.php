<?php

namespace App\Http\Validation;

use Illuminate\Http\Request;

class OsdrRequestValidator
{
    public static function validateListRequest(Request $request): array
    {
        return $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'sort_column' => 'sometimes|string|in:id,dataset_id,title,updated_at,inserted_at',
            'sort_order' => 'sometimes|string|in:asc,desc',
        ]);
    }
}


