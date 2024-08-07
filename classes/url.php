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
	 * @param array $q Query часть ссылки
	 * @return string */
	public static function Make(array $static=[],string $ending='',array$q=[]):string
	{
		$r=[];

		foreach($static as $v)
			$r[]=is_int($v) ? $v : urlencode((string)$v);

		return join('/',$r).$ending.($q ? static::Query($q) : '');
	}

	/** Генерация Query для
	 * @param array $a Многомерный массив параметров, которых должен быть преобразован в URL
	 * @param bool $q Добавить ? в начале, если удалось собрать строку запроса
	 * @param string $d Разделитель параметров, получаемого URL
	 * @return string */
	public static function Query(array$a,bool$q=true,string$d='&amp;'):string
	{
		$r=[];

		foreach($a as $k=>&$v)
		{
			$k=urlencode($k);

			if(is_array($v))
				static::QueryParam($v,$k.'[',$r);
			elseif(is_string($v))
				$r[]=$k.'='.urlencode($v);
			elseif(is_int($v))
				$r[]=$k.'='.$v;
			elseif($v)
				$r[]=$k;
		}

		return($q && $r ? '?' : '').join($d,$r);
	}

	/** Генерация параметров для метода Query.
	 * @param array $a Параметры
	 * @param string $p Префикс для каждого параметра
	 * @param array &$r Ссылка на массив для помещения результатов */
	protected static function QueryParam(array$a,string$p,array &$r):void
	{
		$is_list=array_is_list($a);

		foreach($a as $k=>&$v)
			if(is_array($v))
				static::QueryParam($v,$p.$k.'][',$r);
			else
				$r[]=$p.($is_list ? '' : urlencode($k)).']='.(is_int($v) ? $v : urlencode((string)$v));
	}
}

Url::$current=substr($_SERVER['REQUEST_URI'],strlen(Eleanor\SITEDIR));

return Url::class;