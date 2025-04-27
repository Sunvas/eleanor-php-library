<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Interfaces;
use Eleanor\Enums\DateFormat;

/** Обязательные методы системных языков */
interface L10n
{
	/** Представление даты для человека
	 * @param int|string $d Дата в обычном машинном формате, либо timestamp, 0 либо пустая строка - текущая дата
	 * @param DateFormat $t
	 * @return string */
	static function Date(int|string$d,DateFormat$t):string;
}

return L10n::class;