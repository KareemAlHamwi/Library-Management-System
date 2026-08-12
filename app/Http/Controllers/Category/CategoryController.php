<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\AddCategoryRequest;
use App\Http\Resources\Category\CategoryListResource;
use App\Http\Resources\Category\CategoryResource;
use App\Services\Category\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function list(Request $request): AnonymousResourceCollection
    {
        return CategoryListResource::collection(
            $this->categoryService->list((int) $request->input('per_page', 15))
        );
    }

    public function get(int $catCategoryId): JsonResponse
    {
        $catCategory = $this->categoryService->get($catCategoryId);

        if (! $catCategory) {
            return response()->json(['message' => __('catCategory.catCategory_not_found')], 404);
        }

        return response()->json(new CategoryResource($catCategory));
    }

    public function add(AddCategoryRequest $request): JsonResponse
    {
        return response()->json(
            new CategoryResource($this->categoryService->add($request->validated())),
            201
        );
    }

    public function delete(int $catCategoryId): JsonResponse
    {
        $catCategory = $this->categoryService->get($catCategoryId);

        if (! $catCategory) {
            return response()->json(['message' => __('catCategory.catCategory_not_found')], 404);
        }

        $this->categoryService->delete($catCategory);

        return response()->json(['message' => __('catCategory.catCategory_deleted')]);
    }
}
