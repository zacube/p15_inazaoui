<?php

namespace App\Service\PasswordNotification;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FileLogPasswordNotifier implements PasswordNotifierInterface
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/dev_passwords.log')]
        private string $logFile,
    ) {
    }

    public function notify(User $user, string $temporaryPassword): void
    {
        file_put_contents(
            $this->logFile,
            sprintf("%s|%s|%s\n", $user->getId(), $user->getEmail(), $temporaryPassword),
            FILE_APPEND | LOCK_EX
        );
    }

    public function forget(User $user): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_filter($lines, function ($line) use ($user) {
            $parts = explode('|', $line);

            return $parts[0] !== (string) $user->getId();
        });

        file_put_contents($this->logFile, implode("\n", $lines).(empty($lines) ? '' : "\n"));
    }
}
