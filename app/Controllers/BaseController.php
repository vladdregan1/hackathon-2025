<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class BaseController
{
    public function __construct(
        protected Twig $view,
    ) {}

    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    public function getCurrentUserData(): array
    {
        return [
            'currentUserId' => $_SESSION['user_id'] ?? null,
            'currentUserName' => $_SESSION['username'] ?? null,
        ];
    }
}
