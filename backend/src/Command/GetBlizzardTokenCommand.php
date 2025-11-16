<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:get-blizzard-token',
    description: 'Get a client credentials token from Blizzard API',
)]
class GetBlizzardTokenCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $blizzardClientId,
        private readonly string $blizzardClientSecret,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Getting Blizzard OAuth Token');

        $tokenUrl = 'https://eu.battle.net/oauth/token';

        try {
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
            $token = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 0;

            if (!$token) {
                $io->error('Failed to get token from response');
                return Command::FAILURE;
            }

            $io->success('Token retrieved successfully!');
            $io->section('Access Token:');
            $io->text($token);
            $io->newLine();
            $io->text("Expires in: {$expiresIn} seconds (" . ($expiresIn / 3600) . " hours)");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to get token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
