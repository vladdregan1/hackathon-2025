<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ExpenseService;
use App\Validators\ExpenseValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        // TODO: obtain logged-in user ID from session
        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->find($userId);
        $userData = $this->getCurrentUserData();

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));

        $years = $this->expenseService->listExpenditureYears($user);


        $totalCount = $this->expenseService->count($user, $year, $month);
        $totalPages = (int)ceil($totalCount / $pageSize);

        $expenses = $this->expenseService->list($user,$year, $month, $page, $pageSize);
        $flashMessage = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);



        return $this->render($response, 'expenses/index.twig', array_merge($userData, [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => $totalCount,
            'totalPages' => $totalPages,
            'year' => $year,
            'month' => $month,
            'years' => $years,
            'flash_message' => $flashMessage,
        ]));
    }


    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        $userData = $this->getCurrentUserData();

        $categories = $this->expenseService->getCategories();

        $data = array_merge($userData, [
            'categories' => $categories,
        ]);

        return $this->render($response, 'expenses/create.twig', $data);
    }





    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $data = (array)$request->getParsedBody();
        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->find($userId);

        $errors = ExpenseValidator::validateExpenseData($data);

        $amountFloat = (float)($data['amount'] ?? 0);
        $description = $data['description'] ?? '';
        $dateString = $data['date'] ?? '';
        $category = $data['category'] ?? '';
        $date = new \DateTimeImmutable($data['date']);
        $categories = $this->expenseService->getCategories();


        if (!empty($errors)) {
            $userData = $this->getCurrentUserData();
            $transferData = array_merge($userData, [
                'errors' => $errors,
                'categories' => $categories,
            ]);
            return $this->render($response, 'expenses/create.twig', $transferData);
        }

        try {
            $this->expenseService->create($user, $amountFloat, $description, $date, $category);
            return $response->withHeader('Location','/expenses')->withStatus(302);
        }catch (\Exception $e){
            $errors['general'] = 'An error occured while saving the expense.';
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $errors,
                'categories' => $categories,
            ]);
        }



    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expenseId = (int)($routeParams['id'] ?? 0);

        if ($expenseId <= 0) {
            return $response->withStatus(404);
        }

        $expense = $this->expenseService->getExpenseById($expenseId);

        if (!$expense){
            return $response->withStatus(404);
        }

        $userId = $_SESSION['user_id'] ?? null;
        if ($expense->userId !== $userId) {
            return $response->withStatus(403);
        }

        $categories = $this->expenseService->getCategories();


        $amountFloat = $expense->amountCents / 100;

        $userData = $this->getCurrentUserData();

        $categories = $this->expenseService->getCategories();

        $data =array_merge($userData,[
            'expense' => [
                'id' => $expense->id,
                'amount' => number_format($amountFloat, 2, '.', ''),
                'description' => $expense->description,
                'date' => $expense->date->format('Y-m-d'),
                'category' => $expense->category,
            ],
            'categories' => $categories,
        ]);




        return $this->render($response, 'expenses/edit.twig', $data);
    }

    private function renderEditWithErrors(Response $response, $expense, array $data, array $categories, array $errors): Response
    {
        $userData = $this->getCurrentUserData();
        $data = array_merge($userData, [
            'expense' => [
                'id' => $expense->id,
                'amount' => $data['amount'] ?? '',
                'description' => $data['description'] ?? '',
                'date' => $data['date'] ?? '',
                'category' => $data['category'] ?? '',
            ],
            'categories' => $categories,
            'errors' => $errors,
        ]);
        return $this->render($response, 'expenses/edit.twig', $data);
    }

    private function getExpenseIdFromRoute(array $routeParams): ?int
    {
        $expenseId = (int)($routeParams['id'] ?? 0);
        return $expenseId > 0 ? $expenseId : null;
    }

    private function isOwner($expense): bool
    {
        $userId = $_SESSION['user_id'] ?? null;
        return $expense->userId === $userId;
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $expenseId = $this->getExpenseIdFromRoute($routeParams);
        if ($expenseId === null) {
            return $response->withStatus(404);
        }

        $expense = $this->expenseService->getExpenseById($expenseId);
        if (!$expense){
            return $response->withStatus(404);
        }

        if (!$this->isOwner($expense)) {
            return $response->withStatus(403);
        }

        $data = (array)$request->getParsedBody();
        $errors = ExpenseValidator::validateExpenseData($data);
        $categories = $this->expenseService->getCategories();

        if (!empty($errors)) {
            return $this->renderEditWithErrors($response, $expense, $data, $categories, $errors);
        }

        $amountFloat = (float)($data['amount'] ?? 0);
        $description = $data['description'];
        $category = $data['category'];
        $date = new \DateTimeImmutable($data['date']);

        try {
            $this->expenseService->update($expense, $amountFloat, $description, $date, $category);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            $errors['general'] = 'An error occurred while updating the expense.';
            return $this->renderEditWithErrors($response, $expense, $data, $categories, $errors);
        }

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $expenseId = $this->getExpenseIdFromRoute($routeParams);
        $expense = $this->expenseService->getExpenseById($expenseId);

        if (!$this->isOwner($expense)){
            return $response->withStatus(403);
        }

        try {
            $this->expenseService->deleteExpense($expenseId);
            $_SESSION['flash_message'] = 'Expense deleted successfully.';
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Failed to delete expense.';
            $response->getBody()->write('An error occured while deleting the expense.');
            return $response->withStatus(500);
        }

        return $response;
    }

    private function validateCsvUpload(array $uploadedFiles, Response $response): ?Response
    {
        if (!isset($uploadedFiles['csv'])) {
            $response->getBody()->write("CSV file is required.");
            return $response->withStatus(400);
        }
        return null;
    }


    public function import(Request $request, Response$response, array $args): Response
    {

        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->find($userId);

        $uploadedFiles = $request->getUploadedFiles();

        $validationResponse = $this->validateCsvUpload($uploadedFiles, $response);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $csvFile = $uploadedFiles['csv'];

        try {
            $importedCount = $this->expenseService->importFromCsv($user, $csvFile);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            return $response->withHeader('Location', '/expenses/import')->withStatus(302);
        }
    }




}
