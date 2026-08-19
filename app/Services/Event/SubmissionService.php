<?php

namespace App\Services\Event;

use App\Enums\EventStatus;
use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\EventSubmission;
use App\Models\User;
use App\Repositories\Contracts\Event\EventRepositoryInterface;
use App\Repositories\Contracts\Event\SubmissionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $submissionRepository,
        private readonly EventRepositoryInterface $eventRepository
    ) {}

    public function getEventSubmissions(Event $event, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->submissionRepository->getEventSubmissions($event, $filters, $perPage);
    }

    public function getUserSubmissions(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->submissionRepository->getUserSubmissions($user, $perPage);
    }

    public function submit(User $user, Event $event, array $data): EventSubmission
    {
        if ($event->status !== EventStatus::OPEN) {
            throw new \Exception('This event is not accepting submissions.', 422);
        }

        if (! $this->eventRepository->isParticipant($user, $event)) {
            throw new \Exception('You must join the event before submitting.', 403);
        }

        if ($this->submissionRepository->hasSubmitted($user, $event)) {
            throw new \Exception('You have already submitted to this event.', 409);
        }

        return $this->submissionRepository->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'content' => $data['content'],
        ]);
    }

    public function approve(EventSubmission $submission, array $data): EventSubmission
    {
        if ($submission->status !== SubmissionStatus::PENDING) {
            throw new \Exception('Only pending submissions can be approved.', 422);
        }

        return DB::transaction(function () use ($submission, $data) {
            $approved = $this->submissionRepository->approve($submission);

            $this->submissionRepository->awardPoints([
                'event_id' => $submission->event_id,
                'user_id' => $submission->user_id,
                'submission_id' => $submission->id,
                'points_awarded' => $data['points_awarded'],
                'reason' => $data['reason'],
            ]);

            $submission->user->increment('event_points', $data['points_awarded']);

            return $approved;
        });
    }

    public function reject(EventSubmission $submission): EventSubmission
    {
        if ($submission->status !== SubmissionStatus::PENDING) {
            throw new \Exception('Only pending submissions can be rejected.', 422);
        }

        return $this->submissionRepository->reject($submission);
    }

    public function getEventSubmissionsAsSupervisor(User $supervisor, Event $event, array $filters, int $perPage): LengthAwarePaginator
    {
        if ($event->supervisor_id !== $supervisor->id) {
            throw new \Exception('You are not the supervisor of this event.', 403);
        }

        return $this->submissionRepository->getEventSubmissions($event, $filters, $perPage);
    }

    public function approveAsSupervisor(User $supervisor, EventSubmission $submission, array $data): EventSubmission
    {
        $submission->loadMissing('event');

        if ($submission->event->supervisor_id !== $supervisor->id) {
            throw new \Exception('You are not the supervisor of this event.', 403);
        }

        return $this->approve($submission, $data);
    }

    public function rejectAsSupervisor(User $supervisor, EventSubmission $submission): EventSubmission
    {
        $submission->loadMissing('event');

        if ($submission->event->supervisor_id !== $supervisor->id) {
            throw new \Exception('You are not the supervisor of this event.', 403);
        }

        return $this->reject($submission);
    }
}
