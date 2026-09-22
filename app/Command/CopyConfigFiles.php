<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Copies Dokpress configuration into the WordPress tree.
 *
 * `.env.example` is copied to `.env` only when `.env` is missing.
 * `app/wp.php` is always copied to `public/wp-core/wp-config.php`.
 * The Redis drop-in is copied when the redis-cache plugin is installed.
 */
class CopyConfigFiles extends Command
{
    /**
     * Files copied only when the destination does not exist.
     *
     * Keys are source paths and values are destination paths, both relative
     * to `WD_BASE_PATH`.
     *
     * @var array<string, string>
     */
    protected array $once = [
        '/.env.example' => '/.env',
    ];

    /**
     * Files copied on every run, overwriting the destination.
     *
     * Keys are source paths and values are destination paths, both relative
     * to `WD_BASE_PATH`.
     *
     * @var array<string, string>
     */
    protected array $always = [
        '/app/wp.php' => '/public/wp-core/wp-config.php',
    ];

    /**
     * Registers the `dokpress:copy-config-files` command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('dokpress:copy-config-files')
            ->setDescription('Copy wp-config.php to public/wp-core/');
    }

    /**
     * Copies the configured files and the Redis object-cache drop-in.
     *
     * @param InputInterface  $input  Console input. This command accepts no arguments.
     * @param OutputInterface $output Console output.
     *
     * @return int `Command::SUCCESS` when every required copy succeeds, otherwise `Command::FAILURE`.
     */
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
