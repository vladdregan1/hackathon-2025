<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{


    private CategoryBudgetProvider $budgetProvider;
    private MonthlySummaryService $summaryService;

    public function __construct(
        CategoryBudgetProvider $budgetProvider,
        MonthlySummaryService $summaryService
    ) {
        $this->budgetProvider = $budgetProvider;
        $this->summaryService = $summaryService;
    }

    public function capitalizeKey(string $key): string {
        return ucfirst(strtolower($key));
    }


    public function generate(User $user, int $year, int $month): array
    {


        $budgets = $this->budgetProvider->getBudgets();
        $totals = $this->summaryService->computePerCategoryTotals($user, $year, $month);
        $alerts = [];

        foreach ($totals as $category => $amountSpent) {
            $categoryKey = $this->capitalizeKey($category);
            if (isset($budgets[$categoryKey]) && $amountSpent > $budgets[$categoryKey]) {
                $amountDifference = $amountSpent - $budgets[$categoryKey];
                $alerts[] = sprintf(
                    "%s budget exceeded by %.2f €",
                    $categoryKey,
                    $amountDifference
                );
            }
        }

        return $alerts;
    }
}
