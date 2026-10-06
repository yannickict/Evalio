<?php

namespace App\Console\Commands;

use App\Actions\SetEvaluationStatus;
use App\Models\CourseSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateEvaluationStatuses extends Command
{
    protected $signature = 'evaluations:update-statuses';

    protected $description = 'Open evaluations on their start date and close them 14 days after their end date';

    public function handle(SetEvaluationStatus $setStatus): int
    {
        $today = Carbon::today(config('evaluation.timezone'));
        $startDate = $today->toDateString();
        $endDate = $today->copy()->subDays(14)->toDateString();
        $changed = 0;

        CourseSession::query()
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereDate('start_date', $startDate)->orWhereDate('end_date', $endDate);
            })
            ->chunkById(100, function ($sessions) use ($setStatus, $startDate, &$changed): void {
                foreach ($sessions as $session) {
                    $status = $session->start_date->toDateString() === $startDate ? 'open' : 'closed';

                    if ($setStatus->handle($session, $status)) {
                        $changed++;
                    }
                }
            });

        $this->info("Updated $changed evaluation statuses.");

        return self::SUCCESS;
    }
}
