<?php

namespace App\Controller;

use App\Demo\DemoDataLoader;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * First-run installer. Database settings come from DATABASE_URL; the installer
 * only creates the schema and the first admin. The app counts as installed as
 * soon as any user exists, after which every endpoint except /status is locked.
 */
#[Route('/api/install')]
class InstallationController extends AbstractController
{
    private const MIN_PHP_VERSION = '8.2';
    private const REQUIRED_EXTENSIONS = ['pdo_pgsql', 'ctype', 'iconv', 'json', 'tokenizer', 'intl'];

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private DependencyFactory $migrations,
    ) {}

    #[Route('/status', name: 'api_install_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        try {
            return $this->json(['installed' => $this->isInstalled()]);
        } catch (\Throwable) {
            // Database unreachable: not something the installer can fix, but the
            // frontend should still be able to show the installer's system check.
            return $this->json(['installed' => false]);
        }
    }

    #[Route('/system-check', name: 'api_install_system_check', methods: ['GET'])]
    public function systemCheck(): JsonResponse
    {
        if ($locked = $this->lockedResponse(failOpen: true)) {
            return $locked;
        }

        $phpVersion   = PHP_VERSION;
        $phpVersionOk = version_compare($phpVersion, self::MIN_PHP_VERSION, '>=');

        $missingExtensions = array_values(array_filter(
            self::REQUIRED_EXTENSIONS,
            fn (string $ext) => !extension_loaded($ext),
        ));

        $dbConnectionOk = false;
        $dbError        = null;
        try {
            $this->connection->executeQuery('SELECT 1');
            $dbConnectionOk = true;
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        return $this->json([
            'phpVersion'         => $phpVersion,
            'minPhpVersion'      => self::MIN_PHP_VERSION,
            'phpVersionOk'       => $phpVersionOk,
            'requiredExtensions' => self::REQUIRED_EXTENSIONS,
            'missingExtensions'  => $missingExtensions,
            'dbConnectionOk'     => $dbConnectionOk,
            'dbError'            => $dbError,
            'pendingMigrations'  => $dbConnectionOk ? $this->pendingMigrationCount() : null,
            'systemOk'           => $phpVersionOk && !$missingExtensions && $dbConnectionOk,
        ]);
    }

    #[Route('/migrate', name: 'api_install_migrate', methods: ['POST'])]
    public function migrate(KernelInterface $kernel): JsonResponse
    {
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }

        $app = new Application($kernel);
        $app->setAutoExit(false);
        $output = new BufferedOutput();
        $code = $app->run(new ArrayInput([
            'command'             => 'doctrine:migrations:migrate',
            '--no-interaction'    => true,
            '--allow-no-migration' => true,
        ]), $output);

        if ($code !== 0) {
            return $this->json(['error' => 'Migrations failed', 'output' => $output->fetch()], 500);
        }

        return $this->json(['pendingMigrations' => $this->pendingMigrationCount()]);
    }

    #[Route('/create-admin', name: 'api_install_create_admin', methods: ['POST'])]
    public function createAdmin(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        DemoDataLoader $demoData,
    ): JsonResponse {
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $user = new User();
        $user->setEmail(trim((string) ($data['email'] ?? '')));
        $user->setFirstName(trim((string) ($data['firstName'] ?? '')));
        $user->setLastName(trim((string) ($data['lastName'] ?? '')));
        $user->setPlainPassword((string) ($data['password'] ?? ''));
        $user->setRoles(['ROLE_ADMIN']);

        $errors = [];
        foreach ($validator->validate($user) as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        if (strlen($user->getPlainPassword()) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if ($errors) {
            return $this->json(['errors' => $errors], 422);
        }

        $user->setPassword($hasher->hashPassword($user, $user->getPlainPassword()));
        $user->eraseCredentials();

        // Lock the user table so two concurrent requests can't both create the first admin.
        $loadDemoData = filter_var($data['demoData'] ?? false, FILTER_VALIDATE_BOOL);

        $created = $em->wrapInTransaction(function (EntityManagerInterface $em) use ($user, $loadDemoData, $demoData): bool {
            $this->connection->executeStatement('LOCK TABLE "user" IN EXCLUSIVE MODE');
            if ($this->isInstalled()) {
                return false;
            }
            $em->persist($user);
            if ($loadDemoData) {
                $demoData->load($user);
            }
            $em->flush();
            return true;
        });

        if (!$created) {
            return $this->json(['error' => 'Application is already installed'], 403);
        }

        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
        ], 201);
    }

    private function isInstalled(): bool
    {
        try {
            return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM "user"') > 0;
        } catch (TableNotFoundException) {
            return false;
        }
    }

    /**
     * If we can't tell whether the app is installed (e.g. the database is down),
     * mutating endpoints fail closed. The read-only system check is let through
     * so it can report the database problem.
     */
    private function lockedResponse(bool $failOpen = false): ?JsonResponse
    {
        try {
            $installed = $this->isInstalled();
        } catch (\Throwable) {
            return $failOpen ? null : $this->json(['error' => 'Database unavailable'], 503);
        }

        return $installed ? $this->json(['error' => 'Application is already installed'], 403) : null;
    }

    private function pendingMigrationCount(): int
    {
        return count($this->migrations->getMigrationStatusCalculator()->getNewMigrations());
    }
}
