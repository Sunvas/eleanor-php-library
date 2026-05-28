<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Enums;

/** Date and time output formats supported by localization classes. */
enum DateFormat:string {
	/** Time only, numeric format: HH:MM:SS */
	case Time='t';

	/** Date only, numeric ISO-like format: YYYY-MM-DD */
	case Date='d';

	/** Date and time, numeric format: YYYY-MM-DD HH:MM:SS */
	case DateTime='dt';

	/** Month name with year when needed */
	case MonthYear='my';

	/** Localized textual date without relative words */
	case TextDate='td';

	/** Localized textual date with time, without relative words */
	case TextDateTime='tdt';

	/** Human-friendly localized date, may use relative words such as today/yesterday/tomorrow */
	case HumanDate='hd';

	/** Human-friendly localized date with time */
	case HumanDateTime='hdt';
}

# Not required here because the enum name matches filename
return DateFormat::class;