<?php

namespace App\Security\Voter;

use App\Entity\GameGuild;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GuildVoter extends Voter
{
    public const VIEW = 'GUILD_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof GameGuild;
    }

    protected function voteOnAttribute(string $attribute, mixed $guild, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        foreach ($guild->getMemberships() as $m) {
            if ($m->getUser()->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
