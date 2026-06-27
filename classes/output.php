<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** HTTP response output helper */
class Output extends \Eleanor\Basic
{
	/** @const Powered-by HTTP header. Feel free to modify it whatever you want! */
	protected const string POWERED='X-Powered-CMS: Eleanor PHP Library https://eleanor-cms.com/library';

	/** Attempt to return 304 http code (Not Modified) when browser's cache is up to date
	 * @param string $etag ETag for checking (via str_contains)
	 * @return bool */
	static function Return304(string$etag=''):bool
	{
		$if_match=$_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

		if($etag)
			$match=\str_contains($if_match,$etag);

		# ETag contains expired time
		elseif(\preg_match('#e=(\d{,12})#',$if_match,$matches)>0)
		{
			$timestamp=\Eleanor\BASE_TIME+(int)$matches[1];
			$match=$timestamp>=\time();
		}
		else
			return false;

		if($match)
			\header(static::POWERED,true,304);

		return $match;
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

		return $hash;
	}

	/** @var array For the link header */
	protected static array $links=[];

	/** Add resource hint to the HTTP Link header.
	 * @param string $url Resource URL
	 * @param string $rel Link relation type.  Usually "preconnect" or "preload".
	 * @param string ...$a Additional Link header parameters */
	static function Link(string$url,string$rel='preconnect',string...$a):void
	{
		static::$links[]="<{$url}>; rel=".$rel.($a ? '; '.\http_build_query($a,'','; ') : '');
	}

	/** @const The most used mime types for SendHeaders */
	const string
		XML='text/xml',
		HTML='text/html',
		TEXT='text/plain',
		JSON='application/json';

	/** Send system HTTP headers. Must be called before any output is sent.
	 * @param string $mimetype Response content type (xml, html, text, json, etc...)
	 * @param int $code HTTP status code
	 * @param string|int $cache Cache control parameter:
	 *     - int: cache lifetime in seconds
	 *     - string: ETag content
	 * @return bool */
	static function SendHeaders(string$mimetype=self::TEXT,int$code=200,int|string$cache=604800):bool
	{
		if(\headers_sent())
			return false;

		if($cache)
		{
			# Unconditional cache for N seconds
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

		# Without cache
		else
			\header('Cache-Control: no-cache, no-store');

		$is_html=$mimetype===static::HTML;

		if(static::$nonces)
		{
			$nonces=\join("' 'nonce-",static::$nonces);

			\header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic' 'nonce-{$nonces}'");
		}
		elseif($is_html)
			\header("Content-Security-Policy: frame-ancestors 'self'; script-src 'unsafe-eval' 'strict-dynamic'");

		if($is_html and static::$links)
			\header('Link: '.join(', ',static::$links));

		if(static::POWERED)
			\header(static::POWERED);

		\header("Content-Type: {$mimetype}; charset=utf-8",true,$code);

		return true;
	}
}

# Not required here because class name matches filename
return Output::class;