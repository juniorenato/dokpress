<?php

namespace App\Command;

use App\Service\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeSetup extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('dokpress:theme-setup')
            ->setDescription('Install and build the theme defined by THEME_DIR');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $themeDir = (string) Environment::get('THEME_DIR', '');

        if ($themeDir === '') {
            $io->warning('THEME_DIR is empty. Set it in .env to build a theme.');
            return Command::SUCCESS;
        }

        $themePath = WD_BASE_PATH . '/' . ltrim($themeDir, '/');

        if (!is_dir($themePath)) {
            $io->error('Theme directory not found: ' . $themePath);
            return Command::FAILURE;
        }

        $commands = [];

        if (is_file($themePath . '/composer.json')) {
            $commands['composer install'] = ['composer', 'install', '--no-interaction', '--prefer-dist'];
        }

        if (is_file($themePath . '/package.json')) {
            $commands['npm ci'] = ['npm', 'ci'];
            $commands['npm run build'] = ['npm', 'run', 'build'];
        }

        if ($commands === []) {
            $io->warning('No composer.json or package.json in ' . $themePath);
            return Command::SUCCESS;
        }

        foreach ($commands as $label => $cmd) {
            $process = new Process($cmd, $themePath);
            $process->setTimeout(null);

            $io->text('<info>Running:</info> ' . $label);

            $process->run(function ($type, $buffer) use ($io) {
                $io->text($buffer);
            });

            if (!$process->isSuccessful()) {
                $io->error('Command failed: ' . $label);
                $io->text($process->getErrorOutput());
                return Command::FAILURE;
            }

            $io->text('<comment>' . $label . ' completed.</comment>');
            $io->newLine();
        }

        $io->success('Theme setup completed successfully!');

        return Command::SUCCESS;
    }
}
