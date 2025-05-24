<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Infrastructure\Persistence\PdoUserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly PdoUserRepository $pdoUserRepository,
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
        $user = $this->pdoUserRepository->find($userId);
        $userData = $this->getCurrentUserData();

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));

        $years = $this->expenseService->listExpenditureYears($user);


        $totalCount = $this->expenseService->count($user, $year, $month);
        $totalPages = (int)ceil($totalCount / $pageSize);

        $expenses = $this->expenseService->list($user,$year, $month, $page, $pageSize);



        return $this->render($response, 'expenses/index.twig', array_merge($userData, [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => $totalCount,
            'totalPages' => $totalPages,
            'year' => $year,
            'month' => $month,
            'years' => $years,
        ]));
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        $userData = $this->getCurrentUserData();

        $categories = [];

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
        $user = $this->pdoUserRepository->find($userId);

        $errors = [];
        $amount = (float)$data['amount'] ?? null;
        $description = $data['description'] ?? '';
        $dateString = $data['date'] ?? '';
        $category = $data['category'] ?? '';

        if (!is_numeric($amount) || (float)$amount <= 0) {
            $errors['amount'] = 'Amount must be a positive number.';
        }

        try {
            $date = new \DateTimeImmutable($dateString);
        } catch (\Exception) {
            $errors['date'] = 'Invalid date format.';
        }

        if (empty($category)) {
            $errors['category'] = 'Category is required.';
        }

        if (!empty($errors)) {
            return $this->render($response, 'expenses/create.twig', [
               'errors' => $errors,
            ]);
        }

        try {
            $this->expenseService->create($user, $amount, $description, $date, $category);
            return $response->withHeader('Location','/expenses');
        }catch (\Exception $e){
            $errors['general'] = 'An error occured while saving the expense.';
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $errors,
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

        $expense = ['id' => 1];

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => []]);
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

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        return $response;
    }

}
