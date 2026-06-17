<?php

namespace App\Http\Controllers;

use App\Facades\PartnerService;
use Illuminate\Http\Request;

class PartnersController extends Controller
{
    public function index(Request $request): \App\Http\Resources\PartnerCollection
    {
        return PartnerService::call()
            ->list($request->all());
    }

    public function togglePartnersInFavorites(Request $request)
    {
        $request->validate([
            "id" => "required"
        ]);

        return response()
            ->json([
                "fav_partners" => PartnerService::call()
                    ->togglePartnerInFavorites($request->id)
            ]);
    }

    public function partnersCategories(Request $request): \Illuminate\Database\Eloquent\Collection|array
    {
        return PartnerService::call()
            ->listOfPartnersCategories();
    }

    public function updateSettings(Request $request)
    {

        return PartnerService::call()
            ->updateSettings($request->all());
    }

    public function updateActiveStatus(Request $request)
    {
        return PartnerService::call()
            ->updateActiveStatus($request->all());
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            "product_id" => "required",
            "partner_id" => "required",
            "status" => "required",
        ]);

        return PartnerService::call()
            ->changeStatus($request->all());
    }


    /**
     * @throws ValidationException
     */
    public function store(Request $request): \App\Http\Resources\PartnerResource
    {
        $request->validate([
            "telegram_domain" => "required",
        ]);

        return PartnerService::call()
            ->create($request->all());
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request): \App\Http\Resources\PartnerResource
    {
        $request->validate([
            'id' => "required",
            'bot_partner_id' => "required",
            'title' => "",
            'description' => "",
            'image' => "",
            'is_active' => "",
            'extra_charge' => "",
            'config' => "",
            'legal_info' => "",
        ]);

        return PartnerService::call()
            ->update($request->all(), $request->hasFile("file") ? $request->file("file") : null);


    }

    /**
     * @throws ValidationException
     */
    public function updateSelf(Request $request)
    {
        $request->validate([
            'title' => "",
            'description' => "",
        ]);

        return PartnerService::call()
            ->updateSelf($request->all(), $request->hasFile("file") ? $request->file("file") : null);


    }

    public function destroy(Request $request, $id)
    {

        return PartnerService::call()
            ->destroy($id);

    }
}
