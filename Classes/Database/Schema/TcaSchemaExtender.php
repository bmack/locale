<?php
declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "locale" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Locale\Database\Schema;

use B13\Locale\Database\LocalizableTableProvider;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds a sys_locale field to all TCA tables which are localizable
 */
class TcaSchemaExtender
{
    /**
     * @var LocalizableTableProvider
     */
    protected $provider;

    /**
     * @var string
     */
    protected $schemaString = 'CREATE TABLE %s (sys_locale varchar(20) DEFAULT NULL);';

    public function __construct(LocalizableTableProvider $provider = null)
    {
        $this->provider = $provider ?? GeneralUtility::makeInstance(LocalizableTableProvider::class);
    }

    public function addLocalesToTcaColumns(AlterTableDefinitionStatementsEvent $event): void
    {
        // Fetch all TCA tables with translations
        foreach ($this->provider->getAllLocalizableTables() as $table) {
            $event->addSqlData(sprintf($this->schemaString, $table));
        }
    }
}
