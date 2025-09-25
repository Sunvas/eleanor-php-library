<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Special exception for MySQL */
class EM extends \Eleanor\Abstracts\E
{
	const int
		/** Connection issue */
		CONNECT=1,

		/** Query issue */
		QUERY=2,

		/** Issue in prepared statement */
		PREPARED=3;

	/** @param string $message The same as in \Exception
	 * @param int $code Constants of class (from above) should be used
	 * @param ?\Throwable $previous The same as in \Exception
	 * @param ?string $file Path to the file where the exception occurred
	 * @param ?int $line Line number where the exception occurred
	 * @param ?int $errno Error number by MySQL
	 * @param ?string $query Bad query (for QUERY and PREPARED)
	 * @param ?array $params Parameters of bag query (for CONNECT and PREPARED) */
	function __construct(string$message,int$code=0,?\Throwable$previous=null,?string$file=null,?int$line=null,readonly ?int$errno=null,readonly ?string$query=null,readonly ?array$params=null)
	{
		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		parent::__construct($message,$code,$previous);
	}

	/** For BSOD */
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

	/** Logging */
	function Log():void
	{
		$type=match($this->code){
			self::CONNECT=>'db_connect',
			self::QUERY=>'db_query',
			self::PREPARED=>'db_prepared',
			default=>'db_unknown'
		};

		$this->LogWriter(
			\Eleanor\Library::$logs.$type,
			\md5($this->line.$this->file.$this->code),
		);
	}

	/** Entry in a .log file
	 * @param array $data The accumulated data of this exception
	 * @return string Item for log file */
	protected function LogItem(array&$data):string
	{
		#Запись в переменные нужна для последующего удобного чтения лог-файла любыми читалками
		$data['n']??=0;#Counter
		$data['n']++;

		$data['u']=Uri::$current;
		$data['d']=\date('Y-m-d H:i:s');
		$data['l']=$this->line;
		$data['m']=$this->message;
		$data['f']=$this->file;

		$log=$this->message.PHP_EOL;

		switch($this->code)
		{
			case self::CONNECT:
				//Если проблема с конкретным пользователем - запишем данные пользователя в лог
				if(\str_contains($this->message,'Access denied for user'))
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
				$data['p']=\serialize($this->params);

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

#Not necessary here, since class name equals filename
return EM::class;