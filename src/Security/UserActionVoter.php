<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserActionVoter extends Voter
{
    private array $rolePriorities;

    public function __construct(string $jsonRolePriorities)
    {
        $this->rolePriorities = json_decode($jsonRolePriorities, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof UserInterface;
    }

    /**
     * @param UserInterface $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $highestCurrentUserRole = $this->getHighestRole($token->getUser()->getRoles());
        $highestTargetUserRole = $this->getHighestRole($subject->getRoles());

        return $highestTargetUserRole !== $highestCurrentUserRole
            && $this->rolePriorities[$highestTargetUserRole] > $this->rolePriorities[$highestCurrentUserRole];
    }

    private function getHighestRole(array $roles): string
    {
        $highestRole = null;
        foreach ($roles as $role) {
            if (!$highestRole || $this->rolePriorities[$role] < $this->rolePriorities[$highestRole]) {
                $highestRole = $role;
            }
        }

        return $highestRole;
    }
}
