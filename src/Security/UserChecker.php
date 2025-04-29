<?php

namespace App\Security;

use App\Entity\UserEraTime as AppUser;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Ce compte est inactif.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user->isActive()) {
            throw new AccountExpiredException('...');
        }
    }
}