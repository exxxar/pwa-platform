<?php

namespace App\Http\Controllers;

use App\Facades\StoryService;
use App\Http\Resources\StoryCollection;
use App\Http\Resources\StoryResource;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function index(Request $request): StoryCollection
    {
        return StoryService::call()
            ->list(
                size: $request->size ?? 20,
            );
    }


    public function store(Request $request): StoryResource
    {
        $request->validate([
            "title" => "required|string",
            //"type" => "required|in:image,video",
        ]);

        return StoryService::call()
            ->store($request->all(), $request->files ?? []);
    }

    public function destroy(Request $request, $storyId): StoryResource
    {

        return StoryService::call()
            ->destroy($storyId);
    }
}
