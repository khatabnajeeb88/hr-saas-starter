<?php

namespace App\Security\Voter;

use App\Entity\Subscription;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SubscriptionVoter extends Voter
{
    public const VIEW = 'SUBSCRIPTION_VIEW';
    public const MANAGE = 'SUBSCRIPTION_MANAGE';
    public const CANCEL = 'SUBSCRIPTION_CANCEL';
    public const UPGRADE = 'SUBSCRIPTION_UPGRADE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE, self::CANCEL, self::UPGRADE])
            && $subject instanceof Subscription;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Subscription $subscription */
        $subscription = $subject;
        $team = $subscription->getTeam();

        return match ($attribute) {
            self::VIEW => $this->canView($team, $user),
            self::MANAGE => $this->canManage($team, $user),
            self::CANCEL => $this->canCancel($team, $user),
            self::UPGRADE => $this->canUpgrade($team, $user),
            default => false,
        };
    }

    private function canView(Team $team, User $user): bool
    {
        // Any team member can view subscription
        return $team->hasMember($user) || $team->isOwner($user);
    }

    private function canManage(Team $team, User $user): bool
    {
        // Only owner and admins can manage subscription
        if ($team->isOwner($user)) {
            return true;
        }

        $member = $team->getMemberByUser($user);
        if ($member === null) {
            return false;
        }

        return in_array($member->getRole(), ['admin', 'owner']);
    }

    private function canCancel(Team $team, User $user): bool
    {
        // Only owner can cancel subscription
        return $team->isOwner($user);
    }

    private function canUpgrade(Team $team, User $user): bool
    {
        // Owner and admins can upgrade/downgrade
        return $this->canManage($team, $user);
    }
}
