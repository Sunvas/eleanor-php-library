<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\L10n;
use Eleanor\Enums\DateFormat;

/** English language support */
class En extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
	/** Formatting singular and plural nouns
	 * @param int $n Number
	 * @param string $singular form of the word. Example: item, page
	 * @param ?string $plural form of the word. If omitted, plural form will be made by adding 's' to the singular form.
	 * @return string */
	static function Plural(int$n,string$singular,?string$plural=null):string
	{
		return$n===1 ? $singular : $plural ?? $singular.'s';
	}

	/** Formatting date/time in human-readable format
	 * @param int|string $d Machine-readable date/time or timestamp. If 0 or empty string - current date will be used
	 * @param DateFormat $f
	 * @return string */
	static function Date(int|string$d=0,DateFormat$f=DateFormat::HumanDateTime):string
	{
		if(!$d)
			$d=\time();
		elseif(\is_string($d))
			$d=\strtotime($d);

		if(!$d)
			return'';

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

	/** Formatting date in human-readable format
	 * @param int $t Timestamp
	 * @param bool $human Flag to enable "Today", "Tomorrow", "Yesterday" instead of a date
	 * @return string */
	static function TextDate(int$t,bool$human=true):string
	{
		#PHP 8.6
		$date=\explode(',',\date('Y,n,j,t',$t));
		$now=\explode(',',\date('Y,n,j,t'));

		if($human)
		{
			if($date[2]==$now[2] and $date[1]==$now[1] and $date[0]==$now[0])
				return'Today';

			if($now[0]==$date[0] and $now[1]==$date[1] and $date[2]+1==$now[2] or //Same year and month
				$now[2]==1 and $date[3]==$date[2] and ($now[0]==$date[0] and $date[1]+1==$now[1] or $date[0]+1==$now[0] and $now[1]==1))//Today is the first day of the month
				return'Yesterday';

			if($now[0]==$date[0] and $now[1]==$date[1] and $now[2]+1==$date[2] or //Same year and month
				$date[2]==1 and $now[3]==$now[2] and ($now[0]==$date[0] and $now[1]+1==$date[1] or $now[0]+1==$date[0] and $date[1]==1))//Date is the first day of the month
				return'Tomorrow';
		}

		#Omitting the year, if the date refers to the current year or the past one, but not more than 3 months
		return date($now[0]==$date[0] || ($now[0]-$date[0])==1 && $t>=\strtotime('-3month') ? 'd F' : 'd F Y',$t);
	}
}