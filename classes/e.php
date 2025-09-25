<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Eleanor's main exception which answers the main question: who is responsible for? */
class E extends \Eleanor\Abstracts\E
{
	const int
		/** Error in php code: the responsible person is the one who wrote this code (developer) */
		PHP=1,

		/** System error (e.g. no access to a file): the responsible person is the one who can fix it (sysadmin) */
		SYSTEM=2,

		/** Data error (e.g. incorrect file format): the person who created the data is responsible. */
		DATA=3,

		/** User error (e.g. incorrectly transmitted data): no one is responsible 😆 */
		USER=4;

	/** @param string $message The same as in \Exception
	 * @param int $code Constants of class (from above) should be used
	 * @param ?\Throwable $previous The same as in \Exception
	 * @param ?string $file Path to the file where the exception was thrown
	 * @param ?int $line Line number where the exception was thrown
	 * @param string $hint Hint on how to fix an exception
	 * @param mixed $input Input data that thrown an exception */
	function __construct(string$message,int$code=self::USER,?\Throwable$previous=null,?string$file=null,?int$line=null,readonly string$hint='',readonly mixed$input=null)
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
		$intro=match($this->code){
			$this::PHP=>'PHP',
			$this::DATA=>'Data',
			$this::USER=>'User',
			$this::SYSTEM=>'System',
			default=>'Unknown'
		};

		return$intro.' exception: '.$this->message;
	}

	/** Logging */
	function Log():void
	{
		if(!\Eleanor\Library::$logs_enabled)
			return;

		$type=match($this->code){
			self::PHP=>'php',
			self::DATA=>'data',
			self::USER=>'user',
			self::SYSTEM=>'system',
			default=>'unknown'
		};

		$this->LogWriter(
			\Eleanor\Library::$logs.$type,
			\md5($this->line.$this->file.$this->code.$this->message)
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
		$data['m']=$this->getMessage();
		$data['f']=$this->file;

		$log=$this->message.PHP_EOL;

		if($this->input)
		{
			$input=\json_encode($this->input,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			$log.=$input!==false//Not everithing is convertable to JSON
				? 'JSONed input: '.$input.PHP_EOL
				: 'Serialized input: '.\serialize($this->input).PHP_EOL;
		}

		$log.=<<<LOG
File: {$data['f']}[{$data['l']}]
URL: {$data['u']}
Last happened: {$data['d']}, total: {$data['n']}
LOG;

		return$log;
	}
}

#Not necessary here, since class name equals filename
return E::class;