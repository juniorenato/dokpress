<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs the project setup commands in order.
 *
 * Invoked by Composer `post-install-cmd`. Copies configuration files and
 * deploys WordPress. Salt refresh and theme build stay as separate commands.
 */
class DokpressSetup extends Command
{
    /**
     * Registers the `dokpress:setup` command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('dokpress:setup')
            ->setDescription('Run project setup commands');
    }

    /**
     * Copies configuration files, then deploys WordPress.
     *
     * Stops at the first child command that does not return success.
     *
     * @param InputInterface  $input  Console input. This command accepts no arguments.
     * @param OutputInterface $output Console output shared with each child command.
     *
     * @return int `Command::SUCCESS` when every step succeeds, otherwise `Command::FAILURE`.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting project setup...');

        $commands = [
            'Copying project files' => 'dokpress:copy-config-files',
            //'Updating Salts'        => 'dokpress:update-salts',
            'Set Wordpress config'  => 'dokpress:wordpress-deploy',
        ];

        $io->progressStart(count($commands));

        foreach ($commands as $title => $commandName) {
            $io->newLine();
            $io->section($title);

            $command = $this->getApplication()->find($commandName);

            $result = $command->run(new ArrayInput([]), $output);

            if ($result !== Command::SUCCESS) {
                $output->writeln('<error>Error:</error> ' . $commandName);
                return Command::FAILURE;
            }

            $io->progressAdvance();
            $io->newLine();
        }

        $io->progressFinish();
        $io->success('Setup completed successfully!');
        return Command::SUCCESS;
    }
}
