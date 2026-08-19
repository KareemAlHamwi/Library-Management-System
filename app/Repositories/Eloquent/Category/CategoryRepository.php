<?php

namespace App\Repositories\Eloquent\Category;

use App\Models\Category;
use App\Repositories\Contracts\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('books')
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Category
    {
        return Category::with('books')->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
