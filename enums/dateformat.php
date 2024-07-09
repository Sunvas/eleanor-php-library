<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Enums;

/** Форматы дат, поддерживаемых системой */
enum DateFormat:string {
	case Time='t';
	case Date='d';
	case DateTime='dt';
	case MonthYear='my';
	case TextDate='td';
	case TextDateTime='tdt';
	case HumanDate='hd';
	case HumanDateTime='hdt';
}

return DateFormat::class;