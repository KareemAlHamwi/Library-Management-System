<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\AddCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\Category\CategoryListResource;
use App\Http\Resources\Category\CategoryResource;
use App\Services\Category\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function list(Request $request): AnonymousResourceCollection
    {
        return CategoryListResource::collection(
            $this->categoryService->list((int) $request->input('per_page', 15))
        );
    }

    public function get(int $categoryId): JsonResponse
    {
        $category = $this->categoryService->get($categoryId);

        if (! $category) {
            return response()->json(['message' => __('category.not_found')], 404);
        }

        return response()->json(new CategoryResource($category));
    }

    public function add(AddCategoryRequest $request): JsonResponse
    {
        return response()->json(
            new CategoryResource($this->categoryService->add($request->validated())),
            201
        );
    }

    public function update(UpdateCategoryRequest $request, int $categoryId): JsonResponse
    {
        $category = $this->categoryService->get($categoryId);

        if (! $category) {
            return response()->json(['message' => __('category.not_found')], 404);
        }

        return response()->json(
            new CategoryResource($this->categoryService->update($category, $request->validated()))
        );
    }

    public function delete(int $categoryId): JsonResponse
    {
        $category = $this->categoryService->get($categoryId);

        if (! $category) {
            return response()->json(['message' => __('category.not_found')], 404);
        }

        $this->categoryService->delete($category);

        return response()->json(['message' => __('category.deleted')]);
    }
}
