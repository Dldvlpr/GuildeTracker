<?php

namespace App\Security\Voter;

use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Enum\GuildRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MembershipVoter extends Voter
{
    public const VIEW = 'GUILD_VIEW';
    public const MANAGE = 'GUILD_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE], true)
            && $subject instanceof GameGuild;
    }

    protected function voteOnAttribute(string $attribute, mixed $guild, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        
        $membership = $this->getUserMembership($guild, $user);

        if ($membership === null) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($membership),
            self::MANAGE => $this->canManage($membership),
            default => false,
        };
    }

    private function getUserMembership(GameGuild $guild, User $user): ?GuildMembership
    {
        foreach ($guild->getGuildMemberships() as $membership) {
            if ($membership->getUser()->getId() === $user->getId()) {
                return $membership;
            }
        }
        return null;
    }

    private function canView(GuildMembership $membership): bool
    {
        return true;
    }

    private function canManage(GuildMembership $membership): bool
    {
        return in_array($membership->getRole(), [GuildRole::OFFICER, GuildRole::GM], true);
    }
}
