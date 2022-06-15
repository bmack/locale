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

use B13\Locale\Database\LocaleDetector;
use B13\Locale\Database\LocalizableTableProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ensures the integrity of the locales.
 * This is useful if somebody from external might have modified a locale, and we need to re-synchronize this.
 */
class IntegrityCommand extends Command
{
    protected $provider;
    protected $localeDetector;

    public function __construct(LocalizableTableProvider $provider = null, LocaleDetector $localeDetector = null, string $name = null)
    {
        $this->provider = $provider ?? GeneralUtility::makeInstance(LocalizableTableProvider::class);
        $this->localeDetector = $localeDetector ?? GeneralUtility::makeInstance(LocaleDetector::class);
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $itemsWithoutLocale = [];
        $itemsWithWrongLocale = [];
        foreach ($this->provider->getAllLocalizableTables() as $table) {
            $queryBuilder = $this->getDatabaseConnection($table)->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder
                ->select('*')
                ->from($table)
                ->orderBy($this->provider->getLanguageIdField($table))
                ->execute();
            // Find all items without a locale
            while ($record = $statement->fetch()) {
                if ($record['sys_locale'] === null) {
                    $itemsWithoutLocale[] = $table . ':' . $record['uid'] . ' (PID ' . $record['pid'] . ')';
                } else {
                    // Check if the locale matches the one stored in the database
                    $locale = $this->localeDetector->getValidLocaleForRecord($table, $record);
                    if ($locale !== $record['sys_locale']) {
                        $itemsWithWrongLocale[] = $table . ':' . $record['uid'] . ' (record is "' . $record['sys_locale'] . '" should be "' . ($locale ?? 'null') . '")';
                    }
                }
            }
        }

        if (!empty($itemsWithoutLocale)) {
            natcasesort($itemsWithoutLocale);
            $io->section('Found ' . count($itemsWithWrongLocale) . ' items without a locale');
            $io->listing($itemsWithoutLocale);
        }

        if (!empty($itemsWithWrongLocale)) {
            natcasesort($itemsWithWrongLocale);
            $io->section('Found ' . count($itemsWithWrongLocale) . ' items with a wrong locale');
            $io->listing($itemsWithWrongLocale);
        }

        if (empty($itemsWithoutLocale) && empty($itemsWithWrongLocale)) {
            $io->success('Locale integrity is perfect');
        }
    }

    protected function getDatabaseConnection(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }
}
