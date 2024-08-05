<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Генерации ссылок */
class Url extends Eleanor\BaseClass
{
	/** @static Адрес текущего адреса в браузере */
	public static string $current;

	/** Получение ЧеловекоПонятногоУрл
	 * @return string */
	public static function GetFURL():string
	{
		/* Чтобы FURL работал, нужно в конфигурацию nginx поместить примерно следующее:
		if (!-e $request_filename) {
			set $furl $uri;
			rewrite ^.*$ /index.php last;
		}
		...
		location ~ \.php$ {
			fastcgi_param FURL $furl;
			...
		}
		*/

		//Перед нам гарантировано ЧПУ
		if(isset($_SERVER['FURL']))
			return urldecode(ltrim($_SERVER['FURL'],'/'));

		//Перед нами не ЧПУ: ссылки вида /index.php?param=value
		if(str_starts_with($_SERVER['REQUEST_URI'],$_SERVER['SCRIPT_NAME']))
			return'';

		//Перед нами не ЧПУ: просто корень сайта / или корень сайта с параметрами /?param=value
		if($_SERVER['REQUEST_URI']==='/' or str_starts_with($_SERVER['REQUEST_URI'],'/?'))
			return'';

		//? добавлен на случай полного отсутствия параметров
		$furl=strstr($_SERVER['REQUEST_URI'].'?', '?', true);

		return urldecode(substr($furl,strlen(Eleanor\SITEDIR)));
	}

	/** Генерация ссылок
	 * @param array $static Статическая часть ссылки
	 * @param string $ending Окончание ссылки
	 * @param array $query request часть ссылки
	 * @return string */
	public static function Make(array$static=[],string$ending='',array$query=[]):string
	{
		$result=[];

		foreach($static as $v)
			if($v or (string)$v=='0')
				$result[]=urlencode($v);

		return join('/',$result).$ending.($query ? '?'.static::Query($query) : '');
	}

	/** Генерация Query для
	 * @param array $a Многомерный массив параметров, которых должен быть преобразован в URL
	 * @param string $d Разделитель параметров, получаемого URL
	 * @return string */
	public static function Query(array$a,string$d='&amp;'):string
	{
		$r=[];

		foreach($a as $k=>&$v)
		{
			$k=urlencode($k);

			if(is_array($v))
				static::QueryParam($v,$k.'[',$r);
			elseif($v or (string)$v=='0')
				$r[]=$k.'='.(is_string($v) ? urlencode($v) : (int)$v);
			else
				$r[]=$k;
		}

		return join($d,$r);
	}

	/** Генерация параметров для метода Query.
	 * @param array $a Параметры
	 * @param string $p Префикс для каждого параметра
	 * @param array &$r Ссылка на массив для помещения результатов */
	protected static function QueryParam(array $a, string $p, array &$r):void
	{
		$i=0;

		foreach($a as $k=>&$v)
			if(is_array($v))
				static::QueryParam($v,$p.$k.'][',$r);
			elseif($v or (string)$v=='0')
				$r[]=$p.(($k===$i++) ? '' : urlencode($k)).']='.(is_string($v) ? urlencode($v) : (int)$v);
	}
}

Url::$current=substr($_SERVER['REQUEST_URI'],strlen(Eleanor\SITEDIR));

return Url::class;