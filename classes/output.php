<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Outputting content to the browser */
class Output extends \Eleanor\Basic
{
	/** @const Powered by header. Feel free to modify it whatever you want! */
	protected const string POWERED='X-Powered-CMS: Eleanor PHP Library https://eleanor-cms.com/library';

	/** Attempt to return 304 http code (Not Modified) when browser's cache is up to date
	 * @param string $etag Etag for checking (via str_contains)
	 * @return bool */
	static function Return304(string$etag=''):bool
	{
		$if_match=$_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

		if($etag)
			$match=\str_contains($if_match,$etag);

		//Etag contains expired time
		elseif(\preg_match('#e=(\d{,12})#',$if_match,$matches)>0)
		{
			$timestamp=\Eleanor\BASE_TIME+(int)$matches[1];
			$match=$timestamp>=\time();
		}
		else
			return false;

		if($match)
			\header(static::POWERED,true,304);

		return$match;
	}

	/** @var array Storage of nonce */
	static array $nonces=[];

	/** Nonce generator for scripts. All scripts on page can use the same nonce.
	 * @param int $bytes
	 * @return string
	 * @throws \Random\RandomException */
	static function Nonce(int$bytes=16):string
	{
		if(isset(static::$nonces[$bytes]))
			return static::$nonces[$bytes];

		$hash=\random_bytes($bytes);
		$hash=\base64_encode($hash);

		static::$nonces[$bytes]=$hash;

		return$hash;
	}

	/** @const The most used mime types for SendHeaders */
	const string
		XML='text/xml',
		HTML='text/html',
		TEXT='text/plain',
		JSON='application/json';

	/** Sending system headers (before echo)
	 * @param string $mimetype Content type (xml,html,text,json)
	 * @param int $code Status code
	 * @param string|int $cache int specifies the number of seconds for which the result should be cached, string means etag content
	 * @return bool Successful flag */
	static function SendHeaders(string$mimetype=self::TEXT,int$code=200,int|string$cache=604800):bool
	{
		if(\headers_sent())
			return false;

		if($cache)
		{
			//Безусловный кэш на N Секунд
			if(\is_int($cache))
			{
				$age='immutable, max-age='.$cache;
				$etag='e='.(\time()+$cache-\Eleanor\BASE_TIME);
			}
			else
			{
				$age='must-revalidate';
				$etag=$cache;
			}

			\header('Cache-Control: private, no-transform, '.$age);
			\header("ETag: \"{$etag}\"");
		}

		#Без кэша
		else
			\header('Cache-Control: no-cache, no-store');

		if(static::$nonces)
		{
			$nonces=\join("' 'nonce-",static::$nonces);

			\header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic' 'nonce-{$nonces}'");
		}
		elseif($mimetype===static::HTML)
			\header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic'");

		if(static::POWERED)
			\header(static::POWERED);

		\header("Content-Type: {$mimetype}; charset=utf-8",true,$code);

		return true;
	}
}

#Not necessary here, since class name equals filename
return Output::class;