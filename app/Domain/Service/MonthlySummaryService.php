<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        // TODO: compute expenses total for year-month for a given user
        return $this->expenses->sumAmounts([
           'user_id' => $user->id,
           'year' => $year,
           'month' => $month,
        ]);

    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        // TODO: compute totals for year-month for a given user
        return $this->expenses->sumAmountsByCategory([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user
        return $this->expenses->averageAmountsByCategory([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
