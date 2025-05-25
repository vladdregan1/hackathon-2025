<?php

namespace App\Domain\Service;

class CategoryBudgetProvider
{
    private array $budgets;

    public function __construct(string $jsonBudgets)
    {
        $this->budgets = json_decode($jsonBudgets, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getBudgets(): array
    {
        return $this->budgets;
    }

}