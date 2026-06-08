<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categories->list($request->user()));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ], 201);
    }

    public function show(Request $request, string $category): CategoryResource
    {
        return new CategoryResource($this->categories->get($request->user(), (int) $category));
    }

    public function update(UpdateCategoryRequest $request, string $category): JsonResponse
    {
        $category = $this->categories->update($request->user(), (int) $category, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => [
                'category' => new CategoryResource($category),
            ],
        ]);
    }

    public function destroy(Request $request, string $category): JsonResponse
    {
        $this->categories->delete($request->user(), (int) $category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
