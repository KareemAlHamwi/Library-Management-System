<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\AwardPointsRequest;
use App\Http\Requests\Event\SubmitContentRequest;
use App\Http\Resources\Event\SubmissionResource;
use App\Models\Event;
use App\Models\EventSubmission;
use App\Services\Event\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService
    ) {}

    public function store(SubmitContentRequest $request, Event $event): JsonResponse
    {
        try {
            $submission = $this->submissionService->submit(
                $request->user(),
                $event,
                $request->validated()
            );

            return response()->json(new SubmissionResource($submission->load('event')), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function mySubmissions(Request $request): AnonymousResourceCollection
    {
        return SubmissionResource::collection(
            $this->submissionService->getUserSubmissions(
                $request->user(),
                (int) $request->input('per_page', 15)
            )
        );
    }

    // ── Admin ────────────────────────────────────────────────

    public function eventSubmissions(Request $request, Event $event): AnonymousResourceCollection
    {
        return SubmissionResource::collection(
            $this->submissionService->getEventSubmissions(
                $event,
                $request->only(['status']),
                (int) $request->input('per_page', 15)
            )
        );
    }

    public function approve(AwardPointsRequest $request, EventSubmission $submission): JsonResponse
    {
        try {
            $approved = $this->submissionService->approve($submission, $request->validated());

            return response()->json(new SubmissionResource($approved->load(['user', 'eventPointLogs'])));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function reject(EventSubmission $submission): JsonResponse
    {
        try {
            $rejected = $this->submissionService->reject($submission);

            return response()->json(new SubmissionResource($rejected->load('user')));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    // ── Supervisor ───────────────────────────────────────────

    public function supervisorEventSubmissions(Request $request, Event $event): AnonymousResourceCollection
    {
        try {
            return SubmissionResource::collection(
                $this->submissionService->getEventSubmissionsAsSupervisor(
                    $request->user(),
                    $event,
                    $request->only(['status']),
                    (int) $request->input('per_page', 15)
                )
            );
        } catch (\Exception $e) {
            abort($e->getCode() ?: 400, $e->getMessage());
        }
    }

    public function supervisorApprove(AwardPointsRequest $request, EventSubmission $submission): JsonResponse
    {
        try {
            $approved = $this->submissionService->approveAsSupervisor(
                $request->user(),
                $submission,
                $request->validated()
            );

            return response()->json(new SubmissionResource($approved->load(['user', 'eventPointLogs'])));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function supervisorReject(Request $request, EventSubmission $submission): JsonResponse
    {
        try {
            $rejected = $this->submissionService->rejectAsSupervisor($request->user(), $submission);

            return response()->json(new SubmissionResource($rejected->load('user')));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
