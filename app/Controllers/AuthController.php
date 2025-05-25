<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use App\Validators\AuthValidator;
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

        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }


    public function getUserData(Request $request)
    {
        $data = (array) $request->getParsedBody();
        return [
            'username' => trim($data['username'] ?? ''),
            'password' => trim($data['password'] ?? ''),
            'password_confirm' => trim($data['password_confirm'] ?? ''),
        ];
    }

    public function register(Request $request, Response $response): Response
    {
        $userData = $this->getUserData($request);
        $username = $userData['username'];
        $password = $userData['password'];
        $passwordConfirm = $userData['password_confirm'];

        $errors = AuthValidator::validateAuthData($userData);

        if ($password != $passwordConfirm){
            $errors['password_confirm'] = 'Passwords do not match.';
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

        $userData = $this->getUserData($request);
        $username = $userData['username'];
        $password = $userData['password'];

        $errors = [];

        if (!$this->authService->attempt($username, $password)) {
            $errors['general'] = 'Invalid username or password.';
            return $this->render($response, 'auth/login.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }


        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {

        session_destroy();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
