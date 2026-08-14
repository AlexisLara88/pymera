<?php

namespace App\Services;

final class EisenhowerClassifier
{
    public const DO_NOW   = 'do_now';
    public const SCHEDULE = 'schedule';
    public const DELEGATE = 'delegate';
    public const ELIMINATE = 'eliminate';

    public function classify(bool $isUrgent, bool $isImportant): string
    {
        if ($isUrgent && $isImportant) {
            return self::DO_NOW;
        }

        if ($isImportant) {
            return self::SCHEDULE;
        }

        if ($isUrgent) {
            return self::DELEGATE;
        }

        return self::ELIMINATE;
    }
}
