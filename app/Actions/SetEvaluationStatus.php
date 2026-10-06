<?php

namespace App\Actions;

use App\Models\CourseSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SetEvaluationStatus
{
    public function handle(CourseSession $courseSession, string $status): bool
    {
        return retry(
            5,
            function () use ($courseSession, $status): bool {
                return DB::transaction(function () use ($courseSession, $status): bool {
                    $session = CourseSession::query()->lockForUpdate()->findOrFail($courseSession->id);

                    if ($session->evaluation_status === $status) {
                        return false;
                    }

                    $session->update(['evaluation_status' => $status]);

                    if ($status === 'closed') {
                        $session->feedbackForm()->whereNotNull('code')->update(['code' => null]);
                    } else {
                        $form = $session->feedbackForm()->firstOrCreate([]);

                        if ($form->code === null) {
                            $form->update(['code' => $this->generateCode()]);
                        }
                    }

                    return true;
                }, 3);
            },
            0,
            fn (\Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException
        );
    }

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
