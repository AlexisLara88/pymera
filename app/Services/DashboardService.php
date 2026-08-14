<?php

declare(strict_types=1);

namespace App\Services;

final class DashboardService
{
    public function __construct(
        private ?BusinessService $businesses = null,
        private ?ObjectiveService $workflow = null,
        private ?FinanceService $finances = null,
    ) {
        $this->businesses ??= new BusinessService();
        $this->workflow   ??= new ObjectiveService();
        $this->finances   ??= new FinanceService();
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $business = $this->businesses->details();

        if (! $business['minimum_profile_complete']) {
            return [
                ...$business,
                'requires_onboarding' => true,
            ];
        }

        $workflow  = $this->workflow->overview();
        $finance   = $this->finances->overview();
        $activities = $workflow['activities'];
        $activeActivities = array_values(array_filter(
            $activities,
            static fn (array $activity): bool => $activity['status'] !== 'cancelled',
        ));
        $openActivities = array_values(array_filter(
            $activeActivities,
            static fn (array $activity): bool => in_array(
                $activity['status'],
                ['pending', 'in_progress'],
                true,
            ),
        ));
        $completedActivities = count(array_filter(
            $activeActivities,
            static fn (array $activity): bool => $activity['status'] === 'completed',
        ));
        $activityProgress = $activeActivities === []
            ? 0
            : (int) round(($completedActivities / count($activeActivities)) * 100);
        $prioritySummary = array_fill_keys(
            ['do_now', 'schedule', 'delegate', 'eliminate'],
            0,
        );

        foreach ($openActivities as &$activity) {
            $activity['is_overdue'] = ! empty($activity['due_date'])
                && $activity['due_date'] < $finance['today'];
            $prioritySummary[$activity['quadrant']]++;
        }
        unset($activity);

        $quadrantOrder = [
            'do_now'    => 0,
            'schedule'  => 1,
            'delegate'  => 2,
            'eliminate' => 3,
        ];
        usort($openActivities, static function (array $left, array $right) use ($quadrantOrder): int {
            $overdueOrder = ((int) ! $left['is_overdue']) <=> ((int) ! $right['is_overdue']);

            if ($overdueOrder !== 0) {
                return $overdueOrder;
            }

            $quadrantComparison = ($quadrantOrder[$left['quadrant']] ?? 4)
                <=> ($quadrantOrder[$right['quadrant']] ?? 4);

            if ($quadrantComparison !== 0) {
                return $quadrantComparison;
            }

            return ($left['due_date'] ?? '9999-12-31') <=> ($right['due_date'] ?? '9999-12-31');
        });

        return [
            ...$business,
            'requires_onboarding' => false,
            'workflow_summary' => [
                ...$workflow['workflow_summary'],
                'open_activities'      => count($openActivities),
                'completed_activities' => $completedActivities,
                'progress_percent'     => $activityProgress,
            ],
            'priority_summary'   => $prioritySummary,
            'next_actions'       => array_slice($openActivities, 0, 5),
            'featured_objective' => $workflow['featured_objective'],
            'finance_period'     => $finance['period'],
            'finance_period_label' => $finance['period_label'],
            'finance_totals'     => $finance['totals'],
            'finance_summary'    => $finance['finance_summary'],
            'finance_chart_entries' => $finance['chart_entries'],
        ];
    }
}
