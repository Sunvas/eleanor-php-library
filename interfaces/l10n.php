<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Interfaces;
use Eleanor\Enums\DateFormat;

/** Necessary methods for L10n classes */
interface L10n
{
	/** Human representation of the date
	 * @param int|string $d Date in plain format, or timestamp, or 0 or '' (for current date)
	 * @param DateFormat $t
	 * @return string */
	static function Date(int|string$d,DateFormat$t):string;
}

return L10n::class;