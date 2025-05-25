<?php




declare(strict_types=1);

namespace App;

use App\Domain\Service\AlertGenerator;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\CategoryBudgetProvider;
use App\Infrastructure\Persistence\PdoExpenseRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PDO;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

use function DI\autowire;
use function DI\factory;

class Kernel
{
    public static function createApp(): App
    {
        // Configure the DI container builder and build the DI container
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);  // Enable autowiring explicitly

        $builder->addDefinitions([
            // Define a factory for the Monolog logger with a stream handler that writes to var/app.log
            LoggerInterface::class            => function () {
                $logger = new Logger('app');
                $logger->pushHandler(new StreamHandler(__DIR__.'/../var/app.log', Level::Debug));

                return $logger;
            },

            // Define a factory for Twig view renderer
            Twig::class                       => function () {
                return Twig::create(__DIR__.'/../templates', ['cache' => false]);
            },

            // Define a factory for PDO database connection
            PDO::class                        => factory(function () {
                static $pdo = null;
                if ($pdo === null) {
                    $dbPath = realpath(__DIR__ . '/../' . $_ENV['DB_PATH']);
                    $pdo = new PDO('sqlite:' . $dbPath);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                }

                return $pdo;
            }),

            // Map interfaces to concrete implementations
            UserRepositoryInterface::class    => autowire(PdoUserRepository::class),
            ExpenseRepositoryInterface::class => autowire(PdoExpenseRepository::class),

            CategoryBudgetProvider::class => function () {
                return new CategoryBudgetProvider($_ENV['CATEGORIES_BUDGETS']);
            },

            MonthlySummaryService::class => function($c) {
                return new MonthlySummaryService($c->get(ExpenseRepositoryInterface::class));
            },

            AlertGenerator::class => function($c) {
                return new AlertGenerator(
                    $c->get(CategoryBudgetProvider::class),
                    $c->get(MonthlySummaryService::class),
                );
            },


        ]);
        $container = $builder->build();

        // Create an application instance and configure
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        $app->add(TwigMiddleware::createFromContainer($app, Twig::class));
        (require __DIR__.'/../config/settings.php')($app);
        (require __DIR__.'/../config/routes.php')($app);

        // TODO: Handle session initialization

        // Make current user ID globally available to twig templates
        // TODO: change the following line to set the user ID stored in the session, for when user is logged
        $loggedInUserId = null;
        $twig = $container->get(Twig::class);
        $twig->getEnvironment()->addGlobal('currentUserId', $loggedInUserId);


        return $app;
    }
}
