<?php

namespace App\Validators;

class AuthValidator
{
    public static function validateUsername($username): ?string
    {
        if (strlen($username) < 4) {
            return 'Username must be at least 4 characters long.';
        }
        return null;
    }

    public static function validatePasswordNbOfCharacters($password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }
        return null;
    }

    public static function validatePasswordNumbers($password): ?string
    {
        if (!preg_match('/\d/', $password)) {
            return 'Password must contain at least 1 number.';
        }
        return null;
    }

    public static function validateAuthData($data): array
    {
        $errors = [];
        if ($error = self::validateUsername($data['username'] ?? null)) {
            $errors['username'] = $error;
        }

        if ($error = self::validatePasswordNbOfCharacters($data['password'] ?? null)) {
            $errors['password'] = $error;
        }

        if ($error = self::validatePasswordNumbers($data['password'] ?? null)) {
            $errors['password'] = $error;
        }

        return $errors;
    }

}