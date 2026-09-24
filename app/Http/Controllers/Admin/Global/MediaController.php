<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Загрузка картинки рекламного блока.
     * Возвращает готовый URL для сохранения в partner.config.ads[].image
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'], // до 5 МБ
        ]);

        $path = $request->file('file')->store('partners/promotions', 'public');

        return response()->json([
            'url'  => '/storage/' . $path,
            'path' => $path,
        ]);
    }
}
