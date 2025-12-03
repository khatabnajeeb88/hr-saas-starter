<?php

namespace App\Security\Voter;

use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TeamVoter extends Voter
{
    // Team permissions
    public const VIEW = 'TEAM_VIEW';
    public const EDIT = 'TEAM_EDIT';
    public const DELETE = 'TEAM_DELETE';
    public const MANAGE_MEMBERS = 'TEAM_MANAGE_MEMBERS';
    public const TRANSFER_OWNERSHIP = 'TEAM_TRANSFER_OWNERSHIP';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Only vote on Team objects
        if (!$subject instanceof Team) {
            return false;
        }

        // Check if the attribute is one we support
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::MANAGE_MEMBERS,
            self::TRANSFER_OWNERSHIP,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        // The user must be logged in
        if (!$user instanceof User) {
            return false;
        }

        /** @var Team $team */
        $team = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($team, $user),
            self::EDIT => $this->canEdit($team, $user),
            self::DELETE => $this->canDelete($team, $user),
            self::MANAGE_MEMBERS => $this->canManageMembers($team, $user),
            self::TRANSFER_OWNERSHIP => $this->canTransferOwnership($team, $user),
            default => false,
        };
    }

    /**
     * Members can view the team
     */
    private function canView(Team $team, User $user): bool
    {
        return $this->isMember($team, $user);
    }

    /**
     * Only owner and admins can edit team details
     */
    private function canEdit(Team $team, User $user): bool
    {
        return $this->hasAdminPrivileges($team, $user);
    }

    /**
     * Only the owner can delete the team
     */
    private function canDelete(Team $team, User $user): bool
    {
        return $this->isOwner($team, $user);
    }

    /**
     * Owner and admins can manage members (add/remove/change roles)
     */
    private function canManageMembers(Team $team, User $user): bool
    {
        return $this->hasAdminPrivileges($team, $user);
    }

    /**
     * Only the owner can transfer ownership
     */
    private function canTransferOwnership(Team $team, User $user): bool
    {
        return $this->isOwner($team, $user);
    }

    /**
     * Check if user is the team owner
     */
    private function isOwner(Team $team, User $user): bool
    {
        return $team->getOwner() === $user;
    }

    /**
     * Check if user is a member of the team
     */
    private function isMember(Team $team, User $user): bool
    {
        return $team->hasMember($user);
    }

    /**
     * Check if user has admin privileges (owner or admin role)
     */
    private function hasAdminPrivileges(Team $team, User $user): bool
    {
        // Owner always has admin privileges
        if ($this->isOwner($team, $user)) {
            return true;
        }

        // Check if user is a member with admin role
        $member = $team->getMemberByUser($user);
        return $member !== null && $member->hasAdminPrivileges();
    }
}
