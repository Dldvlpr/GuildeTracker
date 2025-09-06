<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\GameGuild;
use App\Enum\GuildRole;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class GuildVoter extends Voter
{
    public const VIEW = 'GUILD_VIEW';
    public const EDIT = 'GUILD_EDIT';
    public const DELETE = 'GUILD_DELETE';

    public function __construct(private readonly EntityManagerInterface $em) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof GameGuild;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $role = $this->getRoleInGuild($user, $subject);
        if (!$role) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => \in_array($role, [GuildRole::OFFICER, GuildRole::GM], true),
            self::DELETE => $role === GuildRole::GM,
        };
    }

    private function getRoleInGuild(User $user, GameGuild $guild): ?GuildRole
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m')
            ->from('App\Entity\GuildMembership', 'm')
            ->where('m.user = :u')->andWhere('m.guild = :g')
            ->setParameter('u', $user)->setParameter('g', $guild)
            ->setMaxResults(1);

        $m = $qb->getQuery()->getOneOrNullResult();
        return $m?->getRole();
    }
}
