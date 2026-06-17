<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
    /**
     * @throws \HttpException
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {

        return BusinessLogic::collection()
            ->setBot($request->bot ?? null)
            ->list(
                search:$request->search ?? null,
                size: $request->get("size") ?? config('app.results_per_page'),
                order: $request->order_by ?? "updated_at",
                direction: $request->direction ?? "asc"
            );

    }

    /**
     * @throws \HttpException
     */
    public function globalList(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {

        return BusinessLogic::collection()
            ->setBot($request->bot ?? null)
            ->list(
                search:$request->search ?? null,
                size: $request->get("size") ?? config('app.results_per_page'),
                order: $request->order_by ?? "updated_at",
                direction: $request->direction ?? "asc",
                global: true
            );

    }

    /**
     * @throws \Exception
     */
    public function duplicate(Request $request, $id): ProductCollectionResource
    {
        return BusinessLogic::collection()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->duplicate($id);
    }

    /**
     * @throws HttpException
     */
    public function destroy(Request $request, $id): ProductCollectionResource
    {
        return BusinessLogic::collection()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->destroy($id);
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function store(Request $request): ProductCollectionResource
    {
        $request->validate([
            'title'=> "required",
            'description'=> "required",
            'is_public'=>"",
            'is_active'=>"",
            'discount'=>"",
            'order_position'=>"",
            'config'=>"",
        ]);


        return BusinessLogic::collection()
            ->setBot($request->bot ?? null)
            ->setBotUser($request->botUser ?? null)
            ->store($request->all(),
                $request->hasFile('photo') ?
                    $request->file('photo') : null);
    }
}
