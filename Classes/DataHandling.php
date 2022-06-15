<?php
declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "locale" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Locale;

use B13\Locale\Database\LocaleDetector;
use B13\Locale\Database\LocalizableTableProvider;
use B13\Locale\Database\Synchronizer;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks into DataHandler and updates all records that have been modified
 * to also update sys_language_uid fields of records.
 *
 * 1. When we created a record, the sys_locale record should be populated
 * 2. When we moved a record, we need to update the locale (because of a possible site language change).
 * 3. When we copied a record (e.g. to a different page / site)
 * 4. When we localized a record of course (copy).
 */
class DataHandling
{
    /**
     * @var LocaleDetector
     */
    protected $localeDetector;

    /**
     * @var LocalizableTableProvider
     */
    protected $provider;

    public function __construct(LocalizableTableProvider $provider = null, LocaleDetector $localeDetector = null)
    {
        $this->provider = $provider ?? GeneralUtility::makeInstance(LocalizableTableProvider::class);
        $this->localeDetector = $localeDetector ?? GeneralUtility::makeInstance(LocaleDetector::class);
    }

    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, DataHandler $dataHandler)
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        if ($status === 'new') {
            $fieldArray['sys_locale'] = $this->localeDetector->getValidLocaleForRecord($table, $fieldArray);
        } elseif (isset($fieldArray[$this->provider->getLanguageIdField($table)])) {
            // updating a record where the sys_language record was modified
            $record = $fieldArray;
            $record['uid'] = (int)$id;
            $fieldArray['sys_locale'] = $this->localeDetector->getValidLocaleForRecord($table, $record);
        }
    }

    public function moveRecord_firstElementPostProcess($table, $uid, $destPid, $moveRec, $updateFields, DataHandler $dataHandler)
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        GeneralUtility::makeInstance(Synchronizer::class)->synchronizeRecordByTableAndId($table, $uid);
    }

    public function moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $moveRec, $updateFields, DataHandler $dataHandler)
    {
        if (!$this->provider->isLocalizableTable($table)) {
            return;
        }
        GeneralUtility::makeInstance(Synchronizer::class)->synchronizeRecordByTableAndId($table, $uid);
    }
}
