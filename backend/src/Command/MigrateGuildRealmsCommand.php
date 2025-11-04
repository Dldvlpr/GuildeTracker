<?php

namespace App\Command;

use App\Entity\BlizzardGameRealm;
use App\Entity\GameGuild;
use App\Enum\WowGameType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-guild-realms',
    description: 'Migrate guilds from legacy realm (string) to BlizzardGameRealm relation',
)]
class MigrateGuildRealmsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without saving to database')
            ->addOption('auto-detect', null, InputOption::VALUE_NONE, 'Automatically detect game type from guild data')
            ->addOption('default-game-type', null, InputOption::VALUE_OPTIONAL, 'Default game type for ambiguous guilds', 'classic-anniversary')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, 'Default region', 'eu')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $autoDetect = $input->getOption('auto-detect');
        $defaultGameTypeValue = $input->getOption('default-game-type');
        $defaultRegion = $input->getOption('region');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be saved to database');
        }

        $guilds = $this->em->getRepository(GameGuild::class)
            ->createQueryBuilder('g')
            ->where('g.blizzardRealm IS NULL')
            ->andWhere('g.realm IS NOT NULL')
            ->getQuery()
            ->getResult();

        if (empty($guilds)) {
            $io->success('No guilds to migrate! All guilds are already linked to BlizzardGameRealm.');
            return Command::SUCCESS;
        }

        $io->title(sprintf('Found %d guilds to migrate', count($guilds)));

        $stats = [
            'migrated' => 0,
            'skipped' => 0,
            'ambiguous' => 0,
            'not_found' => 0,
        ];

        foreach ($guilds as $guild) {
            $result = $this->migrateGuild(
                $guild,
                $io,
                $input,
                $output,
                $autoDetect,
                $defaultGameTypeValue,
                $defaultRegion,
                $dryRun
            );

            $stats[$result]++;

            if (!$dryRun && $stats['migrated'] % 10 === 0) {
                $this->em->flush();
                $io->text(sprintf('Flushed %d guilds...', $stats['migrated']));
            }
        }

        if (!$dryRun && $stats['migrated'] > 0) {
            $this->em->flush();
        }

        $io->newLine();
        $io->section('Migration Summary');

        $table = new Table($output);
        $table->setHeaders(['Status', 'Count']);
        $table->addRow(['Migrated', $stats['migrated']]);
        $table->addRow(['Skipped', $stats['skipped']]);
        $table->addRow(['Ambiguous (manual)', $stats['ambiguous']]);
        $table->addRow(['Not Found', $stats['not_found']]);
        $table->render();

        if ($stats['migrated'] > 0) {
            $io->success(sprintf(
                'Migration completed: %d guilds migrated%s',
                $stats['migrated'],
                $dryRun ? ' (DRY RUN)' : ''
            ));
        } else {
            $io->warning('No guilds were migrated.');
        }

        return Command::SUCCESS;
    }

    private function migrateGuild(
        GameGuild $guild,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        bool $autoDetect,
        string $defaultGameTypeValue,
        string $defaultRegion,
        bool $dryRun
    ): string {
        $realmString = $guild->getRealm();
        if (!$realmString) {
            return 'skipped';
        }

        $realmSlug = $this->normalizeRealmName($realmString);

        $matchingRealms = $this->em->getRepository(BlizzardGameRealm::class)
            ->createQueryBuilder('r')
            ->where('r.slug = :slug')
            ->andWhere('r.region = :region')
            ->setParameter('slug', $realmSlug)
            ->setParameter('region', $defaultRegion)
            ->getQuery()
            ->getResult();

        if (empty($matchingRealms)) {
            $io->error(sprintf(
                'Guild "%s": Realm "%s" (slug: %s) not found in region %s',
                $guild->getName(),
                $realmString,
                $realmSlug,
                $defaultRegion
            ));
            return 'not_found';
        }

        if (count($matchingRealms) === 1) {
            $realm = $matchingRealms[0];
            $guild->setBlizzardRealm($realm);

            if (!$dryRun) {
                $this->em->persist($guild);
            }

            $io->text(sprintf(
                '✓ Guild "%s" → %s (%s)',
                $guild->getName(),
                $realm->getName(),
                $realm->getGameType()->getLabel()
            ));

            return 'migrated';
        }

        if ($autoDetect) {
            $defaultGameType = WowGameType::from($defaultGameTypeValue);
            $realm = $this->findRealmByGameType($matchingRealms, $defaultGameType);

            if ($realm) {
                $guild->setBlizzardRealm($realm);

                if (!$dryRun) {
                    $this->em->persist($guild);
                }

                $io->text(sprintf(
                    '✓ Guild "%s" → %s (%s) [auto-detected]',
                    $guild->getName(),
                    $realm->getName(),
                    $realm->getGameType()->getLabel()
                ));

                return 'migrated';
            }
        }

        $io->warning(sprintf(
            'Guild "%s" has multiple realm matches for "%s":',
            $guild->getName(),
            $realmString
        ));

        $choices = [];
        foreach ($matchingRealms as $idx => $realm) {
            $choices[$idx] = sprintf(
                '%s (%s - %s)',
                $realm->getName(),
                $realm->getGameType()->getLabel(),
                strtoupper($realm->getRegion())
            );
        }
        $choices['skip'] = 'Skip this guild';

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select the correct realm:',
            $choices,
            'skip'
        );

        $answer = $helper->ask($input, $output, $question);

        if ($answer === 'Skip this guild') {
            $io->text('  → Skipped');
            return 'ambiguous';
        }

        $selectedIndex = array_search($answer, $choices);
        if ($selectedIndex !== false && isset($matchingRealms[$selectedIndex])) {
            $realm = $matchingRealms[$selectedIndex];
            $guild->setBlizzardRealm($realm);

            if (!$dryRun) {
                $this->em->persist($guild);
            }

            $io->text(sprintf(
                '✓ Guild "%s" → %s (%s)',
                $guild->getName(),
                $realm->getName(),
                $realm->getGameType()->getLabel()
            ));

            return 'migrated';
        }

        return 'ambiguous';
    }

    private function normalizeRealmName(string $realmName): string
    {
        $slug = strtolower($realmName);
        $slug = str_replace(' ', '-', $slug);
        $slug = str_replace("'", '', $slug);


        return $slug;
    }

    private function findRealmByGameType(array $realms, WowGameType $gameType): ?BlizzardGameRealm
    {
        foreach ($realms as $realm) {
            if ($realm->getGameType() === $gameType) {
                return $realm;
            }
        }

        return null;
    }
}
