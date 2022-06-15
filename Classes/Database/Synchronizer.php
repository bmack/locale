<?php
declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "locale" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Locale\Database;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Synchronizes sys_locale for records, tables, or the whole database.
 */
class Synchronizer
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

    public function synchronizeDatabase(): void
    {
        foreach ($this->provider->getAllLocalizableTables() as $table) {
            $this->synchronizeTable($table);
        }
    }

    /**
     * Overrides all values of a table, regardless of what was stored before. Useful if you want to ensure integrity
     * or just populate a record.
     *
     * @param $table
     */
    public function synchronizeTable(string $table): void
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        $statement = $this->getDatabaseConnection($table)->select(['*'], $table);
        while ($record = $statement->fetch()) {
            $this->synchronizeRecordByTableAndRecord($table, $record);
        }
    }

    /**
     * Main method usable by everyone.
     *
     * @param $table
     * @param int $uid
     */
    public function synchronizeRecordByTableAndId($table, int $uid): void
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        // Fetch raw record
        $connection = $this->getDatabaseConnection($table);
        $record = $connection->select(['*'], $table, ['uid' => $uid]);
        if (is_array($record)) {
            $this->synchronizeRecordByTableAndRecord($table, $record);
        }
    }

    /**
     * Expects a full row in $record, not just a couple of fields.
     *
     * @param string $table
     * @param array $record
     */
    public function synchronizeRecordByTableAndRecord(string $table, array $record): void
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        $locale = $this->localeDetector->getValidLocaleForRecord($table, $record);
        if ($locale !== $record['sys_locale']) {
            $this->updateRecord($table, (int)$record['uid'], $locale);
        }
    }

    protected function updateRecord(string $table, int $uid, string $locale)
    {
        $this->getDatabaseConnection($table)->update(
            $table,
            [
                'sys_locale' => $locale
            ],
            [
                'uid' => $uid,
            ]
        );
    }

    protected function getDatabaseConnection(string $table): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }
}
