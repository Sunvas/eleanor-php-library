<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Interfaces;

use Eleanor\Enums\DateFormat;

/** Interface for localization helpers */
interface L10n
{
	/** Format date/time for human-readable localized output.
	 * @param int|string $d Date/time string accepted by strtotime(), Unix timestamp, or 0/'' for current date
	 * @param DateFormat $f Output format
	 * @return string */
	static function Date(int|string$d,DateFormat$f):string;
}

# Not required here because interface name matches filename.
return L10n::class;