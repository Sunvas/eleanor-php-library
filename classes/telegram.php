<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use Eleanor;

/** Primitive class for basic support of Telegram: sending messages and checking authentication credentials */
class Telegram extends Eleanor\Basic
{
	/** @var string Entrypoint of Telegram API */
	protected readonly string $base_url;

	/** @var resource CURL */
	protected $curl;

	/** Checking authentication credentials
	 * @param array $data Incoming data
	 * @param string $key API key
	 * @param int $expire Number of seconds after which data is considered obsolete
	 * @return array|string String on error, array on success */
	static function CheckAuth(array$data,string$key,int$expire=3600):array|string
	{
		$signature=\is_string($data['hash'] ?? 0) ? $data['hash'] : '';
		$checking=[];

		unset($data['hash']);

		foreach($data as$k=>$v)
			$checking[]=$k.'='.$v;

		\sort($checking);

		$checking=\implode("\n", $checking);
		$secret=\hash('sha256', $key, true);
		$hash=\hash_hmac('sha256', $checking, $secret);

		if(!\hash_equals($hash, $signature))
			return'FAIL';

		if($expire>0 and (\time()-$data['auth_date'])>$expire)
			return'OUTDATED';

		return$data;
	}

	/** @oaram string $api API key */
	function __construct(string$api)
	{
		$this->base_url="https://api.telegram.org/bot{$api}/";
		$this->curl=\curl_init($this->base_url);
	}

	function __destruct()
	{
		\curl_close($this->curl);
	}

	/** Making a request to Telegram
	 * @url https://core.telegram.org/bots/api#making-requests
	 * @param string $method
	 * @param array|string $data
	 * @throws E
	 * @return string */
	function Request(string$method,array|string$data=''):string
	{
		$headers=[];

		if(\is_array($data) and \array_any($data,fn($item)=>!\is_scalar($item)))
		{
			$data=\json_encode($data,\JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
			$headers[]='Content-Type: application/json';
		}

		\curl_setopt_array($this->curl,[
			\CURLOPT_URL=>$this->base_url.$method,
			\CURLOPT_RETURNTRANSFER=>true,
			\CURLOPT_ENCODING=>'',//https://php.watch/articles/curl-php-accept-encoding-compression
			\CURLOPT_TIMEOUT=>25,
			\CURLOPT_HEADER=>false,
			\CURLOPT_POST=>!!$data,
			\CURLOPT_POSTFIELDS=>$data,
			\CURLOPT_HTTPHEADER=>$headers
		]);

		$result=\curl_exec($this->curl);
		$erno=\curl_errno($this->curl);

		if($erno>0)
			throw new E($erno.': '.\curl_error($this->curl),E::SYSTEM);

		return $result;
	}

	/** Sending the messages
	 * @url https://core.telegram.org/bots/api#sendmessage
	 * @param int|string $chat_id ID of char or user
	 * @param string $text Текст
	 * @param array $optional Extra options
	 * @throws E
	 * @return string */
	function SendMessage(int|string$chat_id,string$text,array$optional=[]):string
	{
		$optional['chat_id']=$chat_id;
		$optional['text']=$text;

		return $this->Request('SendMessage',$optional);
	}
}

return Telegram::class;