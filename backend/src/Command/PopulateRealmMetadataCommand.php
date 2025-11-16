<?php

namespace App\Command;

use App\Entity\KnownRealmMetadata;
use App\Enum\WowGameType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-realm-metadata',
    description: 'Populate known realm metadata with confirmed server information',
)]
class PopulateRealmMetadataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing metadata before populating')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('clear')) {
            $io->section('Clearing existing metadata');
            $this->em->createQuery('DELETE FROM App\Entity\KnownRealmMetadata')->execute();
            $io->success('Cleared existing metadata');
        }

        $io->section('Populating known realm metadata');

        $metadata = $this->getKnownRealmData();
        $created = 0;

        foreach ($metadata as $data) {
            $existing = $this->em->getRepository(KnownRealmMetadata::class)->findOneBy([
                'slug' => $data['slug'],
                'region' => $data['region'],
            ]);

            if (!$existing) {
                $realm = new KnownRealmMetadata();
                $realm->setSlug($data['slug']);
                $realm->setRegion($data['region']);
                $realm->setExpectedGameType($data['gameType']);
                $realm->setLaunchDate($data['launchDate']);
                $realm->setSource($data['source']);
                $realm->setConfidenceScore($data['confidenceScore']);
                $realm->setNotes($data['notes'] ?? null);

                $this->em->persist($realm);
                $created++;

                $io->text(sprintf('  [+] %s (%s) - %s', $data['slug'], $data['region'], $data['gameType']->getLabel()));
            }
        }

        $this->em->flush();

        $io->success(sprintf('Populated %d realm metadata entries', $created));

        return Command::SUCCESS;
    }

    
    private function getKnownRealmData(): array
    {
        return [

            [
                'slug' => 'thunderstrike',
                'region' => 'eu',
                'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvE Anniversary Fresh server launched Nov 21, 2024'
            ],
            [
                'slug' => 'spineshatter',
                'region' => 'eu',
                'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvP Anniversary Fresh server launched Nov 21, 2024'
            ],

            [
                'slug' => 'dreamscythe',
                'region' => 'us',
                'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Normal Anniversary Fresh server launched Nov 21, 2024'
            ],
            [
                'slug' => 'nightslayer',
                'region' => 'us',
                'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvP Anniversary Fresh server launched Nov 21, 2024'
            ],
            [
                'slug' => 'maladath-au',
                'region' => 'us',
                'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Australia PvP Anniversary Fresh server launched Nov 21, 2024'
            ],

            [
                'slug' => 'soulseeker',
                'region' => 'eu',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Nov 21, 2024 alongside Anniversary'
            ],
            [
                'slug' => 'stitches',
                'region' => 'eu',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2023-08-24'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Aug 24, 2023'
            ],
            [
                'slug' => 'finkle',
                'region' => 'eu',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2023-08-24'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Aug 24, 2023'
            ],
            [
                'slug' => 'lone-wolf',
                'region' => 'eu',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2023-08-24'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Aug 24, 2023'
            ],

            [
                'slug' => 'doomhowl',
                'region' => 'us',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2024-11-21'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Nov 21, 2024 alongside Anniversary'
            ],
            [
                'slug' => 'lone-wolf',
                'region' => 'us',
                'gameType' => WowGameType::HARDCORE,
                'launchDate' => new \DateTimeImmutable('2023-08-24'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'Hardcore server launched Aug 24, 2023'
            ],

            [
                'slug' => 'wild-growth',
                'region' => 'us',
                'gameType' => WowGameType::SEASON_OF_DISCOVERY,
                'launchDate' => new \DateTimeImmutable('2023-11-30'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvE Season of Discovery server'
            ],
            [
                'slug' => 'living-flame',
                'region' => 'us',
                'gameType' => WowGameType::SEASON_OF_DISCOVERY,
                'launchDate' => new \DateTimeImmutable('2023-11-30'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvP Season of Discovery server'
            ],
            [
                'slug' => 'crusader-strike',
                'region' => 'us',
                'gameType' => WowGameType::SEASON_OF_DISCOVERY,
                'launchDate' => new \DateTimeImmutable('2023-11-30'),
                'source' => 'blizzard_announcement',
                'confidenceScore' => 10,
                'notes' => 'PvP Season of Discovery server'
            ],
        ];
    }
}
