<?php

namespace App\Command;

use App\Entity\GameCharacter;
use App\Service\WowClassMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-character-roles',
    description: 'Fix character roles based on their specialization',
)]
class FixCharacterRolesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WowClassMapper $classMapper,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fixing Character Roles');

        $characters = $this->em->getRepository(GameCharacter::class)->findAll();
        $updated = 0;

        foreach ($characters as $character) {
            $spec = $character->getClassSpec();

            if (!$spec || $spec === 'Unknown') {
                continue;
            }

            $newRole = $this->classMapper->getRoleFromSpec($spec);
            $oldRole = $character->getRole();

            if ($newRole !== $oldRole) {
                $character->setRole($newRole);
                $this->em->persist($character);
                $updated++;

                $io->text(sprintf(
                    'Updated %s (%s): %s → %s (spec: %s)',
                    $character->getName(),
                    $character->getClass(),
                    $oldRole,
                    $newRole,
                    $spec
                ));
            }
        }

        if ($updated > 0) {
            $this->em->flush();
            $io->success("Updated {$updated} character roles");
        } else {
            $io->info('No roles needed updating');
        }

        $io->section('Role Distribution:');
        $result = $this->em->getConnection()->executeQuery(
            'SELECT role, COUNT(*) as count FROM game_character GROUP BY role ORDER BY count DESC'
        );

        $io->table(['Role', 'Count'], $result->fetchAllNumeric());

        return Command::SUCCESS;
    }
}
