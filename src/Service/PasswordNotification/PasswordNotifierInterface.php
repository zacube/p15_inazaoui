<?php

namespace App\Service\PasswordNotification;

use App\Entity\User;

interface PasswordNotifierInterface
{
    public function notify(User $user, string $temporaryPassword): void;

    public function forget(User $user): void;
}
