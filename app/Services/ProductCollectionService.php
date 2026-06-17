<?php

namespace App\Services;

use App\Http\Resources\CollectionResource;
use App\Models\Tenant\Collection as ProductCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductCollectionService
{
    public static function call(): self
    {
        return app(self::class);
    }


    /**
     * Универсальный прокси (если вдруг хочешь динамику)
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }


    /**
     * @throws ValidationException
     */
    public function store(array $data, $uploadedPhoto = null): CollectionResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'title'=> "required",
            'description'=> "required",
            'is_active'=>"",
            'discount'=>"",
            'order_position'=>"",
            'config'=>"",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        if (!is_null($uploadedPhoto))
            $imageName = $this->uploadPhoto("/public/companies/" . $tenant->company->slug, $uploadedPhoto);

        $id = $data["id"] ?? null;

        $tmp = [
            'tenant_id' => $tenant->id,

            'title' => $data["title"] ?? null,
            'image' => $imageName ?? null,
            'description' => $data["description"] ?? null,
            'is_active' => (($data["is_active"] ?? false) == "true"),
            'discount' => $data["discount"] ?? 0,
            'order_position' => $data["order_position"] ?? 0,
            'config' => isset($data["config"]) ? json_decode($data["config"]) : null
        ];



        $collection = ProductCollection::query()
            ->where("id", $id)
            ->first();

        if (is_null($collection))
            $collection = ProductCollection::query()
                ->create($tmp);
        else
            $collection->update($tmp);

        if (!is_null($data["products"]??null)){

            $ids = array_values(Collection::make(json_decode($data["products"]))
                ->pluck("id")->toArray());

            $collection->products()->sync($ids);
        }

        return new CollectionResource($collection);
    }



    /**
     * @throws HttpException
     */
    public function list($search = null, $size = null,$order = "updated_at", $direction = "desc", $global = false)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $size = $size ?? config('app.results_per_page');

        $collections = ProductCollection::query()
            ->with(["products.categories"])
            ->where("tenant_id", $tenant->id);

        if ($global)
            $collections = $collections
                ->where("is_active", true);
        // ->whereHas("products");

        if (!is_null($search))
            $collections = $collections
                ->where(function ($q) use ($search) {
                    $q->where("title", 'like', "%$search%")
                        ->orwhere("description", 'like', "%$search%");

                })
                ->orWhere("id", 'like', "%$search%");

        $collections = $collections->orderBy($order, $direction);

        return CollectionResource::collection($collections->paginate($size));
    }

    /**
     * @throws HttpException
     */
    public function duplicate($id): CollectionResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $collection = ProductCollection::query()
            ->with(["product"])
            ->where("id", $id)
            ->first();

        if (is_null($collection))
            throw new HttpException(404, "Коллекция не найдена!");

        $new = $collection->replicate();
        $new->save();

        return new CollectionResource($new);
    }

    /**
     * @throws HttpException
     */
    public function destroy($pageId, $force = false): CollectionResource
    {

        $collection = ProductCollection::query()->where("id", $pageId)
            ->first();


        if (is_null($collection))
            throw new HttpException(404, "Коллекция не найдена!");

        $tmp = $collection;
        $collection->products()->detach();
        $collection->delete();

        return new CollectionResource($tmp);
    }
}
