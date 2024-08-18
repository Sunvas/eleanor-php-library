<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Вывод содержимого в браузер, сборник методов для выдачи информации через http */
class Output extends Eleanor\BaseClass
{
	/** @const Powered by header. Feel free to modify it whatever you want! */
	protected const string POWERED='X-Powered-CMS: Eleanor PHP Library https://eleanor-cms.ru/library';

	/** Проверка возможности вернуть браузеру его кэш
	 * @param string $etag Etag для проверки, поле должно быть пустое если в SendHeaders $cache передавалось число
	 * @return bool */
	public static function ReturnCache(string$etag=''):bool
	{
		$if_match=$_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

		if($etag)
			$match=str_contains($if_match,$etag);

		//Проверка expired
		elseif(preg_match('#e=(\d{,12})#',$if_match,$matches)>0)
		{
			$timestamp=Eleanor\BASE_TIME+(int)$matches[1];
			$match=$timestamp>=time();
		}
		else
			return false;

		if($match)
			header(static::POWERED,true,304);

		return$match;
	}

	/** @var array Storage of nonce */
	public static array $nonces=[];

	/** Генерация nonce для скриптов. Они могут быть использованы повторно
	 * @param int $bytes
	 * @return string
	 * @throws \Random\RandomException */
	public static function Nonce(int$bytes=16):string
	{
		if(isset(static::$nonces[$bytes]))
			return static::$nonces[$bytes];

		$hash=random_bytes($bytes);
		$hash=base64_encode($hash);

		static::$nonces[$bytes]=$hash;

		return$hash;
	}

	/** @const Mime Types for SendHeaders. Список не исключительный, поэтому не enum */
	public const string
		CSS='text/css',
		XML='text/xml',
		HTML='text/html',
		TEXT='text/plain',
		JSON='application/json',
		JAVASCRIPT='application/javascript';

	/** Подготовка хедеров (действия перед echo)
	 * @param string $mimetype Тип контента (text,html,js,css,json,xml)
	 * @param int $code Код ответа
	 * @param string|int $cache Через int указывается количество секунд на которые нужно закэшировать результат, если string - содержимое etag
	 * @return bool Флаг успешной отправки заголовков */
	public static function SendHeaders(string$mimetype=self::TEXT,int$code=200,int|string$cache=604800):bool
	{
		if(headers_sent())
			return false;

		if($cache)
		{
			//Безусловный кэш на N Секунд
			if(is_int($cache))
			{
				$age='immutable, max-age='.$cache;
				$etag='e='.(time()+$cache-Eleanor\BASE_TIME);
			}
			else
			{
				$age='must-revalidate';
				$etag=$cache;
			}

			header('Cache-Control: private, no-transform, '.$age);
			header("ETag: \"{$etag}\"");
		}

		#Без кэша
		else
			header('Cache-Control: no-cache, no-store');

		if(static::$nonces)
		{
			$nonces=join("' 'nonce-",static::$nonces);

			header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic' 'nonce-{$nonces}'");
		}
		elseif($mimetype===static::HTML)
			header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic'");

		header(static::POWERED);
		header("Content-Type: {$mimetype}; charset=utf-8",true,$code);

		return true;
	}
}

return Output::class;