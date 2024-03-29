<?php

namespace Orchestra\DuskUpdater;

use Composer\Semver\Comparator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @copyright Originally created by Jonas Staudenmeir: https://github.com/staudenmeir/dusk-updater
 */
class DetectCommand extends Command
{
    use Concerns\DetectsChromeVersion;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $directory = getcwd().'/vendor/laravel/dusk/bin/';

        $this->setName('detect')
                ->setDescription('Detect the installed Chrome/Chromium version.')
                ->addOption('chrome-dir', null, InputOption::VALUE_OPTIONAL, 'Detect the installed Chrome/Chromium version, optionally in a custom path')
                ->addOption('install-dir', null, InputOption::VALUE_OPTIONAL, 'Install a ChromeDriver binary in this directory', $directory)
                ->addOption('auto-update', null, InputOption::VALUE_NONE, 'Auto update ChromeDriver binary if outdated');
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Input\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $chromeDirectory = $input->getOption('chrome-dir');
        $driverDirectory = $input->getOption('install-dir');
        $autoUpdate = $input->getOption('auto-update');

        $currentOS = OperatingSystem::id();

        $chromeVersions = $this->installedChromeVersion($currentOS, $chromeDirectory);
        $driverVersions = $this->installedChromeDriverVersion($currentOS, $driverDirectory);

        $updated = Comparator::equalTo($driverVersions['semver'], $chromeVersions['semver']);

        $io->table(['Installed', 'Version'], [
            ['Chrome/Chromium', $chromeVersions['semver']],
            ['ChromeDriver', $driverVersions['semver']],
        ]);

        if (! $updated) {
            $io->caution('ChromeDriver is outdated!');
            if ($autoUpdate || $io->confirm('Do you want to update ChromeDriver?')) {
                $this->updateChromeDriver($output, $driverDirectory, $chromeVersions['major']);
            }
        }
    }

    /**
     * Update ChromeDriver.
     *
     * @param \Symfony\Component\Console\Input\OutputInterface $output
     * @param  string          $directory
     * @param  int             $version
     *
     * @return int
     */
    protected function updateChromeDriver(OutputInterface $output, string $directory, int $version): int
    {
        $command = $this->getApplication()->find('update');

        $arguments = [
            'command' => 'update',
            'version' => $version,
            '--install-dir' => $directory,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
