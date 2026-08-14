<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeMetricsService
{
    /**
     * @return array{
     *   total: int,
     *   active: int,
     *   registered: int,
     *   draft: int,
     *   submitted: int,
     *   verified: int,
     *   rejected: int,
     *   rejected_profiles: int
     * }
     */
    public function counts(): array
    {
        $counts = Employee::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN employment_status = 'aktif' THEN 1 ELSE 0 END) AS active")
            ->selectRaw('SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS registered')
            ->selectRaw("SUM(CASE WHEN verification_status = 'draft' THEN 1 ELSE 0 END) AS draft")
            ->selectRaw("SUM(CASE WHEN verification_status = 'submitted' THEN 1 ELSE 0 END) AS submitted")
            ->selectRaw("SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) AS verified")
            ->selectRaw("SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) AS rejected")
            ->selectRaw("SUM(CASE WHEN profile_review_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_profiles")
            ->first();

        return [
            'total' => (int) $counts?->total,
            'active' => (int) $counts?->active,
            'registered' => (int) $counts?->registered,
            'draft' => (int) $counts?->draft,
            'submitted' => (int) $counts?->submitted,
            'verified' => (int) $counts?->verified,
            'rejected' => (int) $counts?->rejected,
            'rejected_profiles' => (int) $counts?->rejected_profiles,
        ];
    }
}
