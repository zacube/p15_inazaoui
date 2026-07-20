<?php

// tests/Security/UserCheckerTest.php
namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserBlockedException;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;

class UserCheckerTest extends TestCase
{
    public function testBlockedUserThrowsException(): void
    {
        $user = new User();
        $user->setBlocked(true);

        $checker = new UserChecker();

        $this->expectException(UserBlockedException::class);
        $checker->checkPostAuth($user);
    }

    public function testNonBlockedUserPassesCheck(): void
    {
        $user = new User();
        $user->setBlocked(false);

        $checker = new UserChecker();

        // Ne doit lever aucune exception
        $checker->checkPostAuth($user);

        $this->addToAssertionCount(1); // confirme qu'on est arrivé ici sans exception
    }
}