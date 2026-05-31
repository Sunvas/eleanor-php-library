<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Specialized exception for MySQL-related errors */
class EM extends \Eleanor\Abstracts\E
{
	const int
		/** Connection issue */
		CONNECT=1,

		/** Query issue */
		QUERY=2,

		/** Issue in prepared statement */
		PREPARED=3,

		/** Issue with value */
		VALUE=4,

		/** Other errors */
		OTHER=5;

	/** @param string $message The same as in \Exception
	 * @param int $code Constants of class (from above) should be used
	 * @param ?\Throwable $previous The same as in \Exception
	 * @param ?string $file Path to the file where the exception occurred
	 * @param ?int $line Line number where the exception occurred
	 * @param int $errno Error number by MySQL
	 * @param string $query Bad query (for QUERY, PREPARED and OTHER), or field name (for VALUE)
	 * @param array $params Parameters of connection (for CONNECT) or of bad query (for PREPARED) */
	function __construct(string$message,int$code=0,?\Throwable$previous=null,?string$file=null,?int$line=null,readonly int$errno=0,readonly string$query='',readonly array$params=[])
	{
		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		parent::__construct($message,$code,$previous);
	}

	/** String representation */
	function __toString():string
	{
		$l10n=new L10n('em');

		return match($this->code){
			self::CONNECT=>$l10n['connect']($this->message,$this->errno,$this->params['db'] ?? '-'),
			self::QUERY=>$l10n['query']($this->message,$this->errno,$this->query),
			self::PREPARED=>$l10n['prepared']($this->message,$this->errno,$this->query,$this->params),
			self::VALUE=>$l10n['value']($this->message,$this->query),
			self::OTHER=>$l10n['other']($this->message,$this->query),
			default=>$l10n['default']($this->message,$this->errno),
		};
	}

	/** Logging */
	function Log():void
	{
		if(!\Eleanor\Library::$logs_enabled)
			return;

		$type=match($this->code){
			self::CONNECT=>'db_connect',
			self::QUERY=>'db_query',
			self::PREPARED=>'db_prepared',
			self::VALUE=>'db_value',
			self::OTHER=>'db_other',
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
		# Store values separately to keep log files easy to read with common log viewers
		$data['n']??=0;# Counter
		$data['n']++;

		$data['d']=\date('Y-m-d H:i:s');
		$data['l']=$this->line;
		$data['m']=$this->message;
		$data['f']=$this->file;

		$log=$this->message.\PHP_EOL;

		switch($this->code)
		{
			case self::CONNECT:
				foreach(['db','host','port','user'] as $k)
					$data[$k]=$this->params[$k] ?? '-';

				$log.=<<<LOG
Host: {$data['host']}
Port: {$data['port']}
User: {$data['user']}
Database: {$data['db']}
LOG;

				if(isset($this->params['socket']))
				{
					$data['socket']=$this->params['socket'] ?? '-';
					$log.='Socket: '.$data['socket'].\PHP_EOL;
				}

				$log.=<<<LOG
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
				$data['p']=\print_r($this->params,true);

				$log.=<<<LOG
Query: {$data['q']}
Params: {$data['p']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
			break;

			case self::VALUE:
				$data['q']=$this->query;

				$log.=<<<LOG
Field name: {$data['q']}
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
			break;

			case self::OTHER:
				if($this->query)
				{
					$data['q']=$this->query;
					$log.='Query: '.$this->query.\PHP_EOL;
				}

				$log.=<<<LOG
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

		return $log;
	}
}

# Not required here because class name matches filename
return EM::class;