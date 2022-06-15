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

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Find the right locale for a given record based on the language of the record and the page ID (= resolving the site).
 */
class LocaleDetector
{
    /**
     * @var LocalizableTableProvider
     */
    protected $provider;

    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    public function __construct(LocalizableTableProvider $provider = null, SiteFinder $siteFinder = null)
    {
        $this->provider = $provider ?? GeneralUtility::makeInstance(LocalizableTableProvider::class);
        $this->siteFinder = $siteFinder ?? GeneralUtility::makeInstance(SiteFinder::class);
    }

    public function getValidLocaleForRecord(string $table, array $record): ?string
    {
        $locale = null;

        if ($table === 'pages') {
            $pageId = (int)($record[$this->provider->getTranslationPointerField($table)] ?: $record['uid']);
        } else {
            $pageId = (int)$record['pid'];
        }
        $languageIdField = $this->provider->getLanguageIdField($table);
        $languageId = (int)$record[$languageIdField];
        if ($languageId === -1) {
            $locale = 't3_all';
        } elseif ($pageId === 0) {
            // Rootlevel, what should we do?
            $locale = null;
        } elseif ($pageId === -1) {
            // v9 with workspaces => do your magic
            $locale = null;
        } else {
            try {
                $site = $this->siteFinder->getSiteByPageId($pageId);
                $languageDetails = $site->getLanguageById($languageId);
                $locale = $languageDetails->getLocale();
            } catch (SiteNotFoundException $exception) {
                // @todo: what to do here?
                $locale = null;
            } catch (\InvalidArgumentException $exception) {
                // @todo: what to do here?
                $locale = null;
            }
        }
        if (is_string($locale) && strpos($locale, '.') !== false) {
            [$locale] = explode('.', $locale);
        }
        return $locale;
    }
}
