<?php

namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(UserRepository::class);
    }

    public function testFindOneByAdminReturnsAdminUser(): void
    {
        $admin = $this->createUser(admin: true, email: 'admin-test@example.com');

        $result = $this->repository->findOneByAdmin(true);

        $this->assertNotNull($result);
        $this->assertTrue($result->isAdmin());
    }

    public function testFindOneByAdminReturnsNonAdminUser(): void
    {
        $this->createUser(admin: false, email: 'user-test@example.com');

        $result = $this->repository->findOneByAdmin(false);

        $this->assertNotNull($result);
        $this->assertFalse($result->isAdmin());
    }

    public function testUpgradePasswordUpdatesHashedPassword(): void
    {
        $user = $this->createUser(admin: false, email: 'upgrade-test@example.com');
        $newHashedPassword = 'nouveau_hash_bcrypt';

        $this->repository->upgradePassword($user, $newHashedPassword);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->refresh($user);

        $this->assertSame($newHashedPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForNonUserInstance(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $fakeUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->repository->upgradePassword($fakeUser, 'peu-importe');
    }

    private function createUser(bool $admin, string $email): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setPassword('faux_password_hashé');
        $user->setAdmin($admin);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
