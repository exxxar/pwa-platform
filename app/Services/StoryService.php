<?php

namespace App\Services;

use App\Http\Resources\StoryCollection;
use App\Http\Resources\StoryResource;
use App\Models\Tenant\Story;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoryService
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
     * Получение списка историй
     */
    public function list($size = null): StoryCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $size = $size ?? config('app.results_per_page');

        $stories = Story::query()
            ->where('tenant_id', $tenant->id)
            ->paginate($size);

        return new StoryCollection($stories);
    }

    /**
     * Получение истории по ID
     */
    public function getById(int $storyId): StoryResource
    {
        $story = Story::query()->find($storyId);

        if (is_null($story)) {
            throw new HttpException(404, "История не найдена.");
        }

        return new StoryResource($story);
    }

    /**
     * Создание или обновление истории
     * @throws ValidationException
     */
    public function store(array $data, $files = []): StoryResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            'id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            //  'thumbnail' => 'nullable|string',
            // 'image' => 'nullable|string',
            'description' => 'nullable|string',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data["tenant_id"] = $tenant->id;

        $needAutoSendStories = $data["need_auto_send_stories"] ?? false;

        unset($data["need_auto_send_stories"]);
        unset($data["thumbnail"]);
        unset($data["image"]);

        if (!empty($data['id'])) {
            $story = Story::query()->find($data['id']);
            if (!$story) {
                throw new HttpException(404, "История не найдена.");
            }
            $story->update($data);
        } else {
            $story = Story::query()->create($data);
        }

        if (count($files ?? []) > 0) {
            $thumbnail = $files->get("thumbnail");
            $image = $files->get("image");

            $filename = time() . '_' . $thumbnail[0]->getClientOriginalName();
            $thumbnail[0]->move(public_path('images/shop/' . $tenant->uuid), $filename);

            if (!is_null($story->thumbnail ?? null)) {
                $oldPath = public_path('images/shop/' . $tenant->uuid . "/" . $story->thumbnail);
                if (file_exists($oldPath))
                    unlink($oldPath);
            }

            $story->thumbnail = '/images/shop/' . $tenant->uuid . "/" . $filename;

            $filename = time() . '_' . $image[0]->getClientOriginalName();
            $image[0]->move(public_path('images/shop/' . $tenant->uuid), $filename);

            if (!is_null($story->image ?? null)) {
                $oldPath = public_path('images/shop/' . $tenant->uuid . "/" . $story->image);
                if (file_exists($oldPath))
                    unlink($oldPath);
            }
            $story->image = '/images/shop/' . $tenant->uuid . "/" . $filename;

            $story->save();
        }



        return new StoryResource($story);
    }

    /**
     * Удаление истории
     */
    public function destroy(int $storyId): StoryResource
    {
        $story = Story::query()->find($storyId);

        if (is_null($story)) {
            throw new HttpException(404, "История не найдена.");
        }

        $story->delete();

        return new StoryResource($story);
    }
}
