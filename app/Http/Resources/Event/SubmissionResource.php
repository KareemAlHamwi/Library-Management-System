<?php

namespace App\Http\Resources\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'status' => $this->status->value,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'full_name' => $this->user->first_name.' '.$this->user->last_name,
            ]),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
            'points_log' => $this->whenLoaded('eventPointLogs', fn () => $this->eventPointLogs->map(fn ($log) => [
                'points_awarded' => $log->points_awarded,
                'reason' => $log->reason,
                'created_at' => $log->created_at->toDateTimeString(),
            ])
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
