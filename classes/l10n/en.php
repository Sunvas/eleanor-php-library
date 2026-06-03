<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\L10n;
use Eleanor\Enums\DateFormat;

/** English language support */
class En extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
	/** Format singular and plural nouns
	 * @param int $n Number
	 * @param string $singular Singular form of the word. Example: item, page
	 * @param ?string $plural Plural form of the word. If omitted, it will be formed by adding "s" to the singular form
	 * @return string */
	static function Plural(int$n,string$singular,?string$plural=null):string
	{
		return $n===1 ? $singular : $plural ?? $singular.'s';
	}

	/** Format date/time in human-readable form
	 * @param int|string $d Machine-readable date/time or timestamp.
	 *     If 0 or an empty string is passed, the current date will be used
	 * @param DateFormat $f
	 * @return string */
	static function Date(int|string$d=0,DateFormat$f=DateFormat::HumanDateTime):string
	{
		if(!$d)
			$d=\time();
		elseif(\is_string($d))
			$d=\strtotime($d);

		if(!$d)
			return '';

		return match($f){
			DateFormat::Date=>\date('Y-m-d',$d),
			DateFormat::Time=>\date('H:i:s',$d),
			DateFormat::DateTime=>\date('Y-m-d H:i:s',$d),
			DateFormat::TextDate=>static::TextDate($d,false),
			DateFormat::TextDateTime=>static::TextDate($d,false).\date(' H:i',$d),
			DateFormat::MonthYear=>\date(\idate('Y')==\idate('Y',$d) ? 'F' : 'F Y',$d),
			DateFormat::HumanDate=>static::TextDate($d),
			DateFormat::HumanDateTime=>static::TextDate($d).\date(' H:i',$d),
		};
	}

	/** Format date in human-readable form
	 * @param int $t Timestamp
	 * @param bool $human Whether to use "Today", "Tomorrow", and "Yesterday" instead of a date
	 * @return string */
	static function TextDate(int$t,bool$human=true):string
	{
		$day=new \DateTime()->setTimestamp($t)->setTime(0,0);
		$diff=new \DateTime()->setTime(0,0)->diff($day);

		if($human)
		{
			$days=$diff->invert ? -$diff->days : $diff->days;
			$date=match($days){
				-1=>'Yesterday',
				0=>'Today',
				1=>'Tomorrow',
				default=>null,
			};

			if($date)
				return $date;
		}

		# If the date is within 4 months of the current date, display it without the year
		return \date($diff->y<1 && $diff->m<5 ? 'd F' : 'd F Y',$t);
	}
}