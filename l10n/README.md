[Русская версия](README.ru.md)

# Localization files

This directory contains localization (l10n) files for library components. Each localization file must return an array where values may be:

- strings — for simple translation cases;
- Closures — for dynamic or structurally complex translations.

Example:

```php
return [
	'title'=>'User profile',
	'items'=>fn(int$count)=>match($count){
		0=>'No items',
		1=>'One item',
		default=>"$count items",
	},
];
```

Each file name must include a language suffix identifying the target language. Supported naming formats:

```text
[component-name].[language-code].php
[language-code].php
```

Examples:

```text
auth.en.php
auth.ru.php
en.php
ru.php
```

## Why translations are programmable

The localization system intentionally avoids gettext-style approaches based exclusively on functions like `__()` and static translation strings. While such approaches may work for simple substitutions, they become increasingly limited when dealing with complex linguistic rules, reusable translation logic, or dynamically structured messages. Translation files in Eleanor PHP Library are therefore designed as executable PHP definitions rather than static dictionaries. This provides several important capabilities:

- reusable translation logic;
- inheritance and composition;
- dynamically generated messages;
- language-specific grammatical rules;
- arbitrarily complex pluralization;
- context-aware formatting.

## Example of a problematic gettext case

Consider two variables representing quantities of apples and oranges. A proper translation system may need to produce:

- `There's nothing`;
- `There is just 1 apple`;
- `There are just 5 apples`;
- `There are 7 apples and 1 orange`;
- `There are 10 oranges and 3 apples`;
- `There are 5 oranges and apples each`.

Such cases become significantly harder to express cleanly using static gettext pluralization rules, especially when multiple independently pluralized variables interact within a single sentence. The problem becomes even more complicated in languages with rich grammatical systems such as Russian.