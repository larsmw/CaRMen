<?php

namespace App\Command;

use App\Demo\DemoDataLoader;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Sets up the public demo: a shared admin login plus DemoDataLoader's sample data.
 * Expects an empty, migrated database (deploy/demo-reset.sh drops and migrates first).
 */
#[AsCommand(name: 'app:demo:load', description: 'Create the shared demo login and load demo data into an empty database')]
class DemoLoadCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private DemoDataLoader $demoData,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Demo login email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Demo login password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $email    = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');

        if ($email === '' || $password === '') {
            $io->error('--email and --password are required.');
            return Command::INVALID;
        }

        if ($this->em->getRepository(User::class)->count([]) > 0) {
            $io->error('The database already has users; app:demo:load only runs on an empty database.');
            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName('Demo')
            ->setLastName('User')
            ->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->demoData->load($user);
        $this->em->flush();

        $io->success("Demo login '{$email}' created and demo data loaded.");

        return Command::SUCCESS;
    }
}
