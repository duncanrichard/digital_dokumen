<?php

namespace App\Services;

use App\Events\DocumentNotificationUpdated;
use App\Models\Document;
use App\Models\User;

class DocumentNotificationBroadcaster
{
    public function broadcast(Document $document): void
    {
        $document->loadMissing(['distributedDepartments:id', 'distributedUsers:id']);

        $departmentIds = $document->distributedDepartments->pluck('id')
            ->push($document->department_id)
            ->filter()
            ->unique();

        $userIds = User::where('is_active', true)
            ->whereIn('department_id', $departmentIds)
            ->pluck('id')
            ->merge($document->distributedUsers->pluck('id'))
            ->unique();

        foreach ($userIds as $userId) {
            rescue(
                fn () => DocumentNotificationUpdated::dispatch((string) $userId, (string) $document->id),
                report: true
            );
        }
    }
}
