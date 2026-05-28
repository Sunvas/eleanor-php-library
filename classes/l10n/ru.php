<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\L10n;
use Eleanor\Enums\DateFormat;

/** Поддержка русского языка */
class Ru extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
	/** Образование формы множественного числа
	 * @param int $n Число
	 * @param string $form1 Форма единственного числа. Например: элемент, страница
	 * @param string $form24 Форма множественного числа для 2-4. Например: элемента, страницы
	 * @param ?string $form5 Форма множественного числа для 5+. Например: элементов, страниц
	 * @return string */
	static function Plural(int$n,string$form1,string$form24,?string$form5=null):string
	{
		$n=\abs($n);
		return $n%10==1 && $n%100!=11 ? $form1 : ($n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? $form24 : $form5 ?? $form24);
	}

	/** Форматирование даты/времени в человекочитаемом формате
	 * @param int|string $d Дата/время в машинном формате или timestamp.
	 *     Если передано 0 или пустая строка, используется текущая дата
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
			DateFormat::TextDate=>static::DateText($d,false),
			DateFormat::TextDateTime=>static::DateText($d,false).\date(' H:i',$d),
			DateFormat::MonthYear=>static::MonthYear($d),
			DateFormat::HumanDate=>static::DateText($d),
			DateFormat::HumanDateTime=>static::DateText($d).\date(' H:i',$d),
		};
	}

	/** Вывод месяца и года
	 * @param int $t Timestamp
	 * @return string */
	static function MonthYear(int$t):string
	{
		$y=\idate('Y',$t);

		return match(\idate('m',$t)){
			1=>'Январь',
			2=>'Февраль',
			3=>'Март',
			4=>'Апрель',
			5=>'Май',
			6=>'Июнь',
			7=>'Июль',
			8=>'Август',
			9=>'Сентябрь',
			10=>'Октябрь',
			11=>'Ноябрь',
			12=>'Декабрь',
		}.(\idate('Y')==$y ? '' : ' '.$y);
	}

	/** Формирование даты в человекочитаемом формате
	 * @param int $t Timestamp
	 * @param bool $human Флаг использования слов "Сегодня", "Завтра", "Послезавтра", "Вчера" и "Позавчера" вместо даты
	 * @return string */
	static function DateText(int$t,bool$human=true):string
	{
		$day=new \DateTime()->setTimestamp($t)->setTime(0,0);
		$diff=new \DateTime()->setTime(0,0)->diff($day);

		if($human)
		{
			$days=$diff->invert ? -$diff->days : $diff->days;
			$date=match($days){
				-2=>'Позавчера',
				-1=>'Вчера',
				0=>'Сегодня',
				1=>'Завтра',
				2=>'Послезавтра',
				default=>null,
			};

			if($date)
				return $date;
		}

		# Если дата находится в пределах 4 месяцев от текущей, выводим её без года
		return \sprintf($diff->y<1 && $diff->m<5 ? '%02d %s' : '%02d %s %d',\idate('j',$t),match(\idate('n',$t)){
				1=>'января',
				2=>'февраля',
				3=>'марта',
				4=>'апреля',
				5=>'мая',
				6=>'июня',
				7=>'июля',
				8=>'августа',
				9=>'сентября',
				10=>'октября',
				11=>'ноября',
				12=>'декабря',
			},\idate('Y',$t));
	}
}