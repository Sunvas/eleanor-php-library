<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Enums;

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