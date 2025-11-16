<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-database',
    description: 'Initialize database with schema and essential data (WoW raids, realms, etc.)',
)]
class InitDatabaseCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('drop', null, InputOption::VALUE_NONE, 'Drop database before creating (WARNING: destroys all data)')
            ->addOption('skip-wow-data', null, InputOption::VALUE_NONE, 'Skip WoW raids/bosses import')
            ->addOption('skip-realms', null, InputOption::VALUE_NONE, 'Skip realm metadata population');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $drop = $input->getOption('drop');
        $skipWowData = $input->getOption('skip-wow-data');
        $skipRealms = $input->getOption('skip-realms');

        $io->title('🚀 GuildTracker Database Initialization');

        if ($drop) {
            $io->warning('You are about to DROP the entire database and recreate it!');
            if (!$io->confirm('Are you sure you want to continue?', false)) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $steps = [];

        if ($drop) {
            $steps[] = ['Drop database', 'doctrine:database:drop', ['--force' => true, '--if-exists' => true]];
        }

        $steps[] = ['Create database', 'doctrine:database:create', ['--if-not-exists' => true]];

        $steps[] = ['Run migrations', 'doctrine:migrations:migrate', ['--no-interaction' => true]];

        if (!$skipWowData) {
            $steps[] = ['Import WoW raids & bosses', 'app:import-wow-raids-db', ['--truncate' => true]];
        }

        if (!$skipRealms) {
            $steps[] = ['Populate realm metadata', 'app:populate-realm-metadata', []];
        }

        $io->section('📋 Execution Plan');
        $io->listing(array_column($steps, 0));

        if (!$io->confirm('Proceed with initialization?', true)) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $totalSteps = count($steps);
        $currentStep = 0;

        foreach ($steps as [$description, $command, $arguments]) {
            $currentStep++;
            $io->section("[$currentStep/$totalSteps] $description");

            try {
                $returnCode = $this->runCommand($command, $arguments, $output);

                if ($returnCode !== Command::SUCCESS) {
                    $io->error("Command '$command' failed with code $returnCode");
                    return Command::FAILURE;
                }

                $io->success("✓ $description completed");
            } catch (\Exception $e) {
                $io->error("Error in step '$description': " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->newLine(2);
        $io->success('🎉 Database initialization completed successfully!');

        $io->section('📊 Summary');
        $io->table(
            ['Component', 'Status'],
            [
                ['Database schema', '✅ Ready'],
                ['WoW raids & bosses', $skipWowData ? '⏭️  Skipped' : '✅ Imported'],
                ['Realm metadata', $skipRealms ? '⏭️  Skipped' : '✅ Populated'],
            ]
        );

        $io->newLine();
        $io->note([
            'Your database is now ready!',
            'You can start the development server with: symfony server:start',
            'Frontend dev server: npm run dev (in frontend directory)',
        ]);

        return Command::SUCCESS;
    }

    private function runCommand(string $name, array $arguments, OutputInterface $output): int
    {
        $command = $this->getApplication()->find($name);
        $arguments['command'] = $name;
        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
