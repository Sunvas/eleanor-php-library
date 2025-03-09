<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
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
			$d=time();
		elseif(is_string($d))
			$d=strtotime($d);

		if(!$d)
			return'';

		return match($f){
			DateFormat::Date=>date('Y-m-d',$d),
			DateFormat::Time=>date('H:i:s',$d),
			DateFormat::DateTime=>date('Y-m-d H:i:s',$d),
			DateFormat::TextDate=>static::TextDate($d,false),
			DateFormat::TextDateTime=>static::TextDate($d,false).date(' H:i',$d),
			DateFormat::MonthYear=>date(idate('Y')==idate('Y',$d) ? 'F' : 'F Y',$d),
			DateFormat::HumanDate=>static::TextDate($d),
			DateFormat::HumanDateTime=>static::TextDate($d).date(' H:i',$d),
		};
	}

	/** Человеческое представление даты
	 * @param int $t Дата в формате timestamp
	 * @param bool $human Флаг включения значений "Today", "Tomorrow", "Yesterday"
	 * @return string */
	static function TextDate(int$t,bool$human=true):string
	{
		$day=explode(',',date('Y,n,j,t',$t));
		$now=explode(',',date('Y,n,j,t'));

		if($human)
		{
			if($day[2]==$now[2] and $day[1]==$now[1] and $day[0]==$now[0])
				return'Today';

			if($day[2]+1==$now[2] and $now[0]==$day[0] and $now[1]==$day[1] or $day[1]+1==$now[1] and $now[0]==$day[0] and $now[2]==1 and $day[3]==$day[2] or $day[0]+1==$now[0] and $now[2]==1 and $now[1]==1 and $day[3]==$day[2])
				return'Yesterday';

			if($day[2]-1==$now[2] and $now[0]==$day[0] and $now[1]==$day[1] or $day[1]-1==$now[1] and $now[0]==$day[0] and $now[2]==$now[3] and $day[2]==1 or $day[0]-1==$now[0] and $now[2]==$now[3] and $now[1]==12 and $day[2]==1)
				return'Tomorrow';
		}

		//Даты текущего года и даты +- полгода отображаем без года
		return date($now[0]==$day[0] || abs($now[0]-$day[0])==1 && abs($now[1]-$day[1])<7 ? 'd F' : 'd F Y',$t);
	}
}