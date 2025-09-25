<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\L10n;
use Eleanor\Enums\DateFormat;

/** Поддержка английского языка */
class En extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
	/** Образование множественной формы слова
	 * @param int $n Число
	 * @param array $forms Формы слова. Пример ['один','два и больше']
	 * @return mixed */
	static function Plural(int$n,array$forms):mixed
	{
		return$n==1 ? $forms[0] : $forms[1];
	}

	/** Представление даты для человека
	 * @param int|string $d Дата в обычном машинном формате, либо timestamp, 0 либо пустая строка - текущая дата
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

	/** Человеческое представление даты
	 * @param int $t Дата в формате timestamp
	 * @param bool $human Флаг включения значений "Today", "Tomorrow", "Yesterday"
	 * @return string */
	static function TextDate(int$t,bool$human=true):string
	{
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

		//This year, or 3 month of previous
		return date($now[0]==$date[0] || ($now[0]-$date[0])==1 && $t>=\strtotime('-3month') ? 'd F' : 'd F Y',$t);
	}
}