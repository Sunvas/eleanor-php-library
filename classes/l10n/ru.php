<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\L10n;
use Eleanor\Enums\DateFormat;

/** Поддержка русского языка */
class Ru extends \Eleanor\Basic implements \Eleanor\Interfaces\L10n
{
	/** Образование множественной формы слова
	 * @param int $n Число
	 * @param array $forms Формы слова. Пример ['один','два, три, четыре', 'пять, шесть, семь, восемь, девять, ноль')
	 * @return mixed */
	static function Plural(int$n,array$forms):mixed
	{
		$forms+=['','',''];
		return $n%10==1&&$n%100!=11?$forms[0]:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$forms[1]:$forms[2]);
	}

	/** Транслитерация строки в латинницу
	 * @param string $s Текст
	 * @return string */
	static function Translit(string$s):string
	{
		return \str_replace(
			['а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ы','ё','ж','ч',
			 'ш','щ','э','ю','я','ъ','ь','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У',
			 'Ф','Х','Ц','Ы','Ё','Ж','Ч','Ш','Щ','Э','Ю','Я','Ъ','Ь'],

			['a','b','v','g','d','e','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','y','yo','zh','ch',
			 'sh','sch','je','yu','ya',"'","'",'A','B','V','G','D','E','Z','I','J','K','L','M','N','O','P','R','S','T','U',
			 'F','H','C','Y','Yo','Zh','Ch','Sh','Sch','Je','Yu','Ya',"'","'"],
			$s
		);
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

	/** Человеческое представление даты
	 * @param int $t Дата в формате timestamp
	 * @param bool $human Флаг включения значений "Сегодня", "Завтра", "Вчера"
	 * @return string */
	static function DateText(int$t,bool$human=true):string
	{
		$date=\array_map('intval',\explode(',',\date('Y,n,j,t',$t)));
		$now=\array_map('intval',\explode(',',\date('Y,n,j,t')));

		if($human)
		{
			if($date[2]==$now[2] and $date[1]==$now[1] and $date[0]==$now[0])
				return'Сегодня';

			if($now[0]==$date[0] and $now[1]==$date[1] and $date[2]+1==$now[2] or //Same year and month
				$now[2]==1 and $date[3]==$date[2] and ($now[0]==$date[0] and $date[1]+1==$now[1] or $date[0]+1==$now[0] and $now[1]==1))//Today is the first day of the month
				return'Вчера';

			if($now[0]==$date[0] and $now[1]==$date[1] and $now[2]+1==$date[2] or //Same year and month
				$date[2]==1 and $now[3]==$now[2] and ($now[0]==$date[0] and $now[1]+1==$date[1] or $now[0]+1==$date[0] and $date[1]==1))//Date is the first day of the month
				return'Завтра';

			if($now[0]==$date[0] and $now[1]==$date[1] and $date[2]+2==$now[2] or //Same year and month
				($now[2]==2 and $date[3]==$date[2] or $now[2]==1 and $date[3]==$date[2]+1) and ($now[0]==$date[0] and $date[1]+1==$now[1] or $date[0]+1==$now[0] and $now[1]==1))//Today is the 1-2 day of the month
				return'Позавчера';

			if($now[0]==$date[0] and $now[1]==$date[1] and $now[2]+2==$date[2] or //Same year and month
				($date[2]==2 and $now[3]==$now[2] or $date[2]==1 and $now[3]==$now[2]+1) and ($now[0]==$date[0] and $now[1]+1==$date[1] or $now[0]+1==$date[0] and $date[1]==1))//Date is the 1-2 day of the month
				return'Послезавтра';
		}

		//This year, or 3 month of previous
		return \sprintf($now[0]==$date[0] || ($now[0]-$date[0])==1 && $t>=\strtotime('-3month') ? '%02d %s' : '%02d %s %d',$date[2],match($date[1]){
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
			},$date[0]);
	}
}