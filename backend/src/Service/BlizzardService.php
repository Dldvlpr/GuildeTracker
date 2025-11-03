<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class BlizzardService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private TokenEncryptionService $tokenEncryption,
        private string $blizzardRegion,
        private string $blizzardLocale,
        private string $blizzardClientId,
        private string $blizzardClientSecret,
    ) {}

    public function getWowCharacters(string $accessToken): array
    {
        $allCharacters = [];

        $retailCharacters = $this->getRetailCharacters($accessToken);
        $allCharacters = array_merge($allCharacters, $retailCharacters);

        $classicCharacters = $this->getClassicCharacters($accessToken);
        $allCharacters = array_merge($allCharacters, $classicCharacters);

        return [
            'characters' => $allCharacters,
            'total' => count($allCharacters),
        ];
    }

    private function getRetailCharacters(string $accessToken): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $namespace = sprintf('profile-%s', $this->blizzardRegion);
        $url = sprintf('%s/profile/user/wow?namespace=%s&locale=%s',
            $base, $namespace, $this->blizzardLocale
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            $data = $response->toArray();
            $characters = [];

            if (isset($data['wow_accounts'])) {
                foreach ($data['wow_accounts'] as $account) {
                    if (isset($account['characters'])) {
                        foreach ($account['characters'] as $character) {
                            $character['wow_type'] = 'Retail';
                            $characters[] = $character;
                        }
                    }
                }
            }

            return $characters;
        } catch (\Throwable $e) {
            error_log("Failed to fetch retail characters: {$e->getMessage()}");
            return [];
        }
    }

    private function getClassicCharacters(string $accessToken): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $namespace = sprintf('profile-classic1x-%s', $this->blizzardRegion);
        $url = sprintf('%s/profile/user/wow?namespace=%s&locale=%s',
            $base, $namespace, $this->blizzardLocale
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            $data = $response->toArray();
            $characters = [];

            if (isset($data['wow_accounts'])) {
                foreach ($data['wow_accounts'] as $account) {
                    if (isset($account['characters'])) {
                        foreach ($account['characters'] as $character) {
                            $character['wow_type'] = 'Classic Anniversary';
                            $characters[] = $character;
                        }
                    }
                }
            }

            return $characters;
        } catch (\Throwable $e) {
            error_log("Failed to fetch classic characters: {$e->getMessage()}");
            return [];
        }
    }

    private function detectWowType(string $key): string
    {
        return match(true) {
            str_contains($key, 'classic') => 'Classic',
            str_contains($key, 'season') => 'Season of Discovery',
            default => 'Retail',
        };
    }

    public function getCharacterProfile(string $accessToken, string $realm, string $characterName, string $wowType = 'Retail'): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);

        // Determine namespace based on WoW type
        $namespace = match (true) {
            str_contains($wowType, 'Classic Anniversary') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            str_contains($wowType, 'Classic Era') => sprintf('profile-classic-%s', $this->blizzardRegion),
            str_contains($wowType, 'Season of Discovery') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            str_contains($wowType, 'Hardcore') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            str_contains($wowType, 'Classic') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            default => sprintf('profile-%s', $this->blizzardRegion), // Retail by default
        };

        $realmSlug = strtolower(str_replace(' ', '-', $realm));
        $charNameLower = strtolower($characterName);

        $url = sprintf('%s/profile/wow/character/%s/%s?namespace=%s&locale=%s',
            $base, $realmSlug, $charNameLower, $namespace, $this->blizzardLocale
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $response->toArray();
    }

    public function getCharacterGuild(string $accessToken, string $realm, string $characterName, string $wowType = 'Retail'): ?array
    {
        $profile = $this->getCharacterProfile($accessToken, $realm, $characterName, $wowType);

        if (!isset($profile['guild'])) {
            return null;
        }

        return $this->getGuildDetails($accessToken, $realm, $profile['guild']['name'], $wowType);
    }

    public function getGuildDetails(string $accessToken, string $realm, string $guildName, string $wowType = 'Retail'): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $realmSlug = strtolower(str_replace(' ', '-', $realm));
        $guildNameLower = strtolower($guildName);

        // For Classic, use profile namespace instead of dynamic
        // The roster endpoint structure is different
        $isClassic = str_contains($wowType, 'Classic');

        if ($isClassic) {
            $namespace = match (true) {
                str_contains($wowType, 'Classic Anniversary') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                str_contains($wowType, 'Classic Era') => sprintf('profile-classic-%s', $this->blizzardRegion),
                str_contains($wowType, 'Season of Discovery') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                str_contains($wowType, 'Hardcore') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                default => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            };

            // Classic uses profile/wow/guild endpoint
            $url = sprintf('%s/data/wow/guild/%s/%s/roster?namespace=%s&locale=%s',
                $base, $realmSlug, $guildNameLower, $namespace, $this->blizzardLocale
            );
        } else {
            // Retail uses dynamic namespace
            $namespace = sprintf('dynamic-%s', $this->blizzardRegion);
            $url = sprintf('%s/data/wow/guild/%s/%s/roster?namespace=%s&locale=%s',
                $base, $realmSlug, $guildNameLower, $namespace, $this->blizzardLocale
            );
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $response->toArray();
    }

    /**
     * Returns the rank (0 = GM, 1..n officers/members) for a character in the guild roster.
     * Returns null if not found.
     */
    public function getGuildRank(string $accessToken, string $realm, string $characterName, string $wowType = 'Retail'): ?int
    {
        $logFile = '/Users/dldvlpr/Dev/GuildeTracker/backend/var/debug.log';

        try {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] getGuildRank called: realm={$realm}, char={$characterName}, wowType={$wowType}\n", FILE_APPEND);

            $profile = $this->getCharacterProfile($accessToken, $realm, $characterName, $wowType);
            if (!isset($profile['guild'])) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Character {$characterName} has no guild in profile\n", FILE_APPEND);
                return null;
            }

            $guildName = $profile['guild']['name'] ?? null;
            if (!$guildName) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: No guild name found in profile for {$characterName}\n", FILE_APPEND);
                return null;
            }

            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Guild name: {$guildName}\n", FILE_APPEND);

            $guild = $this->getGuildDetails($accessToken, $realm, $guildName, $wowType);

            if (!isset($guild['members']) || !is_array($guild['members'])) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: No members array in guild roster for {$guildName} (wowType: {$wowType})\n", FILE_APPEND);
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Guild data keys: " . implode(', ', array_keys($guild)) . "\n", FILE_APPEND);
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Guild data (first 500 chars): " . substr(json_encode($guild), 0, 500) . "\n", FILE_APPEND);
                return null;
            }

            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Found " . count($guild['members']) . " members in roster\n", FILE_APPEND);

            $target = strtolower($characterName);
            foreach ($guild['members'] as $member) {
                $name = strtolower($member['character']['name'] ?? '');
                if ($name === $target) {
                    $rank = (int)($member['rank'] ?? 99);
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SUCCESS: Found {$characterName} with rank {$rank}\n", FILE_APPEND);
                    return $rank;
                }
            }
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Character {$characterName} not found in guild roster (searched for: {$target})\n", FILE_APPEND);

            // Log first 5 member names for debugging
            $memberNames = array_slice(array_map(fn($m) => strtolower($m['character']['name'] ?? 'unknown'), $guild['members']), 0, 5);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] First 5 members: " . implode(', ', $memberNames) . "\n", FILE_APPEND);

        } catch (\Throwable $e) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
        }
        return null;
    }

    /**
     * Import minimal roster information as GameCharacter placeholders.
     * This does not link users; it creates character rows for visibility.
     */
    public function importRosterIntoGuild(string $accessToken, string $realm, string $guildName, \App\Entity\GameGuild $gameGuild, string $wowType = 'Retail'): int
    {
        $logFile = '/Users/dldvlpr/Dev/GuildeTracker/backend/var/debug.log';

        try {
            $data = $this->getGuildDetails($accessToken, $realm, $guildName, $wowType);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: got guild data with " . count($data['members'] ?? []) . " members\n", FILE_APPEND);
        } catch (\Throwable $e) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: failed to get guild data: " . $e->getMessage() . "\n", FILE_APPEND);
            return 0;
        }

        if (!isset($data['members']) || !is_array($data['members'])) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: no members array\n", FILE_APPEND);
            return 0;
        }

        $existing = [];
        foreach ($gameGuild->getGameCharacters() as $ch) {
            $existing[strtolower($ch->getName() ?? '')] = true;
        }

        $created = 0;
        foreach ($data['members'] as $member) {
            $name = $member['character']['name'] ?? null;
            if (!$name) { continue; }
            $key = strtolower($name);
            if (isset($existing[$key])) { continue; }

            // Try multiple paths for class name
            $className = $member['character']['playable_class']['name']
                ?? $member['character']['character_class']['name']
                ?? $member['character']['class']['name']
                ?? 'Unknown';

            if ($created < 3) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: member structure: " . json_encode($member['character']) . "\n", FILE_APPEND);
            }

            $gc = new \App\Entity\GameCharacter();
            $gc->setName($name);
            $gc->setClass($className ?: 'Unknown');
            $gc->setClassSpec('Unknown');
            $gc->setRole('Unknown');
            $gc->setGuild($gameGuild);

            $this->em->persist($gc);
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: created {$created} characters\n", FILE_APPEND);
        }

        return $created;
    }

    public function isGuildMaster(string $accessToken, string $realm, string $characterName, string $wowType = 'Retail'): bool
    {
        try {
            $guildData = $this->getCharacterGuild($accessToken, $realm, $characterName, $wowType);

            if (!$guildData || !isset($guildData['members'])) {
                return false;
            }

            $charNameLower = strtolower($characterName);

            foreach ($guildData['members'] as $member) {
                $memberName = strtolower($member['character']['name']);
                if ($memberName === $charNameLower) {
                    return ($member['rank'] ?? 99) === 0;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getValidAccessToken(User $user): ?string
    {
        if (!$user->getBlizzardAccessToken()) {
            return null;
        }

        try {
            $token = $this->tokenEncryption->decrypt($user->getBlizzardAccessToken());
        } catch (\Throwable) {
            return null;
        }

        $expiresAt = $user->getBlizzardTokenExpiresAt();
        $now = new \DateTimeImmutable();

        if ($expiresAt && $expiresAt > $now->modify('+60 seconds')) {
            return $token;
        }

        return $this->refreshAccessToken($user);
    }

    private function refreshAccessToken(User $user): ?string
    {
        $encryptedRefreshToken = $user->getBlizzardRefreshToken();

        if (!$encryptedRefreshToken) {
            return null;
        }

        try {
            $refreshToken = $this->tokenEncryption->decrypt($encryptedRefreshToken);

            $tokenUrl = sprintf('https://%s.battle.net/oauth/token', $this->blizzardRegion);

            $response = $this->httpClient->request('POST', $tokenUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->blizzardClientId,
                    'client_secret' => $this->blizzardClientSecret,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            $newAccessToken = $data['access_token'] ?? null;
            $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
            $expiresIn = $data['expires_in'] ?? 86400;

            if (!$newAccessToken) {
                return null;
            }

            $encryptedAccessToken = $this->tokenEncryption->encrypt($newAccessToken);
            $encryptedNewRefreshToken = $this->tokenEncryption->encrypt($newRefreshToken);
            $expiresAt = new \DateTimeImmutable('+' . $expiresIn . ' seconds');

            $user->setBlizzardAccessToken($encryptedAccessToken);
            $user->setBlizzardRefreshToken($encryptedNewRefreshToken);
            $user->setBlizzardTokenExpiresAt($expiresAt);

            $this->em->persist($user);
            $this->em->flush();

            return $newAccessToken;
        } catch (\Throwable $e) {
            error_log("Blizzard token refresh failed: {$e->getMessage()}");
            return null;
        }
    }
}
