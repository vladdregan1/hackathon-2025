<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AlertGenerator;
use App\Domain\Service\ExpenseService;
use App\Domain\Service\MonthlySummaryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly AlertGenerator $alertGenerator,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->find($userId);

        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));

        $years = $this->expenseService->listExpenditureYears($user);

        $totalForMonth = 0;
        $totalForCategories = [];
        $alerts = [];
        if ($user !== null) {
            $totalForMonth = $this->monthlySummaryService->computeTotalExpenditure($user, $year, $month);
            $rawCategoryTotals = $this->monthlySummaryService->computePerCategoryTotals($user, $year, $month);
            $totalForCategories = $this->addCategoryPercentages($rawCategoryTotals, $totalForMonth);
            $avgCategoriesRaw = $this->monthlySummaryService->computePerCategoryAverages($user, $year, $month);
            $averagesCategories = $this->addCategoryPercentagesForAverages($avgCategoriesRaw);
            $alerts = $this->alertGenerator->generate($user, $year, $month);
        }

        return $this->render($response, 'dashboard.twig', array_merge([

            'alerts'                => $alerts,
            'totalForMonth'         => $totalForMonth,
            'totalsForCategories'   => $totalForCategories,
            'averagesForCategories' => $averagesCategories,
            'year' => $year,
            'month' => $month,
            'years' => $years,
        ],  $this->getCurrentUserData()));

    }

    private function addCategoryPercentages(array $categoryTotals, float $totalForMonth): array
    {
        $result = [];

        foreach ($categoryTotals as $category => $value) {
            if ($totalForMonth > 0) {
                $percentage = round(($value / $totalForMonth) * 100, 2);
            } else {
                $percentage = 0;
            }
            $result[$category] = [
                'value' => $value,
                'percentage' => $percentage,
            ];
        }
        return $result;
    }

    private function addCategoryPercentagesForAverages(array $categoryAverages): array
    {
        $result = [];
        $totalAverage = array_sum($categoryAverages);

        foreach ($categoryAverages as $category => $average) {
            if ($totalAverage > 0) {
                $percentage = round(($average / $totalAverage) * 100, 2);
            } else {
                $percentage = 0;
            }
            $result[$category] = [
                'value' => $average,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }
}
