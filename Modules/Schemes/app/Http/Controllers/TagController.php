<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\TagRequest;
use Modules\Schemes\Http\Resources\TagResource;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Services\TagService;

class TagController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private readonly TagService $service) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 15);

        if ($search) {
            $paginator = Tag::search($search)->paginate($perPage);
        } else {
            $paginator = Tag::query()->orderBy('name')->paginate($perPage);
        }

        $paginator->getCollection()->transform(fn($tag) => new TagResource($tag));
        return $this->paginateResponse($paginator);
    }

    public function store(TagRequest $request)
    {
        $this->authorize('create', Tag::class);
        
        $validated = $request->validated();
        
        // Handle bulk creation with names array
        if (isset($validated['names']) && is_array($validated['names'])) {
            $tags = $this->service->createMany($validated['names']);
            return $this->success(
                TagResource::collection($tags),
                __('messages.tags.created')
            );
        }
        
        // Handle single tag creation
        $tag = $this->service->create($validated);
        return $this->created(new TagResource($tag), __('messages.tags.created'));
    }

    public function show(Tag $tag)
    {
        return $this->success(new TagResource($tag));
    }

    public function update(TagRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);
        $updated = $this->service->update($tag->id, $request->validated());

        return $this->success(new TagResource($updated), __('messages.tags.updated'));
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $this->service->delete($tag->id);

        return $this->success([], __('messages.tags.deleted'));
    }
}
