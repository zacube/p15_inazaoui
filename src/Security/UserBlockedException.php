<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class UserBlockedException extends AccountStatusException
{
    public function getMessageKey(): string
    {
        return 'Votre compte a été bloqué par l\'administrateur.';
    }
}
