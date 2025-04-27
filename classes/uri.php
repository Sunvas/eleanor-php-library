<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Генератор относительных URI */
class Uri extends Eleanor\Basic
{
	/** @static Текущий адрес (по которому обратились к скрипту) */
	static string $current;

	/** Получение ЧеловекоПонятного URI (обработанную переменную $uri из nginx)
	 * @url https://ru.wikipedia.org/wiki/Человекопонятный_URL
	 * @return string */
	static function GetURI():string
	{
		/* Для повышения точности, в конфигурацию nginx рекомендуется поместить примерно следующее:
		set $clean_url "";

		if (!-e $request_filename) {
			set $clean_url $uri;
			rewrite ^.*$ /index.php last;
		}
		...
		location ~ \.php$ {
			fastcgi_param URI $clean_url;
			...
		}
		*/

		//Перед нам гарантировано ЧПУ
		if(isset($_SERVER['URI']))
		{
			$uri=$_SERVER['URI'];

			return urldecode(substr($uri,strlen(Eleanor\SITEDIR)));
		}

		$uri=$_SERVER['REQUEST_URI'];

		//НЕ ЧПУ: ссылки вида /index.php?param=value
		if(str_starts_with($uri,$_SERVER['SCRIPT_NAME']))
			return'';

		//НЕ ЧПУ: корень сайта / или корень сайта с параметрами /?param=value
		if($uri==='/' or str_starts_with($uri,'/?'))
			return'';

		[$uri]=explode('?',static::$current, 2);

		return urldecode($uri);
	}

	/** Генерация относительных URI (ссылок)
	 * @param array $slugs ЧПУшная часть ссылки
	 * @param string $ending Окончание ссылки
	 * @param array $q Query часть ссылки
	 * @return string */
	static function Make(array$slugs=[],string$ending='',array$q=[]):string
	{
		$r=[];

		foreach($slugs as $v)
			$r[]=is_int($v) ? $v : urlencode((string)$v);

		return join('/',$r).$ending.($q ? static::Query($q) : '');
	}

	/** Генерация query
	 * @param array $a Многомерный массив параметров, которых должен быть преобразован в URL
	 * @param bool $q Добавить ? в начале, если удалось собрать строку запроса
	 * @param string $d Разделитель параметров, получаемого URL
	 * @return string */
	static function Query(array$a,bool$q=true,string$d='&amp;'):string
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

Uri::$current=substr($_SERVER['REQUEST_URI'],strlen(Eleanor\SITEDIR));

return Uri::class;