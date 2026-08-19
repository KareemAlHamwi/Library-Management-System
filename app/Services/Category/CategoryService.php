<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Repositories\Contracts\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function list(int $perPage): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function get(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function add(array $data): Category
    {
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('categories', 'public');
        }

        return $this->categoryRepository->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['image'])) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $data['image']->store('categories', 'public');
        }

        return $this->categoryRepository->update($category, $data);
    }

    public function delete(Category $category): void
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $this->categoryRepository->delete($category);
    }
}
