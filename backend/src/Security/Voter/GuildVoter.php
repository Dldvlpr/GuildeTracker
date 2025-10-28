<?php

namespace App\Security\Voter;

use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Enum\GuildRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GuildVoter extends Voter
{
    public const VIEW = 'GUILD_VIEW';
    public const MANAGE = 'GUILD_MANAGE';
    public const DELETE = 'GUILD_DELETE';
    public const USE_FEATURES = 'GUILD_USE_FEATURES';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE, self::DELETE, self::USE_FEATURES], true)
            && $subject instanceof GameGuild;
    }

    protected function voteOnAttribute(string $attribute, mixed $guild, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var GameGuild $guild */
        $membership = $this->getUserMembership($guild, $user);

        if ($membership === null) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($membership),
            self::MANAGE => $this->canManage($membership),
            self::DELETE => $this->canDelete($membership),
            self::USE_FEATURES => $this->canUseFeatures($membership, $guild),
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

    private function canDelete(GuildMembership $membership): bool
    {
        return $membership->getRole() === GuildRole::GM;
    }

    private function canUseFeatures(GuildMembership $membership, GameGuild $guild): bool
    {
        // Member must be in the guild AND the guild must have at least one character
        return $guild->isValid();
    }
}
