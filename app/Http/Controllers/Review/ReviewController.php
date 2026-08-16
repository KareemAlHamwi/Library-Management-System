<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\AddReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\Review\ReviewResource;
use App\Models\Book;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    public function index(Request $request, Book $book): AnonymousResourceCollection
    {
        return ReviewResource::collection(
            $this->reviewService->getBookReviews(
                $book,
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function myReviews(Request $request): AnonymousResourceCollection
    {
        return ReviewResource::collection(
            $this->reviewService->getUserReviews(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function store(AddReviewRequest $request, Book $book): JsonResponse
    {
        try {
            $review = $this->reviewService->add(
                $request->user(),
                $book,
                $request->validated()
            );

            return response()->json(new ReviewResource($review->load('user')), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        try {
            $updated = $this->reviewService->update(
                $request->user(),
                $review,
                $request->validated()
            );

            return response()->json(new ReviewResource($updated->load('user')));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        try {
            $this->reviewService->delete($request->user(), $review);

            return response()->json(['message' => 'Review deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // ── Admin ────────────────────────────────────────────────

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'book_id' => $request->input('book_id'),
            'is_approved' => $request->has('is_approved')
                ? filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN)
                : null,
        ];

        return ReviewResource::collection(
            $this->reviewService->getAllReviews(
                array_filter($filters, fn ($v) => ! is_null($v)),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function approve(Review $review): JsonResponse
    {
        $approved = $this->reviewService->approve($review);

        return response()->json(new ReviewResource($approved->load(['user', 'book'])));
    }

    public function adminDestroy(Review $review): JsonResponse
    {
        $this->reviewService->adminDelete($review);

        return response()->json(['message' => 'Review deleted successfully.']);
    }
}
