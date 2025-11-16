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
        private WowClassMapper $classMapper,
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

    
    public function getCharacterSpecialization(string $accessToken, string $realm, string $characterName, string $wowType = 'Retail'): ?array
    {
        try {
            $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);

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

            $url = sprintf('%s/profile/wow/character/%s/%s/specializations?namespace=%s&locale=%s',
                $base, $realmSlug, $charNameLower, $namespace, $this->blizzardLocale
            );

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $data = $response->toArray();

            $activeSpec = null;

            if (isset($data['active_specialization']['name'])) {
                $activeSpec = $data['active_specialization']['name'];
            }

            elseif (isset($data['specialization_groups'])) {
                foreach ($data['specialization_groups'] as $group) {
                    if (isset($group['is_active']) && $group['is_active'] && isset($group['specializations'])) {

                        $maxPoints = 0;
                        $mainSpec = null;

                        foreach ($group['specializations'] as $spec) {
                            $points = $spec['spent_points'] ?? 0;
                            if ($points > $maxPoints) {
                                $maxPoints = $points;
                                $mainSpec = $spec['specialization_name'] ?? null;
                            }
                        }

                        if ($mainSpec) {
                            $activeSpec = $mainSpec;
                            break;
                        }
                    }
                }
            }

            elseif (isset($data['specializations'])) {
                foreach ($data['specializations'] as $spec) {
                    if (isset($spec['specialization']['name'])) {
                        $activeSpec = $spec['specialization']['name'];
                        break;
                    }
                }
            }

            if (!$activeSpec) {
                return null;
            }

            $role = $this->classMapper->getRoleFromSpec($activeSpec);

            return [
                'spec' => $activeSpec,
                'role' => $role,
            ];
        } catch (\Throwable $e) {

            return null;
        }
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


        $isClassic = str_contains($wowType, 'Classic');

        if ($isClassic) {
            $namespace = match (true) {
                str_contains($wowType, 'Classic Anniversary') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                str_contains($wowType, 'Classic Era') => sprintf('profile-classic-%s', $this->blizzardRegion),
                str_contains($wowType, 'Season of Discovery') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                str_contains($wowType, 'Hardcore') => sprintf('profile-classic1x-%s', $this->blizzardRegion),
                default => sprintf('profile-classic1x-%s', $this->blizzardRegion),
            };

            $url = sprintf('%s/data/wow/guild/%s/%s/roster?namespace=%s&locale=%s',
                $base, $realmSlug, $guildNameLower, $namespace, $this->blizzardLocale
            );
        } else {

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

            $memberNames = array_slice(array_map(fn($m) => strtolower($m['character']['name'] ?? 'unknown'), $guild['members']), 0, 5);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] First 5 members: " . implode(', ', $memberNames) . "\n", FILE_APPEND);

        } catch (\Throwable $e) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
        }
        return null;
    }

    
    public function syncGuildRoster(string $accessToken, string $realm, string $guildName, \App\Entity\GameGuild $gameGuild, string $wowType = 'Retail', bool $fetchSpecs = true, bool $preserveManualRoles = true): array
    {
        $logFile = '/Users/dldvlpr/Dev/GuildeTracker/backend/var/debug.log';

        try {
            $data = $this->getGuildDetails($accessToken, $realm, $guildName, $wowType);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] syncRoster: got guild data with " . count($data['members'] ?? []) . " members\n", FILE_APPEND);
        } catch (\Throwable $e) {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] syncRoster: failed to get guild data: " . $e->getMessage() . "\n", FILE_APPEND);
            return ['created' => 0, 'updated' => 0, 'removed' => 0];
        }

        if (!isset($data['members']) || !is_array($data['members'])) {
            return ['created' => 0, 'updated' => 0, 'removed' => 0];
        }

        $existingCharacters = [];
        foreach ($gameGuild->getGameCharacters() as $ch) {
            $existingCharacters[strtolower($ch->getName() ?? '')] = $ch;
        }

        $currentRosterNames = [];
        $created = 0;
        $updated = 0;

        foreach ($data['members'] as $member) {
            $name = $member['character']['name'] ?? null;
            if (!$name) { continue; }

            $key = strtolower($name);
            $currentRosterNames[] = $key;

            $classId = $member['character']['playable_class']['id']
                ?? $member['character']['character_class']['id']
                ?? $member['character']['class']['id']
                ?? null;

            $className = $classId ? $this->classMapper->getClassName($classId) : 'Unknown';

            $raceId = $member['character']['playable_race']['id']
                ?? $member['character']['race']['id']
                ?? null;

            $raceName = $raceId ? $this->classMapper->getRaceName($raceId) : 'Unknown';

            $spec = 'Unknown';
            $role = 'Unknown';

            if ($fetchSpecs) {
                $specData = $this->getCharacterSpecialization($accessToken, $realm, $name, $wowType);
                if ($specData) {
                    $spec = $specData['spec'];
                    $role = $specData['role'];
                }
            }

            if (isset($existingCharacters[$key])) {
                $character = $existingCharacters[$key];

                $character->setClass($className);
                $character->setClassSpec($spec);

                if (!$preserveManualRoles || $character->getRole() === 'Unknown') {
                    $character->setRole($role);
                }

                $this->em->persist($character);
                $updated++;
            } else {

                $character = new \App\Entity\GameCharacter();
                $character->setName($name);
                $character->setClass($className);
                $character->setClassSpec($spec);
                $character->setRole($role);
                $character->setGuild($gameGuild);

                $this->em->persist($character);
                $created++;
            }
        }

        $removed = 0;



        $this->em->flush();

        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] syncRoster: created {$created}, updated {$updated}\n", FILE_APPEND);

        return [
            'created' => $created,
            'updated' => $updated,
            'removed' => $removed,
        ];
    }

    
    public function importRosterIntoGuild(string $accessToken, string $realm, string $guildName, \App\Entity\GameGuild $gameGuild, string $wowType = 'Retail', bool $fetchSpecs = true): int
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

            $classId = $member['character']['playable_class']['id']
                ?? $member['character']['character_class']['id']
                ?? $member['character']['class']['id']
                ?? null;

            $className = $classId ? $this->classMapper->getClassName($classId) : 'Unknown';

            $raceId = $member['character']['playable_race']['id']
                ?? $member['character']['race']['id']
                ?? null;

            $raceName = $raceId ? $this->classMapper->getRaceName($raceId) : 'Unknown';

            $spec = 'Unknown';
            $role = 'Unknown';

            if ($fetchSpecs) {
                $specData = $this->getCharacterSpecialization($accessToken, $realm, $name, $wowType);
                if ($specData) {
                    $spec = $specData['spec'];
                    $role = $specData['role'];
                }
            }

            if ($created < 3) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: member structure: " . json_encode($member['character']) . "\n", FILE_APPEND);
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] importRoster: mapped class ID {$classId} to '{$className}', race ID {$raceId} to '{$raceName}', spec: '{$spec}', role: '{$role}'\n", FILE_APPEND);
            }

            $gc = new \App\Entity\GameCharacter();
            $gc->setName($name);
            $gc->setClass($className);
            $gc->setClassSpec($spec);
            $gc->setRole($role);
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
