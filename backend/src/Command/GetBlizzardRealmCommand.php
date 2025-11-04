<?php

namespace App\Command;

use App\Entity\BlizzardGameRealm;
use App\Enum\WowGameType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:sync-blizzard-realms',
    description: 'Synchronize WoW realms from Blizzard API to local database',
)]
class GetBlizzardRealmCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly string $blizzardClientId,
        private readonly string $blizzardClientSecret,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('region', 'r', InputOption::VALUE_OPTIONAL, 'Region (us, eu, kr, tw)', 'eu')
            ->addOption('game-type', 'g', InputOption::VALUE_OPTIONAL, 'Game type (retail, classic-anniversary, season-of-discovery, etc.)', 'all')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without saving to database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $region = $input->getOption('region');
        $gameTypeFilter = $input->getOption('game-type');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be saved to database');
        }

        $io->text('Getting OAuth token...');
        $accessToken = $this->getClientCredentialsToken($region);

        if (!$accessToken) {
            $io->error('Failed to get OAuth token');
            return Command::FAILURE;
        }

        $gameTypes = $gameTypeFilter === 'all'
            ? WowGameType::cases()
            : [WowGameType::from($gameTypeFilter)];

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($gameTypes as $gameType) {
            $io->section(sprintf('Syncing %s realms for region %s', $gameType->getLabel(), strtoupper($region)));

            try {
                $realms = $this->fetchRealms($accessToken, $region, $gameType);
                $io->text(sprintf('Found %d realms from API', count($realms)));

                foreach ($realms as $realmData) {
                    $result = $this->syncRealm($realmData, $gameType, $region, $dryRun);

                    if ($result === 'created') {
                        $totalCreated++;
                        $io->text(sprintf('  [+] %s', $realmData['name']));
                    } elseif ($result === 'updated') {
                        $totalUpdated++;
                        $io->text(sprintf('  [~] %s', $realmData['name']));
                    }
                }

                if (!$dryRun) {
                    $this->em->flush();
                }
            } catch (\Throwable $e) {
                $io->warning(sprintf('Failed to sync %s: %s', $gameType->getLabel(), $e->getMessage()));
            }
        }

        $io->success(sprintf(
            'Sync completed: %d created, %d updated%s',
            $totalCreated,
            $totalUpdated,
            $dryRun ? ' (DRY RUN)' : ''
        ));

        return Command::SUCCESS;
    }

    private function getClientCredentialsToken(string $region): ?string
    {
        try {
            $tokenUrl = sprintf('https://%s.battle.net/oauth/token', $region);

            $response = $this->httpClient->request('POST', $tokenUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->blizzardClientId,
                    'client_secret' => $this->blizzardClientSecret,
                ],
            ]);

            $data = $response->toArray();
            return $data['access_token'] ?? null;
        } catch (\Throwable $e) {
            error_log('Failed to get client credentials token: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchRealms(string $accessToken, string $region, WowGameType $gameType): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $region);
        $namespace = $gameType->getDynamicNamespace($region);

        $endpoint = match($gameType) {
            WowGameType::RETAIL => '/data/wow/realm/index',
            default => '/data/wow/realm/index',
        };

        $url = sprintf('%s%s?namespace=%s&locale=en_US', $base, $endpoint, $namespace);

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = $response->toArray();
        return $data['realms'] ?? [];
    }

    private function syncRealm(array $realmData, WowGameType $gameType, string $region, bool $dryRun): string
    {
        $slug = $realmData['slug'] ?? null;
        if (!$slug) {
            return 'skipped';
        }

        $realm = $this->em->getRepository(BlizzardGameRealm::class)->findOneBy([
            'slug' => $slug,
            'gameType' => $gameType,
            'region' => $region,
        ]);

        $isNew = false;
        if (!$realm) {
            $realm = new BlizzardGameRealm();
            $realm->setSlug($slug);
            $realm->setGameType($gameType);
            $realm->setRegion($region);
            $realm->setIsTournament(false);
            $isNew = true;
        }

        $realm->setName($realmData['name'] ?? $slug);
        $realm->setBlizzardRealmId($realmData['id'] ?? null);

        if (isset($realmData['timezone'])) {
            $realm->setTimezone($realmData['timezone']);
        }

        if (isset($realmData['type']['name'])) {
            $realm->setType($realmData['type']['name']);
        }

        if (isset($realmData['is_tournament'])) {
            $realm->setIsTournament($realmData['is_tournament']);
        }

        if (isset($realmData['connected_realm']['href'])) {
            preg_match('/connected-realm\/(\d+)/', $realmData['connected_realm']['href'], $matches);
            if (isset($matches[1])) {
                $realm->setConnectedRealmId((int)$matches[1]);
            }
        }

        $realm->setLastSyncAt(new \DateTimeImmutable());

        if (!$dryRun) {
            $this->em->persist($realm);
        }

        return $isNew ? 'created' : 'updated';
    }
}
