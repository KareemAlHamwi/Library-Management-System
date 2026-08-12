<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Repositories\Contracts\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository) {}

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
        return $this->categoryRepository->create($data);
    }

    public function delete(Category $category): void
    {
        $this->categoryRepository->delete($category);
    }
}
