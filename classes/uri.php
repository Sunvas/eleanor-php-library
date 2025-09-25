<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Relative URI generator */
class Uri extends \Eleanor\Basic
{
	/** @static Current URI (from browser's address bar) */
	static string $current;

	/** Obtaining the Clean URI (decoded variable $uri from NGINX)
	 * @url https://en.wikipedia.org/wiki/Clean_URL
	 * @url https://ru.wikipedia.org/wiki/Человекопонятный_URL
	 * @return string */
	static function GetURI():string
	{
		/* To improve accuracy, it is recommended to add to the nginx configuration something like this:
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

		//It is guaranteed that we have Clean URL here
		if(isset($_SERVER['URI']))
		{
			$uri=$_SERVER['URI'];

			return \urldecode(\substr($uri,\strlen(\Eleanor\SITEDIR)));
		}

		$uri=$_SERVER['REQUEST_URI'];

		//Not Clean URL: link like /index.php?param=value
		if(\str_starts_with($uri,$_SERVER['SCRIPT_NAME']))
			return'';

		//Not Clean URL: site root / with or without query /?param=value
		if($uri==='/' or \str_starts_with($uri,'/?'))
			return'';

		[$uri]=\explode('?',static::$current, 2);

		return \urldecode($uri);
	}

	/** Generating relative URIs
	 * @param array $slugs Clean URL parts (slugs)
	 * @param string $ending Ending of URI
	 * @param array $q Query
	 * @return string */
	static function Make(array$slugs=[],string$ending='',array$q=[]):string
	{
		$r=[];

		foreach($slugs as $v)
			$r[]=\is_int($v) ? $v : \urlencode((string)$v);

		return \join('/',$r).($r ? $ending : '').($q ? static::Query($q) : '');
	}

	/** Query generator
	 * @param array $a Parameters for query
	 * @param bool $q Add ? to the beginning, if it was possible to assemble query string
	 * @param string $d Separator of parameters
	 * @return string */
	static function Query(array$a,bool$q=true,string$d='&amp;'):string
	{
		$r=[];

		foreach($a as $k=>&$v)
		{
			$k=\urlencode($k);

			if(\is_array($v))
				static::QueryParam($v,$k.'%5B',$r);//[
			elseif(\is_string($v))
				$r[]=$k.'='.\urlencode($v);
			elseif(\is_int($v))
				$r[]=$k.'='.$v;
			elseif($v)
				$r[]=$k;
		}

		return($q && $r ? '?' : '').\join($d,$r);
	}

	/** Parameters generator for the Query method.
	 * @param array $a Parameters
	 * @param string $p Prefix for each param
	 * @param array &$r Reference for placing results */
	protected static function QueryParam(array$a,string$p,array &$r):void
	{
		$is_list=\array_is_list($a);

		foreach($a as $k=>&$v)
			if(\is_array($v))
				static::QueryParam($v,$p.$k.'%5D%5B',$r);//][
			else
				$r[]=$p.($is_list ? '' : \urlencode($k)).'%5D='.(\is_int($v) ? $v : \urlencode((string)$v));
	}
}

Uri::$current=\substr($_SERVER['REQUEST_URI'],\strlen(\Eleanor\SITEDIR));

#Not necessary here, since class name equals filename
return Uri::class;