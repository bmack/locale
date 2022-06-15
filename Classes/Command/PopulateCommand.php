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

use B13\Locale\Database\Migrations\PopulateLocales;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initial populate all missing sys_locale fields.
 */
class PopulateCommand extends Command
{
    protected $migration;

    public function __construct(PopulateLocales $migration = null, string $name = null)
    {
        $this->migration = $migration ?? GeneralUtility::makeInstance(PopulateLocales::class);
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->migration->populate();
        $io->success('Updated all locales');
    }
}
