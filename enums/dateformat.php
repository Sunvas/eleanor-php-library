<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
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

#Not necessary here, since enum name equals filename
return DateFormat::class;