<?php

namespace App\Command;

use App\Service\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Installs WordPress when needed and applies the Dokpress baseline.
 *
 * The baseline activates maintenance mode, installs Brazilian Portuguese,
 * flushes rewrite rules, updates the database, shuffles the salts written
 * in `wp-config.php`, refreshes languages and leaves maintenance mode.
 */
class WordpressDeploy extends Command
{
    /**
     * Registers the `dokpress:wordpress-deploy` command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('dokpress:wordpress-deploy')
            ->setDescription('Install WordPress core, set language, flush rewrite rules and update database.');
    }

    /**
     * Installs WordPress if needed, then runs the baseline WP-CLI commands.
     *
     * A missing site is installed with `APP_URL`, `APP_NAME` and `WP_ADMIN_*`.
     * Each WP-CLI process must succeed before the next one starts.
     *
     * @param InputInterface  $input  Console input. This command accepts no arguments.
     * @param OutputInterface $output Console output.
     *
     * @return int `Command::SUCCESS` when every WP-CLI command succeeds, otherwise `Command::FAILURE`.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if WordPress is installed
        $checkInstall = new Process(['wp', 'core', 'is-installed', '--path=public/wp-core', '--allow-root']);
        $checkInstall->run();

        if (!$checkInstall->isSuccessful()) {
            $domain = Environment::get('APP_URL', 'https://localhost');
            $name   = Environment::get('APP_NAME', 'WordPress Site');
            $user   = Environment::get('WP_ADMIN_USER', 'admin');
            $pass   = Environment::get('WP_ADMIN_PASSWORD', 'admin123');
            $email  = Environment::get('WP_ADMIN_EMAIL', 'admin@example.com');

            $io->text('WordPress is not installed!');
            $io->text("Installing with user: {$user}");

            $commands[] = [
                'wp',
                'core',
                'install',
                '--url=' . $domain,
                '--title=' . $name,
                '--admin_user=' . $user,
                '--admin_password=' . $pass,
                '--admin_email=' . $email,
                '--path=public/wp-core',
                '--allow-root',
            ];
        }

        $commands[] = ['wp', 'maintenance-mode', 'activate', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'language', 'core', 'install', 'pt_BR', '--activate', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'language', 'plugin', 'install', 'pt_BR', '--all', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'rewrite', 'flush', '--hard', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'core', 'update-db', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'config', 'shuffle-salts', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'plugin', 'list', '--status=active', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'theme', 'list', '--status=active', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'transient', 'delete', '--expired', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'language', 'core', 'update', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'language', 'plugin', 'update', '--all', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'language', 'theme', 'update', '--all', '--path=public/wp-core', '--allow-root'];
        $commands[] = ['wp', 'maintenance-mode', 'deactivate', '--path=public/wp-core', '--allow-root'];

        foreach ($commands as $cmd) {
            $process = new Process($cmd);
            $process->setTimeout(null);

            $io->text('<info>Running:</info> ' . implode(' ', $cmd));

            $process->run(function ($type, $buffer) use ($io) {
                $io->text($buffer);
            });

            if (!$process->isSuccessful()) {
                $output->writeln('<error>Error:</error> ' . $process->getErrorOutput());
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>WordPress deployment completed successfully!</info>');
        return Command::SUCCESS;
    }
}
