<?php

namespace App\Command;

use App\Entity\Spell;
use App\Repository\SpellRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fetch-blizzard-spells',
    description: 'Sync spell icons from Blizzard API into the local spell table (uses local spell list, no runtime token on the front)',
)]
class FetchBlizzardSpellsCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly SpellRepository $spellRepository,
        private readonly string $blizzardRegion,
        private readonly string $blizzardLocale,
        private readonly string $blizzardClientId,
        private readonly string $blizzardClientSecret,
        private readonly string $defaultSourcePath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Path to spells yaml/json file or "db"', $this->defaultSourcePath)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of spells to process', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write to DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string)$input->getOption('source');
        $limit = $input->getOption('limit') ? (int)$input->getOption('limit') : null;
        $dryRun = (bool)$input->getOption('dry-run');

        $io->title('Fetching Blizzard spell icons');
        $io->text(sprintf('Region: %s | Locale: %s', $this->blizzardRegion, $this->blizzardLocale));

        $spells = $this->loadSpellList($source);
        if (!$spells) {
            $io->warning('No spells found in source. Aborting.');
            return Command::SUCCESS;
        }

        if ($limit !== null) {
            $spells = array_slice($spells, 0, $limit);
        }

        $token = $this->getClientCredentialsToken();
        if (!$token) {
            $io->error('Failed to get Blizzard token (check OAUTH_BNET_CLIENT_ID/OAUTH_BNET_CLIENT_SECRET).');
            return Command::FAILURE;
        }

        $updated = 0;
        $created = 0;
        $errors = 0;
        $seenIds = [];

        foreach ($spells as $row) {
            $spellId = (int)($row['id'] ?? 0);
            if ($spellId <= 0) {
                $errors++;
                $io->warning('Invalid spell entry encountered (missing id)');
                continue;
            }

            if (isset($seenIds[$spellId])) {
                $io->warning(sprintf('Skipping duplicate entry for spell ID %d in source list', $spellId));
                continue;
            }
            $seenIds[$spellId] = true;

            $name = (string)($row['name'] ?? '');
            try {
                $media = $this->fetchSpellMedia($spellId, $token);
                $iconUrl = $this->extractIconUrl($media);
                $iconFile = $iconUrl ? pathinfo(parse_url($iconUrl, PHP_URL_PATH) ?? '', PATHINFO_FILENAME) : null;

                $displayName = $name ?: ($media['name'] ?? '');

                $spell = $this->spellRepository->findOneByBlizzardId($spellId) ?? new Spell();
                $isNew = $spell->getId() === null;

                $spell->setBlizzardId($spellId);
                if ($displayName) {
                    $spell->setName($displayName);
                }
                $spell->setIconFile($iconFile);
                $spell->setLastSyncedAt(new \DateTimeImmutable('now'));

                if (!$dryRun) {
                    $this->em->persist($spell);
                }

                $isNew ? $created++ : $updated++;

                $io->writeln(sprintf(
                    '<info>%s</info> %s — icon: %s',
                    $isNew ? '[+]' : '[~]',
                    $displayName ?: ('Spell #' . $spellId),
                    $iconFile ?? 'n/a'
                ));
            } catch (\Throwable $e) {
                $errors++;
                $io->error(sprintf('Spell %d failed: %s', $spellId, $e->getMessage()));
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('Done. created=%d updated=%d errors=%d', $created, $updated, $errors));
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
        * Load spells from YAML/JSON file or DB.
        *
        * @return array<int, array{id:int,name?:string}>
        */
    private function loadSpellList(string $source): array
    {
        if (strtolower($source) === 'db') {
            return array_map(static fn(Spell $s) => ['id' => $s->getBlizzardId(), 'name' => $s->getName()], $this->spellRepository->findAll());
        }

        $path = $source;
        if (!is_file($path)) {
            return [];
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $content = file_get_contents($path) ?: '';

        $data = [];
        if (in_array($ext, ['yml', 'yaml'], true)) {
            $parsed = Yaml::parse($content);
            $data = is_array($parsed) ? $parsed : [];
        } elseif ($ext === 'json') {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $data = is_array($parsed) ? $parsed : [];
        }

        if (isset($data['spells']) && is_array($data['spells'])) {
            $data = $data['spells'];
        }

        $rows = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!isset($row['id']) && isset($row['spellId'])) {
                $row['id'] = $row['spellId'];
            }
            if (!isset($row['name']) && isset($row['spellName'])) {
                $row['name'] = $row['spellName'];
            }
            if (isset($row['id']) && (int)$row['id'] > 0) {
                $rows[] = ['id' => (int)$row['id'], 'name' => $row['name'] ?? null];
            }
        }

        return $rows;
    }

    private function getClientCredentialsToken(): ?string
    {
        $tokenUrl = sprintf('https://%s.battle.net/oauth/token', $this->blizzardRegion ?: 'eu');

        try {
            $response = $this->httpClient->request('POST', $tokenUrl, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->blizzardClientId,
                    'client_secret' => $this->blizzardClientSecret,
                ],
            ]);

            $data = $response->toArray(false);
            return $data['access_token'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchSpellMedia(int $spellId, string $token): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $namespace = sprintf('static-%s', $this->blizzardRegion);
        $url = sprintf('%s/data/wow/media/spell/%d?namespace=%s&locale=%s', $base, $spellId, $namespace, $this->blizzardLocale);

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        return $response->toArray(false);
    }

    private function extractIconUrl(array $media): ?string
    {
        $assets = $media['assets'] ?? [];
        if (!is_array($assets)) {
            return null;
        }
        foreach ($assets as $asset) {
            if (($asset['key'] ?? '') === 'icon' && !empty($asset['value'])) {
                return $asset['value'];
            }
        }

        // fallback to first value if not keyed
        foreach ($assets as $asset) {
            if (!empty($asset['value'])) {
                return $asset['value'];
            }
        }
        return null;
    }
}
