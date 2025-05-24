<?php

namespace App\Validators;

class ExpenseValidator
{
    public static function validateAmount($amount): ?string
    {
        if (!is_numeric($amount) || (float)$amount <= 0) {
            return 'Amount must be a positive number.';
        }
        return null;
    }

    public static function validateCategory($category): ?string
    {
        if (empty($category)) {
            return 'Category is required.';
        }
        return null;
    }

    public static function validateDescription($description): ?string
    {
        if (empty($description)) {
            return 'Description is required.';
        }
        return null;
    }

    public static function validateDate($dateString): ?string
    {
        try {
            $date = new \DateTimeImmutable($dateString);
            $today = new \DateTimeImmutable('today');
            if ($date > $today) {
                return 'Date cannot be in the future.';
            }
        } catch (\Exception) {
            return 'Invalid date format.';
        }
        return null;
    }

    public static function validateExpenseData(array $data): array
    {
        $errors = [];
        if ($error = self::validateAmount($data['amount'] ?? null)) {
            $errors['amount'] = $error;
        }
        if ($error = self::validateCategory($data['category'] ?? null)) {
            $errors['category'] = $error;
        }
        if ($error = self::validateDescription($data['description'] ?? null)) {
            $errors['description'] = $error;
        }
        if ($error = self::validateDate($data['date'] ?? null)) {
            $errors['date'] = $error;
        }
        return $errors;
    }


}