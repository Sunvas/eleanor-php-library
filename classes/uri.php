<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Relative URI generator */
class Uri extends \Eleanor\Basic
{
	/** @static Raw current URI from browser address bar */
	static string $raw;

	/** Get decoded clean URI (decoded variable $uri from NGINX).
	 * To improve accuracy, it is recommended to add to the nginx configuration something like this:
	 * set $clean_uri "";
	 *
	 * if (!-e $request_filename) {
	 *   set $clean_uri $uri;
	 *   rewrite ^.*$ /index.php last;
	 * }
	 * ...
	 * location ~ \.php$ {
	 *   fastcgi_param CLEAN_URI $clean_uri;
	 *   ...
	 * }
	 * @see https://en.wikipedia.org/wiki/Clean_URL
	 * @see https://ru.wikipedia.org/wiki/Человекопонятный_URL
	 * @return string */
	static function Clean():string
	{
		# It is guaranteed that we have Clean URL here
		if(isset($_SERVER['CLEAN_URI']))
		{
			$uri=$_SERVER['CLEAN_URI'];

			# PHP 8.6: migrate to pipe operator
			return \substr($uri,\strlen(\Eleanor\SITEDIR))
				|> \urldecode(...);
		}

		$uri=$_SERVER['REQUEST_URI'];

		# Traditional query-based URL: link like /index.php?param=value
		if(\str_starts_with($uri,$_SERVER['SCRIPT_NAME']))
			return '';

		# Traditional query-based URL: site root / with or without query /?param=value
		if($uri==='/' or \str_starts_with($uri,'/?'))
			return '';

		[$uri]=\explode('?',static::$raw, 2);

		return \urldecode($uri);
	}

	/** Generate relative URI
	 * @param string[] $slugs Clean URL parts (slugs)
	 * @param string $ending Ending of URI
	 * @param array $q Query
	 * @return string */
	static function Make(array$slugs=[],string$ending='',array$q=[]):string
	{
		$r=[];

		foreach($slugs as $v)
			$r[]=\is_int($v) ? $v : \urlencode($v);

		return \join('/',$r).($r ? $ending : '').($q ? static::Query($q) : '');
	}

	/** Generate query string
	 * @param array $a Parameters for query. Elements with integer keys are treated as raw query fragments.
	 * @param bool $q Add ? to the beginning, if it was possible to assemble query string
	 * @param string $d Separator of parameters
	 * @return string */
	static function Query(array$a,bool$q=true,string$d='&amp;'):string
	{
		$r=[];

		foreach($a as $k=>$v)
		{
			if(\is_int($k))
			{
				$r[]=$v;
				continue;
			}

			$k=\urlencode($k);

			if(\is_int($v))
				$r[]=$k.'='.$v;
			elseif(\is_string($v))
				$r[]=$k.'='.\urlencode($v);
			elseif(\is_array($v))
				self::Nested($v,$k,$r);# [
			elseif($v)
				$r[]=$k;
		}

		return($q && $r ? '?' : '').\join($d,$r);
	}

	/** Generate nested query parameters for Query().
	 * @param array $a Parameters to serialize.
	 * @param string $p Prefix name for each param
	 * @param array &$r Reference for placing results */
	private static function Nested(array$a,string$p,array&$r):void
	{
		$p.='%5B';# [
		$is_list=\array_is_list($a);

		foreach($a as $k=>$v)
		{
			$k=$p.($is_list ? '' : \urlencode($k)).'%5D';# ]

			if(\is_int($v))
				$r[]=$k.'='.$v;
			elseif(\is_string($v))
				$r[]=$k.'='.\urlencode($v);
			elseif(\is_array($v))
				self::Nested($v,$k,$r);# [
			elseif($v)
				$r[]=$k;
		}
	}
}

# Get raw URI without site directory prefix
Uri::$raw=\substr($_SERVER['REQUEST_URI'],\strlen(\Eleanor\SITEDIR));

# Not required here because class name matches filename
return Uri::class;