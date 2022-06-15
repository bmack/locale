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

/**
 * Wrapper around some common TCA-related questions regarding localization.
 */
class LocalizableTableProvider
{
    public function getAllLocalizableTables(): array
    {
        $localizableTables = [];
        foreach ($GLOBALS['TCA'] as $table => $configuration) {
            if ($this->isLocalizableTable($table)) {
                $localizableTables[] = $table;
            }
        }
        return $localizableTables;
    }

    public function isLocalizableTable(string $table): bool
    {
        return isset($GLOBALS['TCA'][$table]['ctrl']['languageField']);
    }

    public function getLanguageIdField(string $table): string
    {
        if ($this->isLocalizableTable($table)) {
            return $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        }
        throw new \RuntimeException('Table ' . $table . ' is not localizable', 1604665764);
    }

    public function getTranslationPointerField(string $table): string
    {
        if ($this->isLocalizableTable($table)) {
            return $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        throw new \RuntimeException('Table ' . $table . ' is not localizable', 1604665766);
    }
}
