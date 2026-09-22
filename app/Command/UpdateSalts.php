<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes fresh WordPress salts into the project `.env` file.
 *
 * Salts are fetched from the WordPress.org secret-key API. Existing keys are
 * replaced in place; missing keys are appended. This command is not part of
 * `dokpress:setup`.
 */
class UpdateSalts extends Command
{
    /**
     * Absolute path to the project `.env` file.
     */
    private string $envFile;

    /**
     * Resolves the `.env` path from `WD_BASE_PATH`.
     */
    public function __construct()
    {
        parent::__construct();
        $this->envFile = WD_BASE_PATH . '/.env';
    }

    /**
     * Registers the `dokpress:update-salts` command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('dokpress:update-salts')
            ->setDescription('Update WordPress SALT keys in .env file');
    }

    /**
     * Fetches salts and writes them to `.env`.
     *
     * @param InputInterface  $input  Console input. This command accepts no arguments.
     * @param OutputInterface $output Console output.
     *
     * @return int `Command::SUCCESS` when `.env` is updated, otherwise `Command::FAILURE`.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $salts = @file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');
        if (!$salts) {
            $output->writeln('<error>Failed to fetch SALT keys from WordPress API.</error>');
            return Command::FAILURE;
        }

        if (!file_exists($this->envFile)) {
            $output->writeln("<error>.env file not found at {$this->envFile}</error>");
            return Command::FAILURE;
        }

        $envContent = file_get_contents($this->envFile);

        $patternMap = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
        ];

        foreach ($patternMap as $key) {
            if (preg_match("/define\(\s*'{$key}'\s*,\s*'(.+?)'\s*\);/", $salts, $match)) {
                $value = $match[1];
                if (preg_match("/^{$key}=.*$/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*$/m", "{$key}=\"{$value}\"", $envContent);
                } else {
                    $envContent .= "\n{$key}=\"{$value}\"";
                }
            }
        }

        file_put_contents($this->envFile, $envContent);
        $output->writeln('<info>Salt keys updated successfully in .env file</info>');

        return Command::SUCCESS;
    }
}
