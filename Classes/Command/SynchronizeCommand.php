<?php
declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "locale" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Locale\Command;

use B13\Locale\Database\Synchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ensures the integrity of the locales.
 * Checks all set locales and sees if there are updates needed
 */
class SynchronizeCommand extends Command
{
    protected $synchronizer;

    public function __construct(Synchronizer $synchronizer, string $name = null)
    {
        $this->synchronizer = $synchronizer ?? GeneralUtility::makeInstance(Synchronizer::class);
        parent::__construct($name);
    }

    public function configure()
    {
        $this->addOption(
            'tables',
            't',
            InputOption::VALUE_IS_ARRAY & InputOption::VALUE_REQUIRED
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $tables = $input->getOption('tables');
        if (empty($tables)) {
            $this->synchronizer->synchronizeDatabase();
            $io->success('Updated all locales');
        } else {
            foreach ($tables as $table) {
                $this->synchronizer->synchronizeTable($table);
                $io->success('Synchronized locales for ' . $table);
            }
        }
    }
}
