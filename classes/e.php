<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Eleanor's main exception that classifies who is responsible for the error. */
class E extends \Eleanor\Abstracts\E
{
	const int
		/** Error in php code: the responsible person is the one who wrote this code (developer) */
		PHP=1,

		/** System error (e.g. no access to a file): the responsible person is the one who can fix it (sysadmin) */
		SYSTEM=2,

		/** Data error (e.g. incorrect file format): the person who created the data is responsible */
		DATA=3,

		/** User error (e.g. incorrectly transmitted data): no one is responsible 😆 */
		USER=4,

		/** External resource error (e.g. invalid API response): the external service or resource provider is responsible */
		EXTERNAL=5;

	protected(set) static bool $logging=false;

	/** @param string $message The same as in \Exception
	 * @param int $code Constants of class (from above) should be used
	 * @param ?\Throwable $previous The same as in \Exception
	 * @param ?string $file Path to the file where the exception was thrown
	 * @param ?int $line Line number where the exception was thrown
	 * @param string $hint Hint on how to fix an exception
	 * @param mixed $input Input data that caused an exception */
	function __construct(string$message,int$code=self::USER,?\Throwable$previous=null,?string$file=null,?int$line=null,readonly string$hint='',readonly mixed$input=null)
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
		$intro=match($this->code){
			$this::PHP=>'PHP',
			$this::DATA=>'Data',
			$this::USER=>'User',
			$this::SYSTEM=>'System',
			$this::EXTERNAL=>'External',
			default=>'Unknown'
		};

		return $intro." exception: $this->message\n File: $this->file[$this->line]"
			.($this->hint ? "\n Hint: ".$this->hint : '');
	}

	/** Logging */
	function Log():void
	{
		if(static::$logging)
			return;

		$type=match($this->code){
			self::PHP=>'php',
			self::DATA=>'data',
			self::USER=>'user',
			self::SYSTEM=>'system',
			self::EXTERNAL=>'external',
			default=>'unknown'
		};

		static::$logging=true;

		try{
			$this->LogWriter(
				\Eleanor\Library::$logs.$type,
				\md5($this->line.$this->file.$this->code.$this->message.$this->hint)
			);
		}finally{
			static::$logging=false;
		}
	}

	/** Entry in a .log file
	 * @param array $data The accumulated data of this exception
	 * @return string Item for log file */
	protected function LogItem(array&$data):string
	{
		$data['n']??=0;# Counter
		$data['n']++;

		$data['d']=\date('Y-m-d H:i:s');
		$data['l']=$this->line;
		$data['m']=$this->getMessage();
		$data['f']=$this->file;

		$log=$this->message.\PHP_EOL;

		if($this->hint)
			$log.='Hint: '.$this->hint.\PHP_EOL;

		if($this->input!==null)
			$log.='Input: '.\print_r($this->input,true).\PHP_EOL;

		if(\Eleanor\Library::$cli)
			$log.=<<<LOG
File: {$data['f']}[{$data['l']}]
Last happened: {$data['d']}, total: {$data['n']}
LOG;
		else
		{
			$data['u']=Uri::$raw;
			$log.=<<<LOG
File: {$data['f']}[{$data['l']}]
URL: {$data['u']}
Last happened: {$data['d']}, total: {$data['n']}
LOG;
		}

		return $log;
	}
}

# Not required here because class name matches filename
return E::class;