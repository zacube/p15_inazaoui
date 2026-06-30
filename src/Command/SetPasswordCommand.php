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
    name: 'app:set-password',
    description: 'Initialise le mot de passe de tous les utilisateurs non-admin',
)]
class SetPasswordCommand extends Command
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument('password', InputArgument::REQUIRED, 'Le mot de passe générique en clair');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plainPassword = $input->getArgument('password');

        $users = $this->entityManager->getRepository(User::class)->findBy(['admin' => false]);
        foreach ($users as $user){
            $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
        }
        $this->entityManager->flush();

        $io->success(sprintf('%d utilisateurs mis à jour.', count($users)));

        return Command::SUCCESS;
    }
}
