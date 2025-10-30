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

    public function getCharacterProfile(string $accessToken, string $realm, string $characterName): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $namespace = sprintf('profile-%s', $this->blizzardRegion);
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

    public function getCharacterGuild(string $accessToken, string $realm, string $characterName): ?array
    {
        $profile = $this->getCharacterProfile($accessToken, $realm, $characterName);

        if (!isset($profile['guild'])) {
            return null;
        }

        return $this->getGuildDetails($accessToken, $realm, $profile['guild']['name']);
    }

    public function getGuildDetails(string $accessToken, string $realm, string $guildName): array
    {
        $base = sprintf('https://%s.api.blizzard.com', $this->blizzardRegion);
        $namespace = sprintf('profile-%s', $this->blizzardRegion);
        $realmSlug = strtolower(str_replace(' ', '-', $realm));
        $guildNameLower = strtolower($guildName);

        $url = sprintf('%s/data/wow/guild/%s/%s/roster?namespace=%s&locale=%s',
            $base, $realmSlug, $guildNameLower, $namespace, $this->blizzardLocale
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $response->toArray();
    }

    public function isGuildMaster(string $accessToken, string $realm, string $characterName): bool
    {
        try {
            $guildData = $this->getCharacterGuild($accessToken, $realm, $characterName);

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
