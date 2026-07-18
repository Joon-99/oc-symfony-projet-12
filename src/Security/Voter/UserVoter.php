<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\User;
use App\Enum\UserRole;

class UserVoter extends Voter
{
    const CAN_EDIT_USER = 'CAN_EDIT_USER';
    const CAN_DELETE_USER = 'CAN_DELETE_USER';

    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CAN_EDIT_USER, self::CAN_DELETE_USER]) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }
        $isAdmin = $this->accessDecisionManager->decide($token, [UserRole::ADMIN->value]);

        $targetUser = $subject;
        $targetIsAdmin = in_array(UserRole::ADMIN->value, $targetUser->getRoles());

        if ($attribute === self::CAN_EDIT_USER) {
            // Regular user cannot edit anyone
            if (!$isAdmin) {
                return false;
            }

            // Admin can edit anyone except other admins
            if ($targetIsAdmin) {
                return $currentUser === $targetUser;
            }
            return true;
        }

        if ($attribute === self::CAN_DELETE_USER) {
            // Regular user cannot delete anyone
            if (!$isAdmin) {
                return false;
            }

            // Admin can delete anyone except admins
            if ($targetIsAdmin) {
                return false;
            }
            return true;
        }

        return false;
    }
}
