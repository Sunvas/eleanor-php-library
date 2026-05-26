[Русская версия](README.ru.md)

# Localization Helpers Directory

This directory contains localization helper classes used by the Eleanor PHP Library. Localization helpers provide language-specific formatting logic that cannot be represented as simple translation strings. Typical examples are date formatting, plural forms, transliteration, and other grammar-dependent operations.

## Namespace

All helper classes in this directory belong to the `Eleanor\Classes\L10n` namespace. For example:

```php
namespace Eleanor\Classes\L10n;
class En
{
    // ...
}
```

The main `Eleanor\Classes\L10n` facade selects a helper class by the current language code and calls its static methods.

## File Naming

Each file represents one language helper and should be named after the lowercase language code:

```text
en.php
ru.php
```

The class name should use the same code in PascalCase:

```php
Eleanor\Classes\L10n\En
Eleanor\Classes\L10n\Ru
```

## Required Interface

Language helpers must implement `Eleanor\Interfaces\L10n`. Currently the interface requires the `Date()` method:

```php
static function Date(int|string $d, \Eleanor\Enums\DateFormat $f): string;
```

The method should format a timestamp or date/time string according to the requested `DateFormat` case.

## Common Helper Methods

Helpers may define additional language-specific methods, such as:

- `Plural()` for plural word forms;
- `Translit()` for language-specific transliteration;
- internal helpers for date/month name formatting.

These methods are not part of the shared interface unless they are explicitly needed by generic library code.

## Adding a New Language

To add a new helper:

1. Create a file named after the language code, for example `de.php`.
2. Declare a class in the `Eleanor\Classes\L10n` namespace, for example `De`.
3. Implement `Eleanor\Interfaces\L10n`.
4. Provide the required `Date()` method and any additional language-specific helpers.

Example:

```php
<?php
namespace Eleanor\Classes\L10n;

use Eleanor\Enums\DateFormat;

class De extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
    static function Date(int|string $d=0, DateFormat $f=DateFormat::HumanDateTime): string
    {
        // Language-specific formatting.
    }
}
```
