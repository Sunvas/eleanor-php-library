<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Специальное исключение для MySQL */
class EM extends Eleanor\Abstracts\E
{
	/** @var ?int Номер ошибки по версии MySQL */
	readonly ?int $errno;

	/** @var ?string Проблемный запрос (для QUERY и PREPARED) */
	readonly ?string $query;

	/** @var ?array Параметры (для CONNECT и PREPARED) */
	readonly ?array $params;

	const int
		/** Ошибка при подключении */
		CONNECT=1,

		/** Ошибка в запросе */
		QUERY=2,

		/** Ошибка в prepared statement */
		PREPARED=3;

	/** @param string $message Тип исключения: connect - ошибка при подключении, query - ошибка при запросе
	 * @param int $code Код исключения
	 * @param ?\Throwable $previous Предыдущее исключение
	 * @param ?string $file Путь к файлу, где произошло исключение
	 * @param ?int $line Номер строки, где произошло исключение
	 * @param ?int $errno Номер ошибки по версии MySQL
	 * @param ?string $query Проблемный запрос (для QUERY и PREPARED)
	 * @param ?array $params Параметры (для CONNECT и PREPARED) */
	function __construct(string$message,int$code=0,?\Throwable$previous=null,?string$file=null,?int$line=null,?int$errno=null,?string$query=null,?array$params=null)
	{
		$this->errno=$errno;
		$this->query=$query;
		$this->params=$params;

		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		parent::__construct($message,$code,$previous);
	}

	/** Для BSOD */
	function __toString():string
	{
		$l10n=new L10n('em');

		return match($this->code){
			self::CONNECT=>$l10n['connect']($this->message,$this->errno,$this->params['db']),
			self::QUERY=>$l10n['query']($this->message,$this->errno,$this->query),
			self::PREPARED=>$l10n['prepared']($this->message,$this->errno,$this->query,$this->params),
			default=>$l10n['default']($this->message,$this->errno),
		};
	}

	/** Логирование исключения */
	function Log():void
	{
		$type=match($this->code){
			self::CONNECT=>'db_connect',
			self::QUERY=>'db_query',
			self::PREPARED=>'db_prepared',
			default=>'db_unknown'
		};

		$this->LogWriter(
			Eleanor\Library::$logs.$type,
			md5($this->line.$this->file.$this->code),
		);
	}

	/** Формирование записи в .log файле
	 * @param array $data Накопленные данные этого исключения
	 * @return string Запись для .log файла */
	protected function LogItem(array&$data):string
	{
		#Запись в переменные нужна для последующего удобного чтения лог-файла любыми читалками
		$data['n']??=0;#Counter
		$data['n']++;

		$data['u']=Uri::$current;
		$data['d']=date('Y-m-d H:i:s');
		$data['l']=$this->line;
		$data['m']=$this->message;
		$data['f']=$this->file;

		$log=$this->message.PHP_EOL;

		switch($this->code)
		{
			case self::CONNECT:
				//Если проблема с конкретным пользователем - запишем данные пользователя в лог
				if(str_contains($this->message,'Access denied for user'))
				{
					$data['h']=$this->params['host'] ?? '';
					$data['u']=$this->params['user'] ?? '';
					$data['p']=$this->params['pass'] ?? '';

					$log.=<<<LOG
Host: {$data['h']}
User: {$data['u']}
Pass: {$data['p']}
LOG;
				}

				$data['db']=$this->params['db'] ?? '';

				$log.=<<<LOG
Database: {$data['db']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
			break;

			case self::QUERY:
				$data['q']=$this->query;

				$log.=<<<LOG
Query: {$data['q']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
			break;

			case self::PREPARED:
				$data['q']=$this->query;
				$data['p']=serialize($this->params);

				$log.=<<<LOG
Query: {$data['q']}
Params: {$data['p']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
			break;

			default:
				$log.=<<<LOG
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
		}

		return$log;
	}
}

return EM::class;