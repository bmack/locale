<?php
declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "locale" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Locale\Database\Migrations;

use B13\Locale\Database\LocaleDetector;
use B13\Locale\Database\LocalizableTableProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fills all locale fields based on the site configuration.
 *
 * This is very process is very "graceful" to the system, as it does not override locales,
 * and does grouping so the update is done in a smooth and quick way.
 */
class PopulateLocales
{
    /**
     * @var LocalizableTableProvider
     */
    protected $provider;

    /**
     * @var LocaleDetector
     */
    protected $localeDetector;

    public function __construct(LocalizableTableProvider $provider = null, LocaleDetector $localeDetector = null)
    {
        $this->provider = $provider ?? GeneralUtility::makeInstance(LocalizableTableProvider::class);
        $this->localeDetector = $localeDetector ?? GeneralUtility::makeInstance(LocaleDetector::class);
    }

    public function populate(bool $force = false): void
    {
        foreach ($this->provider->getAllLocalizableTables() as $table) {
            $languageIdField = $this->provider->getLanguageIdField($table);
            $queryBuilder = $this->getDatabaseConnection($table)->createQueryBuilder();
            // Fetch all unassigned values, we could do a group by page + language
            $queryBuilder
                ->select(
                    'pid',
                    $languageIdField
                )
                ->from($table)
                ->groupBy(
                    'pid',
                    $languageIdField
                );

            // If force is not set, only populate the ones that do not have a locale yet. (e.g. on initial filling)
            // force is useful for overriding data in a quick manner, but also check out the synchronizer.
            if (!$force) {
                $queryBuilder->where($queryBuilder->expr()->isNull('sys_locale'));
            }
            if ($table === 'pages') {
                $queryBuilder->addSelect($this->provider->getTranslationPointerField($table));
            }

            $statement = $queryBuilder->execute();
            while ($record = $statement->fetch()) {
                $this->updateLocaleForRecords($table, $record);
            }
        }
    }

    protected function updateLocaleForRecords(string $table, array $record): void
    {
        $locale = $this->localeDetector->getValidLocaleForRecord($table, $record);
        $languageIdField = $this->provider->getLanguageIdField($table);
        $this->getDatabaseConnection($table)->update(
            $table,
            [
                'sys_locale' => $locale
            ],
            [
                'pid' => (int)$record['pid'],
                $languageIdField => (int)$record[$languageIdField]
            ]
        );
    }

    protected function getDatabaseConnection(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }
}
