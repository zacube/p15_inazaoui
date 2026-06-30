<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:set-admin-password',
    description: 'Initialise le mot de passe pour un admin (email)',
)]
class SetAdminPasswordCommand extends Command
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('password', InputArgument::REQUIRED, 'Le mot de passe générique en clair')
            ->addArgument('email', InputArgument::REQUIRED, 'L\'email de l\'admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$admin) {
            $io->error(sprintf('Aucun utilisateur trouvé pour %s', $email));

            return Command::FAILURE;
        }
        $hashed = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashed);

        $this->entityManager->flush();

        $io->success(sprintf('administrateur %s mis à jour.', $admin->getEmail()));

        return Command::SUCCESS;
    }
}
