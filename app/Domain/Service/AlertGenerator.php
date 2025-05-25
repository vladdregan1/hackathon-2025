<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.

    private CategoryBudgetProvider $budgetProvider;
    private MonthlySummaryService $summaryService;

    public function __construct(
        CategoryBudgetProvider $budgetProvider,
        MonthlySummaryService $summaryService
    ) {
        $this->budgetProvider = $budgetProvider;
        $this->summaryService = $summaryService;
    }

    function capitalizeKey(string $key): string {
        return ucfirst(strtolower($key));
    }


    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category

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
