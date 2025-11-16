<?php

namespace App\Service;

use App\Entity\KnownRealmMetadata;
use App\Entity\RealmTypeOverride;
use App\Enum\WowGameType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;


class RealmGameTypeDetector
{
    private array $overrideCache = [];
    private array $metadataCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    
    public function detectSupportedGameTypes(array $realmData, array $candidateGameTypes): array
    {
        $slug = $realmData['slug'] ?? null;
        $region = $this->extractRegion($realmData);

        if (!$slug || !$region) {
            $this->logger->warning('Missing slug or region in realm data', ['realmData' => $realmData]);
            return $this->fallbackDetection($realmData, $candidateGameTypes);
        }

        if ($override = $this->getOverride($slug, $region)) {
            $this->logger->info('Using manual override for realm', [
                'slug' => $slug,
                'region' => $region,
                'gameType' => $override->value,
            ]);
            return [$override];
        }

        $category = $this->extractCategory($realmData);
        if ($category) {
            $detected = $this->detectFromCategory($category, $slug, $region, $candidateGameTypes);
            if (!empty($detected)) {
                return $detected;
            }
        }

        if ($metadata = $this->getMetadata($slug, $region)) {
            $this->logger->info('Using known metadata for realm', [
                'slug' => $slug,
                'region' => $region,
                'gameType' => $metadata->getExpectedGameType()->value,
                'source' => $metadata->getSource(),
            ]);
            return [$metadata->getExpectedGameType()];
        }

        return $this->fallbackDetection($realmData, $candidateGameTypes);
    }

    private function getOverride(string $slug, string $region): ?WowGameType
    {
        $key = "{$region}:{$slug}";

        if (!isset($this->overrideCache[$key])) {
            $override = $this->em->getRepository(RealmTypeOverride::class)->findOneBy([
                'slug' => $slug,
                'region' => $region,
            ]);

            $this->overrideCache[$key] = $override?->getGameType();
        }

        return $this->overrideCache[$key];
    }

    private function getMetadata(string $slug, string $region): ?KnownRealmMetadata
    {
        $key = "{$region}:{$slug}";

        if (!isset($this->metadataCache[$key])) {
            $metadata = $this->em->getRepository(KnownRealmMetadata::class)->findOneBy([
                'slug' => $slug,
                'region' => $region,
            ]);

            $this->metadataCache[$key] = $metadata;
        }

        return $this->metadataCache[$key];
    }

    private function extractRegion(array $realmData): ?string
    {


        if (isset($realmData['region'])) {
            return is_string($realmData['region']) ? $realmData['region'] : null;
        }


        return null;
    }

    private function extractCategory(array $realmData): ?string
    {
        if (!isset($realmData['category'])) {
            return null;
        }

        $categoryData = $realmData['category'];

        if (is_array($categoryData)) {
            $category = $categoryData['en_US'] ?? $categoryData['en_GB'] ?? reset($categoryData);
            return $category ? strtolower($category) : null;
        }

        return is_string($categoryData) ? strtolower($categoryData) : null;
    }

    private function extractRealmName(array $realmData): string
    {
        $name = $realmData['name'] ?? $realmData['slug'] ?? 'Unknown';

        if (is_array($name)) {
            return $name['en_US'] ?? $name['en_GB'] ?? reset($name) ?? 'Unknown';
        }

        return $name;
    }

    
    private function detectFromCategory(string $category, string $slug, string $region, array $candidateGameTypes): array
    {
        $supported = [];

        foreach ($candidateGameTypes as $gameType) {
            $shouldInclude = false;

            if (str_contains($category, 'classic era')) {
                $shouldInclude = ($gameType === WowGameType::CLASSIC_ERA || $gameType === WowGameType::CLASSIC_PROGRESSION);
            } elseif (str_contains($category, 'anniversary')) {
                $shouldInclude = ($gameType === WowGameType::CLASSIC_ANNIVERSARY);
            } elseif (str_contains($category, 'seasonal')) {

                if ($metadata = $this->getMetadata($slug, $region)) {
                    $shouldInclude = ($gameType === $metadata->getExpectedGameType());
                } else {

                    $shouldInclude = ($gameType === WowGameType::SEASON_OF_DISCOVERY);
                }
            } elseif (str_contains($category, 'season of discovery') || str_contains($category, 'season')) {
                $shouldInclude = ($gameType === WowGameType::SEASON_OF_DISCOVERY);
            } elseif (str_contains($category, 'hardcore')) {
                $shouldInclude = ($gameType === WowGameType::HARDCORE);
            } elseif (str_contains($category, 'russian') || str_contains($category, 'brasileiro') || str_contains($category, 'oceanic')) {

                if ($metadata = $this->getMetadata($slug, $region)) {
                    $shouldInclude = ($gameType === $metadata->getExpectedGameType());
                } else {
                    $shouldInclude = ($gameType === $candidateGameTypes[0]);
                }
            }

            if ($shouldInclude) {
                $supported[] = $gameType;
            }
        }

        return $supported;
    }

    
    private function fallbackDetection(array $realmData, array $candidateGameTypes): array
    {
        $name = $this->extractRealmName($realmData);
        $slug = $realmData['slug'] ?? '';

        $slugLower = strtolower($slug);
        $nameLower = strtolower($name);

        if (count($candidateGameTypes) === 1) {
            return $candidateGameTypes;
        }

        $supported = [];

        foreach ($candidateGameTypes as $gameType) {
            $shouldInclude = false;

            switch ($gameType) {
                case WowGameType::RETAIL:
                    $shouldInclude = true;
                    break;

                case WowGameType::HARDCORE:
                    if (str_contains($nameLower, 'hardcore') || str_contains($slugLower, 'hardcore')) {
                        $shouldInclude = true;
                    }
                    break;

                case WowGameType::SEASON_OF_DISCOVERY:
                    if (str_contains($nameLower, '(season)') || str_contains($nameLower, 'season')) {
                        $shouldInclude = true;
                    }
                    break;

                case WowGameType::CLASSIC_ANNIVERSARY:
                    if (str_contains($nameLower, 'anniversary') || str_contains($nameLower, 'fresh')) {
                        $shouldInclude = true;
                    }
                    break;
            }

            if ($shouldInclude) {
                $supported[] = $gameType;
            }
        }

        if (empty($supported)) {
            $this->logger->warning('No game type detected for realm', [
                'slug' => $slug,
                'name' => $name,
                'candidates' => array_map(fn($t) => $t->value, $candidateGameTypes),
            ]);
        }

        return $supported;
    }
}
