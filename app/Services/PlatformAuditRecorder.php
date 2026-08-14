<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformAuditEventModel;
use CodeIgniter\I18n\Time;
use RuntimeException;

final class PlatformAuditRecorder
{
    public function __construct(private ?PlatformAuditEventModel $events = null)
    {
        $this->events ??= model(PlatformAuditEventModel::class);
    }

    public function record(
        string $subjectType,
        int $subjectId,
        string $action,
        ?int $actorUserId = null,
    ): int {
        $eventId = $this->events->insert([
            'actor_user_id' => $actorUserId,
            'subject_type'  => $subjectType,
            'subject_id'    => $subjectId,
            'action'        => $action,
            'occurred_at'   => Time::now('UTC')->toDateTimeString(),
        ], true);

        if ($eventId === false) {
            throw new RuntimeException('No fue posible registrar la operación administrativa.');
        }

        return (int) $eventId;
    }
}
