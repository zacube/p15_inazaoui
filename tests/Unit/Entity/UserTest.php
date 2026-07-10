<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesReturnsRoleUserByDefault(): void
    {
        $user = new User();

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testGetRolesIncludesRoleAdminWhenAdminIsTrue(): void
    {
        $user = new User();
        $user->setAdmin(true);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesDoesNotDuplicateWhenAdminSetTwice(): void
    {
        $user = new User();
        $user->setAdmin(true);
        $user->setAdmin(true);

        $roles = $user->getRoles();

        $this->assertCount(2, $roles); // array_unique doit empêcher tout doublon
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('ina@zaoui.com');

        $this->assertSame('ina@zaoui.com', $user->getUserIdentifier());
    }

    public function testGetUsernameIsAliasForGetUserIdentifier(): void
    {
        $user = new User();
        $user->setEmail('ina@zaoui.com');

        $this->assertSame($user->getUserIdentifier(), $user->getUsername());
    }

    public function testGetSaltReturnsNull(): void
    {
        $user = new User();

        $this->assertNull($user->getSalt());
    }

    public function testEraseCredentialsDoesNotThrow(): void
    {
        $user = new User();

        // Corps vide : on vérifie juste qu'aucune exception n'est levée
        $user->eraseCredentials();

        $this->addToAssertionCount(1);
    }

    public function testMediasCollectionIsInitializedEmpty(): void
    {
        $user = new User();

        $this->assertInstanceOf(Collection::class, $user->getMedias());
        $this->assertCount(0, $user->getMedias());
    }

    public function testSetMedias(): void
    {
        $user = new User();
        $media = new Media();
        $collection = new ArrayCollection([$media]);

        $user->setMedias($collection);

        $this->assertCount(1, $user->getMedias());
        $this->assertSame($media, $user->getMedias()->first());
    }

    public function testSimpleGettersAndSetters(): void
    {
        $user = new User();

        $user->setEmail('ina@zaoui.com');
        $this->assertSame('ina@zaoui.com', $user->getEmail());

        $user->setName('Ina Zaoui');
        $this->assertSame('Ina Zaoui', $user->getName());

        $user->setDescription('Une description');
        $this->assertSame('Une description', $user->getDescription());

        $user->setPassword('hashed_password');
        $this->assertSame('hashed_password', $user->getPassword());

        $user->setAdmin(true);
        $this->assertTrue($user->isAdmin());

        $user->setAdmin(false);
        $this->assertFalse($user->isAdmin());
    }
}
