<?php

namespace App\Fixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'user-admin';
    public const USER_REFERENCE_PREFIX = 'user-invite-';
    public const NB_INVITES = 5;

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setName('Ina Zaoui')
            ->setEmail('ina@zaoui.com')
            ->setAdmin(true)
            ->setOwner(true)
            ->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);
        $this->addReference(self::ADMIN_REFERENCE, $admin);

        for ($i = 1; $i <= 5; ++$i) {
            $guest = new User();
            $guest->setName('Invité '.$i)
                ->setEmail('invite+'.$i.'@example.com')
                ->setAdmin(false)
                ->setPassword($this->passwordHasher->hashPassword($guest, 'aze'));
            $manager->persist($guest);
            $this->addReference(self::USER_REFERENCE_PREFIX.$i, $guest);
        }

        $manager->flush();
    }
}
