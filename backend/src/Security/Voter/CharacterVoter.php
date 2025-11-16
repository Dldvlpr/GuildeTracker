<?php

namespace App\Security\Voter;

use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Enum\GuildRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CharacterVoter extends Voter
{
    public const VIEW = 'CHARACTER_VIEW';
    public const CREATE = 'CHARACTER_CREATE';
    public const EDIT = 'CHARACTER_EDIT';
    public const DELETE = 'CHARACTER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::DELETE, self::EDIT], true)
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
            self::CREATE => $this->canCreate($membership),
            self::EDIT => $this->canEdit($membership),
            self::DELETE => $this->canDelete($membership),
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

    private function canCreate(GuildMembership $membership): bool
    {
        return in_array($membership->getRole(), [GuildRole::OFFICER, GuildRole::GM], true);
    }

    private function canEdit(GuildMembership $membership): bool
    {
        return $membership->getRole() === GuildRole::GM;
    }

    private function canDelete(GuildMembership $membership): bool
    {
        return $membership->getRole() === GuildRole::GM;
    }
}
