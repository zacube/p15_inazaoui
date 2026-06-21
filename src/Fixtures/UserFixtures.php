<?php

namespace App\Fixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'user-admin';
    public const USER_REFERENCE_PREFIX = 'user-invite-';
    public const NB_INVITES = 5;
    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setName('Ina Zaoui')
            ->setEmail('ina@zaoui.com')
            ->setAdmin(true);
        $manager->persist($admin);
        $this->addReference(self::ADMIN_REFERENCE, $admin);

        for ($i = 1; $i <= 5; $i++) {
            $guest = (new User())
                ->setName('Invité ' . $i)
                ->setEmail('invite+' . $i . '@example.com')
                ->setAdmin(false);
            $manager->persist($guest);
            $this->addReference(self::USER_REFERENCE_PREFIX . $i, $guest);
        }

        $manager->flush();
    }
}