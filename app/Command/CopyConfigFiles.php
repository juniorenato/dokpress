<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class CopyConfigFiles extends Command
{
    protected array $once = [
        '/.env.example' => '/.env',
    ];

    protected array $always = [
        '/app/wp.php' => '/public/wp-core/wp-config.php',
    ];

    protected function configure(): void
    {
        $this
            ->setName('dokpress:copy-config-files')
            ->setDescription('Copy wp-config.php to public/wp-core/');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        foreach ($this->once as $source => $target) {
            $sourcePath = WD_BASE_PATH . $source;
            $targetPath = WD_BASE_PATH . $target;

            if (!$fs->exists($sourcePath)) {
                $io->error('File `' . $sourcePath . '` was not found.');
                return Command::FAILURE;
            }

            if (!$fs->exists($targetPath)) {
                try {
                    $fs->copy($sourcePath, $targetPath, true);
                    $output->writeln('<info>' . $sourcePath . ' copied to ' . $targetPath . '</info>');
                } catch (\Throwable $e) {
                    $io->error('Error copying file: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }

        foreach ($this->always as $source => $target) {
            $sourcePath = WD_BASE_PATH . $source;
            $targetPath = WD_BASE_PATH . $target;

            if (!$fs->exists($sourcePath)) {
                $io->error('File `' . $sourcePath . '` was not found.');
                return Command::FAILURE;
            }

            try {
                $fs->copy($sourcePath, $targetPath, true);
                $output->writeln('<info>' . $sourcePath . ' copied to ' . $targetPath . '</info>');
            } catch (\Throwable $e) {
                $io->error('Error copying file: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $objectCacheSource = WD_BASE_PATH . '/public/wp-content/plugins/redis-cache/includes/object-cache.php';
        $objectCacheTarget = WD_BASE_PATH . '/public/wp-content/object-cache.php';
        if ($fs->exists($objectCacheSource)) {
            try {
                $fs->copy($objectCacheSource, $objectCacheTarget, true);
                $output->writeln('<info>' . $objectCacheSource . ' copied to ' . $objectCacheTarget . '</info>');
            } catch (\Throwable $e) {
                $io->error('Error copying Redis object cache: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
