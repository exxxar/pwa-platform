<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BitrixController extends Controller
{
    public function index(Request $request): BitrixCollection
    {
        return BusinessLogic::bitrix()
            ->setBot($request->bot ?? null)
            ->get();
    }


    /**
     * @throws ValidationException
     */
    public function store(Request $request): BitrixResource
    {
        $request->validate([
            "url" => "required",

        ]);

        return BusinessLogic::bitrix()
            ->setBot($request->bot ?? null)
            ->store($request->all());
    }

    /**
     * @throws ValidationException
     */
    public function check(Request $request): Response
    {
        $request->validate([
            "url" => "required",
        ]);

        $status = BusinessLogic::bitrix()
            ->setBotUser($request->botUser ?? null)
            ->setBot($request->bot ?? null)
            ->check($request->all());

        return response()->noContent($status ?? 400);
    }

    public function remove(Request $request, $id): Response
    {
        BusinessLogic::bitrix()
            ->setBotUser($request->botUser ?? null)
            ->setBot($request->bot ?? null)
            ->removeConnectionItem($id);

        return response()->noContent();
    }
}
