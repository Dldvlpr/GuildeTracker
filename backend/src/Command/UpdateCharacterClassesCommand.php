<?php

namespace App\Command;

use App\Entity\GameCharacter;
use App\Service\BlizzardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-character-classes',
    description: 'Re-import guild roster to update character classes from Blizzard API',
)]
class UpdateCharacterClassesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlizzardService $blizzardService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('guild-id', 'g', InputOption::VALUE_REQUIRED, 'Guild UUID to update')
            ->addOption('access-token', 't', InputOption::VALUE_REQUIRED, 'Blizzard OAuth access token')
            ->addOption('delete-and-reimport', null, InputOption::VALUE_NONE, 'Delete existing characters and re-import')
            ->addOption('fetch-specs', null, InputOption::VALUE_NONE, 'Fetch specializations from Blizzard API (slower but more accurate)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $guildId = $input->getOption('guild-id');
        $accessToken = $input->getOption('access-token');
        $deleteAndReimport = $input->getOption('delete-and-reimport');
        $fetchSpecs = $input->getOption('fetch-specs');

        if (!$guildId) {
            $io->error('Guild ID is required (--guild-id)');
            return Command::FAILURE;
        }

        if (!$accessToken) {
            $io->error('Access token is required (--access-token). Get one from a user session or use client credentials.');
            return Command::FAILURE;
        }

        $guild = $this->em->getRepository(\App\Entity\GameGuild::class)->find($guildId);

        if (!$guild) {
            $io->error("Guild not found: {$guildId}");
            return Command::FAILURE;
        }

        $guildName = $guild->getName();
        $realm = $guild->getRealm() ?? $guild->getBlizzardRealm()?->getSlug();

        if (!$realm) {
            $io->error("No realm found for guild {$guildName}");
            return Command::FAILURE;
        }

        $io->title("Updating characters for guild: {$guildName} on {$realm}");

        $wowType = 'Retail';
        if ($blizzardRealm = $guild->getBlizzardRealm()) {
            $wowType = match ($blizzardRealm->getGameType()?->value) {
                'classic-anniversary' => 'Classic Anniversary',
                'classic-era' => 'Classic Era',
                'classic-progression' => 'Classic Progression',
                'season-of-discovery' => 'Season of Discovery',
                'hardcore' => 'Hardcore',
                default => 'Retail',
            };
        }

        $io->text("Detected game type: {$wowType}");

        if ($deleteAndReimport) {
            $io->warning('Deleting all existing characters...');
            $existingCount = $guild->getGameCharacters()->count();

            foreach ($guild->getGameCharacters()->toArray() as $character) {
                $this->em->remove($character);
            }

            $this->em->flush();
            $io->success("Deleted {$existingCount} characters");
        }

        try {
            if ($fetchSpecs) {
                $io->warning('Fetching specializations enabled - this will be slower but more accurate');
            }

            $io->text('Importing roster from Blizzard API...');
            $imported = $this->blizzardService->importRosterIntoGuild(
                $accessToken,
                $realm,
                $guildName,
                $guild,
                $wowType,
                $fetchSpecs
            );

            $io->success("Successfully imported {$imported} characters");

            $sampleSize = min(5, $guild->getGameCharacters()->count());
            if ($sampleSize > 0) {
                $io->section('Sample of imported characters:');
                $characters = $guild->getGameCharacters()->slice(0, $sampleSize);
                foreach ($characters as $char) {
                    $io->text(sprintf(
                        '  - %s (%s) - Spec: %s, Role: %s',
                        $char->getName(),
                        $char->getClass(),
                        $char->getClassSpec(),
                        $char->getRole()
                    ));
                }
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to import roster: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
