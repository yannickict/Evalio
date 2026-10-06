<?php

namespace App\Console\Commands;

use App\Actions\SetEvaluationStatus;
use App\Models\CourseSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateEvaluationStatuses extends Command
{
    protected $signature = 'evaluations:update-statuses';

    protected $description = 'Open due and unset evaluations, and close them 14 days after their end date';

    public function handle(SetEvaluationStatus $setStatus): int
    {
        $today = Carbon::today(config('evaluation.timezone'));
        $startDate = $today->toDateString();
        $endDate = $today->copy()->subDays(14)->toDateString();
        $changed = 0;

        CourseSession::query()
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereDate('start_date', $startDate)->orWhereDate('end_date', $endDate);
                $query->orWhere(function ($query) use ($startDate, $endDate): void {
                    $query->whereNull('evaluation_status')
                        ->whereDate('start_date', '<=', $startDate)
                        ->whereDate('end_date', '>', $endDate);
                });
            })
            ->chunkById(100, function ($sessions) use ($setStatus, $endDate, &$changed): void {
                foreach ($sessions as $session) {
                    $status = $session->end_date->toDateString() === $endDate ? 'closed' : 'open';

                    if ($setStatus->handle($session, $status)) {
                        $changed++;
                    }
                }
            });

        $this->info("Updated $changed evaluation statuses.");

        return self::SUCCESS;
    }
}
