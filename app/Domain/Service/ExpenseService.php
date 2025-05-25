<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Validators\ExpenseValidator;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
        private LoggerInterface $logger,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {

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

        $handle = $this->prepareStream($csvFile);
        $importedCount = 0;
        $seenRows = [];
        $skippedRows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->processCsvRow($user, $row, $skippedRows, $seenRows)) {
                    $importedCount++;
                }
            }

            $this->logSkippedRows($skippedRows);
            $this->logger->info("Import finished. Total imported: {$importedCount}");
        } finally {
            fclose($handle);
        }
        return $importedCount;
    }

    private function processCsvRow(User $user, array $row, array &$skippedRows, array &$seenRows): bool
    {
        if ($this->isEmptyRow($row)) {
            $skippedRows[] = ['reason' => 'empty row', 'row' => $row];
            return false;
        }
        $hash = $this->hashRow($row);
        if (isset($seenRows[$hash])) {
            $skippedRows[] = ['reason' => 'duplicate row', 'row' => $row];
            return false;
        }
        $seenRows[$hash] = true;

        $data = $this->parseCsvRow($row);


        if (!$this->isValidCategory($data['category'])) {
            $skippedRows[] = ['reason' => 'invalid category', 'row' => $row];
            return false;
        }
        if (!$this->validateExpenseData($data)) {
            $skippedRows[] = ['reason' => 'invalid data', 'row' => $row];
            return false;
        }

        $this->createExpenseFromData($user, $data);
        return true;
    }

    private function logSkippedRows(array $skippedRows): void
    {
        foreach ($skippedRows as $entry) {
            $this->logger->warning('Skipped CSV row', [
                'reason' => $entry['reason'],
                'row' => array_map('trim', $entry['row']),
            ]);
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter($row)) === 0;
    }

    private function parseCsvRow(array $row): array
    {
        [$date, $description, $amount, $category] = array_map('trim', $row + [null, null, null, null]);

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'category' => $category,
        ];
    }

    private function validateExpenseData(array $data): bool
    {
        $errors = ExpenseValidator::validateExpenseData($data);
        return empty($errors);
    }

    private function createExpenseFromData(User $user, array $data): void
    {
        $dateObj = new DateTimeImmutable($data['date']);
        $amountFloat = (float)$data['amount'];
        $this->create($user, $amountFloat, $data['description'], $dateObj, $data['category']);
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

    public function deleteExpense(int $expenseId): void
    {
        $this->expenses->delete($expenseId);
    }

    public function getCategories(): array
    {
        return [
            'groceries' => 'Groceries',
            'utilities' => 'Utilities',
            'transport' => 'Transport',
            'entertainment' => 'Entertainment',
            'housing' => 'Housing',
            'health' => 'Healthcare',
            'other' => 'Other',
        ];

    }

    private function prepareStream(UploadedFileInterface $csvFile)
    {
        $stream = $csvFile->getStream();
        $handle = fopen('php://temp', 'r+');
        $source = $stream->detach();
        stream_copy_to_stream($source, $handle);
        rewind($handle);
        return $handle;
    }

    private function isValidCategory(string $category): bool
    {
        static $categories = null;
        if ($categories === null) {
            $categories = $this->getCategories();
        }

        return in_array($category, $categories, true);
    }

    private function hashRow(array $row): string
    {
        $normalizedRow = array_map('trim', $row);
        return md5(json_encode($normalizedRow));
    }



}
