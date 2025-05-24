<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Validators\ExpenseValidator;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses
        $offset = ($pageNumber - 1) * $pageSize;

        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        return $this->expenses->findBy($criteria, $offset, $pageSize);
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with

        $data = [
            'amount' => $amount,
            'category' => $category,
            'description' => $description,
            'date' => $date->format('Y-m-d'),
        ];

        $errors = ExpenseValidator::validateExpenseData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid data provided for update.');
        }
        $amountCents = (int) round($amount * 100);
        $expense = new Expense(null, $user->id, $date, $category, $amountCents, $description);

        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist

        $data = [
            'amount' => $amount,
            'category' => $category,
            'description' => $description,
            'date' => $date->format('Y-m-d'),
        ];

        $errors = ExpenseValidator::validateExpenseData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid data provided for update.');
        }
        $expense->amountCents = (int)round($amount * 100);
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;

        $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }

    public function count(User $user, int $year, int $month): int
    {
        return $this->expenses->countBy([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function listExpenditureYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }

    public function getExpenseById (int $id): Expense
    {
        return $this->expenses->find($id);
    }

}
