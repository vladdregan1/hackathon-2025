<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        $params = [
            'user_id' => $expense->userId,
            'date' => $expense->date->format('Y-m-d H:i:s'),
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description,
        ];

        if ($expense->id === null) {

            $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                    VALUES (:user_id, :date, :category, :amount_cents, :description)';

            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
        }else {
            $query = 'UPDATE expenses SET user_id = :user_id, date = :date, category = :category,
                      amount_cents = :amount_cents, description = :description WHERE id = :id';

            $params['id'] = $expense->id;
            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
        }
    }


    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        $query = "SELECT * FROM expenses WHERE user_id = :user_id";
        $params = ['user_id' => $criteria['user_id']];

        if (isset($criteria['year'])) {
            $query .= " AND strftime('%Y', date) = :year";
            $params['year'] = (string)$criteria['year'];
        }
        if (isset($criteria['month'])) {

            $query .= " AND strftime('%m', date) = :month";
            $params['month'] = str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT);
        }

        $query .= " ORDER BY date DESC LIMIT :limit OFFSET :offset";

        $statement = $this->pdo->prepare($query);

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $from, PDO::PARAM_INT);

        foreach ($params as $key => $val) {
            $statement->bindValue(":$key", $val);
        }

        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createExpenseFromData($row);
        }
        return $results;
    }



    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.

        $query = "SELECT COUNT(*) FROM expenses WHERE user_id = :user_id AND strftime('%Y', date) = :year AND strftime('%m', date) = :month";
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $criteria['user_id'],
            'year' => $criteria['year'],
            'month' => str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT),
        ]);
        return (int)$statement->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        $query = "SELECT DISTINCT strftime('%Y', date) AS year
              FROM expenses
              WHERE user_id = :user_id
              ORDER BY year DESC";

        $statement = $this->pdo->prepare($query);
        $statement->execute(['user_id' => $user->id]);

        $years = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $years[] = (int)$row['year'];
        }

        return $years;
    }

    private function applyDateCriteria(string $query, array $params, array $criteria): array
    {
        if (isset($criteria['year'])) {
            $query .= " AND strftime('%Y', date) = :year";
            $params['year'] = (string)$criteria['year'];
        }

        if (isset($criteria['month'])) {
            $query .= " AND strftime('%m', date) = :month";
            $params['month'] = str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT);
        }

        return [$query, $params];
    }

    private function fetchCategoryAmounts(\PDOStatement $statement, string $valueColumn): array
    {
        $results = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['category']] = (float)($row[$valueColumn] / 100);
        }
        return $results;
    }


    private function bindParams(\PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $val) {
                $statement->bindValue(":$key", $val);
        }
    }



    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $query = "SELECT category, SUM(amount_cents) AS total_cents FROM expenses WHERE user_id = :user_id";
        $params = ['user_id' => $criteria['user_id']];

        [$query, $params] = $this->applyDateCriteria($query, $params, $criteria);

        $query .= " GROUP BY category";
        $statement = $this->pdo->prepare($query);

        $this->bindParams($statement, $params);

        $statement->execute();
        return $this->fetchCategoryAmounts($statement, 'total_cents');
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        $query = "SELECT category, AVG(amount_cents) AS average_cents FROM expenses WHERE user_id = :user_id";
        $params = ['user_id' => $criteria['user_id']];

        [$query, $params] = $this->applyDateCriteria($query, $params, $criteria);

        $query .= " GROUP BY category";
        $statement = $this->pdo->prepare($query);
        $this->bindParams($statement, $params);
        $statement->execute();

        return $this->fetchCategoryAmounts($statement, 'average_cents');
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        $query = "SELECT SUM(amount_cents) AS total_cents FROM expenses WHERE user_id = :user_id";
        $params = ['user_id' => $criteria['user_id']];

        [$query, $params] = $this->applyDateCriteria($query, $params, $criteria);

        $statement = $this->pdo->prepare($query);

        $this->bindParams($statement, $params);

        $statement->execute();
        $totalCents = $statement->fetchColumn();

        if ($totalCents === null) {
            return 0;
        }

        return $totalCents/100;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
