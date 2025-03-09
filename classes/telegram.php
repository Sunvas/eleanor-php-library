<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Простенький класс для отправки сообщений в Telegram и проверки аутентификации через него */
class Telegram extends Eleanor\Basic
{
	/** @var string Entrypoint адрес для обращения к API telegram */
	protected readonly string $base_url;

	/** @var resource CURL */
	protected $curl;

	/** Проверка авторизации через Telegram
	 * @param array $data Входящие данные
	 * @param string $key API key бота
	 * @param int $expire Число секунд, после которого вход считается недействительным
	 * @return array|string Строка при ошибке, array - при успехе */
	static function CheckAuth(array$data,string$key,int$expire=3600):array|string
	{
		$signature=(string)($data['hash'] ?? '');
		$checking=[];

		unset($data['hash']);

		foreach($data as$k=>$v)
			$checking[]=$k.'='.$v;

		sort($checking);

		$checking=implode("\n", $checking);
		$secret=hash('sha256', $key, true);
		$hash=hash_hmac('sha256', $checking, $secret);

		if(strcmp($hash, $signature)!==0)
			return'FAIL';

		if($expire>0 and (time()-$data['auth_date'])>$expire)
			return'OUTDATED';

		return$data;
	}

	/** Создание объекта-экземпляра бота
	 * @oaram string $api API ключ бота */
	function __construct(string$api)
	{
		$this->base_url="https://api.telegram.org/bot{$api}/";
		$this->curl=curl_init($this->base_url);
	}

	/** Деструктор */
	function __destruct()
	{
		curl_close($this->curl);
	}

	/** Создание запроса на сервера Telegram
	 * @url https://core.telegram.org/bots/api#making-requests
	 * @param string $method Тип запроса
	 * @param array|string $data Данные
	 * @throws E
	 * @return string Результат */
	function Request(string$method,array|string$data=''):string
	{
		curl_setopt_array($this->curl,[
			CURLOPT_URL=>$this->base_url.$method,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_ENCODING=>'',//https://php.watch/articles/curl-php-accept-encoding-compression
			CURLOPT_TIMEOUT=>25,
			CURLOPT_HEADER=>false,
			CURLOPT_POST=>!!$data,
			CURLOPT_POSTFIELDS=>$data,
		]);

		$result=curl_exec($this->curl);
		$erno=curl_errno($this->curl);

		if($erno>0)
			throw new E($erno.': '.curl_error($this->curl),E::SYSTEM);

		return $result;
	}

	/** Отправка сообщения
	 * @url https://core.telegram.org/bots/api#sendmessage
	 * @param int|string $chat_id ID Чата
	 * @param string $text Текст
	 * @param array $optional Все необязательные атрибуты
	 * @throws E
	 * @return string Результат */
	function SendMessage(int|string$chat_id,string$text,array$optional=[]):string
	{
		$optional['chat_id']=$chat_id;
		$optional['text']=$text;

		return $this->Request('SendMessage',$optional);
	}
}

return Telegram::class;