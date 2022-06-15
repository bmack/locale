# Native Locales for TYPO3


## Introduction

TYPO3 handles content in languages with "sys_languages", and since this
concept is super-flexible (but also added on top of the website),
you have a "default language" ("sys_language_uid=0"), which does
not have any meaning defined in a central place.

TYPO3 v9 brought you Site Handling. This was designed to give
a meaning to the "default language". Plus: You could have
different default languages for multiple sites, and different languages.

Still there are hurdles:
* Why do we need sys_language records anyways?
* What should we do with "All Languages", another magic "-1" value?
* The "fallback" logic to "default language" and the overlay logic blocks
  the decoupling of "content in a different language, which is not a translation"

This extension is built as a Proof-of-Concept to see what the future
could look like with TYPO3 based on locales.

## A new "locale" field

To finally decouple TYPO3 from the 20-year-old concept of "sys_language" record
relations, a new field `sys_locale` is added to all TCA tables which
ship with multi-language support.

This field is - for the time being - synchronized with `sys_language_uid` and
the `locale` property from the site configuration, so it is auto-populated.

* A signal adds `sys_locale` field to any TCA table.
* A CLI command ensures integrity of the current state.
* A DataHandler hook ensures that the `sys_locale` field is filled properly
  (also on update and when moving)

All entries with `sys_language_uid=-1` are filled with the locale `t3_all`.

Install the extension via `composer req `


## What's next?

* Let's not use `sys_language_uid=-1` anymore. Instead, we need some "l10n_state" magic to propagate a record without a locale
* In the end, the "default language" could and should be seen not as the default language, but more like a "virtual node" where the localized records are connected to. It should still be used for linking.
* See how we can resolve long-standing issues (selecting localized records in the backend, not being bound to default language in so many places).

## Things to consider

* Sorting: This is currently a mess with multi-language records (as the sorting is not partitioned)
* Let's re-evaluate overlays + fallbacks again.

## Credits

Benni Mack from b13.
