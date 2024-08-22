<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Специальное исключение для базы даных */
class EE_DB extends EE
{
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
	 * @param array $extra Данные исключения */
	public function __construct(string$message,int$code=0,?\Throwable$previous=null,array$extra=[])
	{
		$l10n=new L10n('ee_db');

		$message=match($code){
			self::CONNECT=>$l10n['connect']($message,$extra['errno'],$extra['db']),
			self::QUERY,self::PREPARED=>$l10n['query']($message,$extra['errno'] ?? null),
			default=>$message
		};

		parent::__construct($message,$code,$previous,$extra);
	}

	/** Логирование исключения. Основной наследуемый метод. */
	public function Log():void
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
			function($data)
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
							$data['h']=$this->extra['host'] ?? '';
							$data['u']=$this->extra['user'] ?? '';
							$data['p']=$this->extra['pass'] ?? '';

							$log.=<<<LOG
Host: {$data['h']}
User: {$data['u']}
Pass: {$data['p']}
LOG;
						}

						$data['db']=$this->extra['db'] ?? '';

						$log.=<<<LOG
Database: {$data['db']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
 					break;

					case self::QUERY:
						$data['q']=$this->extra['query'];

						$log.=<<<LOG
Query: {$data['q']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
					break;

					case self::PREPARED:
						$data['q']=$this->extra['query'];
						$data['p']=isset($this->extra['params']) ? serialize($this->extra['params']) : '-';

						$log.=<<<LOG
Query: {$data['q']}
Params: {$data['p']}
Error: {$data['e']}
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

				return[$data,$log];
			}
		);
	}
}

return EE_DB::class;