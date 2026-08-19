<?php

namespace App\Repositories\Contracts\Event;

use App\Models\Event;
use App\Models\EventPointLog;
use App\Models\EventSubmission;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubmissionRepositoryInterface
{
    public function getEventSubmissions(Event $event, array $filters, int $perPage): LengthAwarePaginator;

    public function getUserSubmissions(User $user, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?EventSubmission;

    public function hasSubmitted(User $user, Event $event): bool;

    public function create(array $data): EventSubmission;

    public function approve(EventSubmission $submission): EventSubmission;

    public function reject(EventSubmission $submission): EventSubmission;

    public function awardPoints(array $data): EventPointLog;
}
