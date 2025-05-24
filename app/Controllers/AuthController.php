<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration
        $data = (array) $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        }

        if (empty($password)){
            $errors['password'] = 'Password is required.';
        }

        if (strlen($username) < 4) {
            $errors['username'] = 'Username must be at least 4 characters long.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }

        if (!preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must contain at least 1 number.';
        }

        if (!empty($errors)){
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username,
                'password' => $password
            ]);
        }

        try {
            $this->authService->register($username, $password);
            $this->logger->info('User registered: ' . $username);


            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\Exception $e) {
            $this->logger->error('Registration error: ' . $e->getMessage());
            $errors['username'] = $e->getMessage();

            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username,
                'password' => $password
            ]);
        }
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
