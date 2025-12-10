<?php

namespace App\Http\Validation;

use Illuminate\Http\Request;

class JwstRequestValidator
{
    public static function validateFeedRequest(Request $request): array
    {
        // Нормализуем пустые строки в null
        $data = $request->all();
        if (isset($data['suffix']) && $data['suffix'] === '') {
            $data['suffix'] = null;
        }
        if (isset($data['program']) && $data['program'] === '') {
            $data['program'] = null;
        }
        if (isset($data['instrument']) && $data['instrument'] === '') {
            $data['instrument'] = null;
        }
        
        $rules = [
            'source' => 'sometimes|nullable|string|in:jpg,suffix,program',
            'suffix' => 'nullable|string|max:50',
            'program' => 'nullable|string|max:20',
            'instrument' => 'nullable|string|in:NIRCam,MIRI,NIRISS,NIRSpec,FGS',
            'page' => 'sometimes|nullable|integer|min:1|max:100',
            'perPage' => 'sometimes|nullable|integer|min:1|max:60',
        ];
        
        // Валидация для API запросов
        if ($request->expectsJson() || $request->is('api/*')) {
            $validator = \Validator::make($data, $rules);
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
            return $validator->validated();
        }
        
        return $request->validate($rules);
    }
}


