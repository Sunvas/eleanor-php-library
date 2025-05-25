<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor;
use Eleanor\Classes\E,
	Eleanor\Classes\Output,
	Eleanor\Traits\FL4E;

/** Encoding of Eleanor's files */
const CHARSET = 'UTF-8';

\mb_internal_encoding(CHARSET);

/** Site path, relative to the domain */
\defined('Eleanor\SITEDIR')||\define('Eleanor\SITEDIR',\rtrim(\dirname($_SERVER['PHP_SELF'] ?? '/'),'/\\').'/');

/** Http protocol */
\defined('Eleanor\PROTOCOL')||\define('Eleanor\PROTOCOL',($_SERVER['HTTPS'] ?? '')=='on' ? 'https://' : 'http://');

/** The starting point of internal time is used to reduce used timestamps */
\defined('Eleanor\BASE_TIME')||\define('Eleanor\BASE_TIME',\mktime(0,0,0,1,1,2025));

/** Windows detector */
\define('Eleanor\W',\stripos(PHP_OS,'win')===0);

/** Obtaining path to file and line number where error has happened
 * @param null|string|object $filter null - previous step, object - last mention, class - first non-mention
 * @return array ['file'=>,'line'=>N] */
function BugFileLine(null|string|object$filter=null):array
{
	$iso=\is_object($filter);
	$db=\debug_backtrace($iso ? DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS : DEBUG_BACKTRACE_IGNORE_ARGS);

	#Bug in previous step
	if($filter===null)
		return[
			'file'=>$db[1]['file'],
			'line'=>$db[1]['line'],
		];

	#Last mention of the object (or its clone)
	if($iso)
	{
		$found=[];

		foreach(\array_slice($db,1) as $item)
			if(isset($item['object']) and $item['object']::class===$filter::class)
				$found=[
					'file'=>$item['file'],
					'line'=>$item['line'],
				];
			elseif($found)
				break;

		return$found;
	}

	#The first non-mention
	foreach(\array_slice($db,1) as $item)
		if(!isset($item['class']) or $item['class']!=$filter)
			return[
				'file'=>$item['file'],
				'line'=>$item['line'],
			];

	return[];
}

/** Safe including of the file: in case of ParseError information will be put into log
 * @param string $file Full path to the file
 * @param array $vars Variables for a scope of file
 * @throws E
 * @return mixed */
function AwareInclude(string$file,array$vars=[]):mixed
{
	if(!\is_file($file))
		throw new E('Missing file '.(\str_starts_with($file,SITEDIR) ? \substr($file,\strlen(SITEDIR)) : $file),E::SYSTEM);

	if($vars)
		\extract($vars,EXTR_PREFIX_INVALID|EXTR_OVERWRITE|EXTR_REFS,'var');

	\ob_start();

	try
	{
		$r=include \func_get_arg(0);
		\ob_end_flush();
	}
	catch(\Throwable$E)
	{
		\ob_end_clean();
		throw$E;
	}

	return$r===null ? true : $r;
}

/** “Quiet” code execution when error display is disabled
 * @param callable $Func
 * @return mixed */
function QuietExecution(callable$Func):mixed
{
	\set_error_handler(fn()=>null);

	try
	{
		$ret=\call_user_func($Func);
	}
	catch(\Throwable)
	{
		$ret=null;
	}

	\restore_error_handler();
	return$ret;
}

/** Eleanor's Blue Screen of Death
 * @param string $error Error message
 * @param int|string $code Error code
 * @param ?string $file Path to file
 * @param ?int $line Line number
 * @param ?string $hint Hint for fixing
 * @param ?array $payload Data that led to crash */
function BSOD(string$error,int|string$code,?string$file,?int$line,?string$hint=null,?array$payload=null):never
{
	$Tpl=new Classes\Template(Library::$bsod);
	$type=match(Library::$bsodtype){
		Output::HTML=>'html',
		Output::JSON=>'json',
		default=>'text'
	};
	$out=(string)$Tpl->{$type}($error,$code,$file,$line,$hint,$payload);

	\ob_clean();
	Output::SendHeaders(Library::$bsodtype,503);

	die($out);
}

/** Basic class from which all others classes are recommended to be extended: it contains the necessary hooks that make
 * it easier to find and fix bugs */
abstract class Basic
{
	/** Handling calls of non-existent static methods.
	 * This method may seem strange: in case you call a non-existent static method, a Fatal error will be generated,
	 * which can be caught and logged. Idea of method is convenience in a successor class: when __callStatic is not
	 * designed to execute everything it can pass "excess" execution to this method.
	 * @param string $n Name of a non-existent method
	 * @param array $a arguments for
	 * @throws \BadMethodCallException */
	static function __callStatic(string$n,array$a)
	{
		throw new class(
			'Called undefined method '.static::class.' :: '.$n,
			1,...BugFileLine(static::class)
		) extends \BadMethodCallException{ use FL4E; };
	}

	/** Handling calls of non-existent methods.
	 * This method may seem strange: in case you call a non-existent static method, a Fatal error will be generated,
	 * which can be caught and logged. Idea of method is convenience in a successor class: when __call is not
	 * designed to execute everything it can pass "excess" execution to this method.
	 * @param string $n Name of a non-existent method
	 * @param array $a Array of arguments for called method
	 * @return mixed *@throws \BadMethodCallException */
	function __call(string$n,array$a):mixed
	{
		if(\property_exists($this,$n) and \is_object($this->$n) and \method_exists($this->$n,'__invoke'))
			return \call_user_func_array([ $this->$n,'__invoke' ],$a);

		throw new class(
			'Called undefined method '.$this::class.' -› '.$n,
			0,...BugFileLine($this)
		) extends \BadMethodCallException{ use FL4E; };
	}

	/** Handling of getting non-existent properties
	 * This method may seem strange: in case you try to get a non-existent property, a Notice will be generated,
	 * which can be caught and logged. Idea of method is convenience in a successor class: when __get is not
	 * designed to provide all requested properties it can pass getting of non-existent properties to this method.
	 * @param string $n Name of requested property
	 * @return mixed
	 * @throws E */
	function __get(string$n):mixed
	{
		throw new E('Reading unknown property '.$this::class.' -› '.$n,E::PHP,...BugFileLine($this));
	}
}

/** Assign On Demand - delayed object creation. Eleanor's own realization of ReflectionClass::newLazyProxy which author
 * finds too clunky. Quite often there are situations when an object needs to be created without an explicit need for it.
 * As a rule, in CMS object \MySQLi is created at the early stage (during initialization), and then internal components
 * use it as needed. But on plain sites, not every page is interacting with the database, so there is no point in
 * wasting server resources, especially on shared hosting. This class allows you to define an object constructor that
 * will create an object when it really needed. Example:
 * class A
 * {
 *    function Say(){ echo 'Hi'; }
 * }
 *
 * class B
 * {
 *    static null|Assign|A $o;
 * }
 *
 * Assign::For(B::$o,fn()=>new A);
 *
 * echo get_class(B::$o); //Assign
 * B::$o->Say(); //Hi
 * echo get_class(B::$o); //A
 */
class Assign extends Basic
{
	/** @param ?object &$link Reference where the object will be written to when created
	 * @param \Closure $Creator The function to return the object */
	function __construct(protected ?object &$link,protected \Closure$Creator){}

	/** Creating object */
	protected function Create():void
	{
		$this->link=\call_user_func($this->Creator);
	}

	/** Syntactic sugar for binding a variable to a future object */
	static function For(?object &$link,\Closure$Creator):void
	{
		$link=new static($link,$Creator);
	}

	function __get(string$n):mixed
	{
		$this->Create();
		return$this->link->$n;
	}

	function __call(string$n,array$a):mixed
	{
		$this->Create();
		return \call_user_func_array([$this->link,$n],$a);
	}
}

/** Main class of Eleanor PHP Library */
#[\AllowDynamicProperties]
class Library extends Basic
{
	static
		/** @var ?callable $old_error_handler Previous error handler */
		$old_error_handler,

		/** @var ?callable $old_exception_handler Previous exception handler */
		$old_exception_handler,

		/** @var callable $log_filter Selective logging filter (if $log_all_errors and $log_all_exceptions are disabled) */
		$log_filter;

	static bool
		/** @var bool $log_all_errors Flag to enable logging of all errors */
		$log_all_errors=true,

		/** @var bool $log_all_exceptions Flag to enable logging of all exceptions */
		$log_all_exceptions=true,

		/** @var bool $logs_enabled Flag to enable logging */
		$logs_enabled=true;

	static string
		/** @var string $logs Path to the directory where logs will be placed */
		$logs,

		/** @var string $bsod Path to screen of death file */
		$bsod=__DIR__.'/bsod.php',

		/** @var string $bsodtype Type of screen of death */
		$bsodtype='text/html';

	/** @var array $creators A set of creators for future objects */
	protected array $creators=[];

	/** Defining a constructor for creating shared objects within this object-storage
	 * @param string $n Property name
	 * @param array $a Parameters of the constructor or \Closure
	 * @return mixed */
	function __call(string$n,array$a):mixed
	{
		$this->creators[$n]=$a;
		return$this;
	}

	/** Obtaining object
	 * @throws E */
	function __get(string$n):mixed
	{
		return$this->$n=$this($n);
	}

	/** One-time creation of an object according to a predefined constructor
	 * @param string $n Имя класса
	 * @throws E */
	function __invoke(string$n):mixed
	{
		$creator=[];

		if(isset($this->creators[$n]))
		{
			$creator=$this->creators[$n];

			//Object creating via \Closure
			if(\count($creator)==1 and $creator[0] instanceof \Closure)
				return \call_user_func($creator[0]);
		}

		$lcc=\strtolower($n);#LowerCase class
		$path=__DIR__."/classes/{$lcc}.php";

		if(\is_file($path))
		{
			$class=require$path;

			if(\class_exists($class,false))
				return new $class($creator);
		}

		throw new E('Trying to construct object from unknown class '.$n,E::PHP,...BugFileLine($this));
	}
}

#By default, all logs are stored in the ./log folder of the site. It is better to deny access to this folder.
Library::$logs=$_SERVER['DOCUMENT_ROOT'].SITEDIR.'logs/';

#Filter receives path to file with error/exception as first parameter and allows or disallows logging.
Library::$log_filter=fn($f)=>\str_starts_with($f,__DIR__.DIRECTORY_SEPARATOR) || \str_starts_with($f,$_SERVER['DOCUMENT_ROOT'].SITEDIR);

Library::$old_error_handler=\set_error_handler(function($c,$error,$f,$l,$context=null){
	if(!Library::$log_all_errors and !\call_user_func(Library::$log_filter,$f,$c))
	{
		if(Library::$old_error_handler)
			\call_user_func(Library::$old_error_handler,$c,$error,$f,$l,$context);

		return;
	}

	if($c and Library::$logs_enabled and \class_exists('\Eleanor\Classes\E'))#
	{
		if($c & E_ERROR)
			$type='Error ';
		elseif($c & E_WARNING)
			$type='Warning ';
		elseif($c & E_NOTICE)
			$type='Notice ';
		elseif($c & E_PARSE)
			$type='Parse error ';
		else
			$type='';

		new E($type.$error,E::PHP,file:$f,line:$l,input:$context)->Log();

		#Display errors only if they are related to php code parsing
		if($c & E_PARSE)
			BSOD($type.$error,$c,$f,$l,null,$context);
	}
},E_ALL);

Library::$old_exception_handler=\set_exception_handler(function(\Throwable$E){
	$f=$E->getFile();
	$l=$E->getLine();
	$c=$E->getCode();
	$m=$E->getMessage();

	if($E instanceof Interfaces\Loggable)
		$E->Log();
	elseif(Library::$log_all_exceptions or \call_user_func(Library::$log_filter,$f,$c)
		#Patch for the case when autoloader is off
		and (\class_exists('\Eleanor\Classes\E',false) or include(__DIR__.'/classes/e.php')))
	{
		$c=match(true){
			$E instanceof \LogicException || $E instanceof \Error=>E::PHP,
			$E instanceof \RuntimeException=>E::SYSTEM,
			$E instanceof \ValueError=>E::DATA,
			default=>E::USER
		};

		new E($m,$c,$E,file:$f,line:$l)->Log();
	}

	BSOD($m,$c,$f,$l,\property_exists($E,'hint') ? $E->hint : null,\property_exists($E,'input') ? $E->input : null);
});

#Implementing autoloader: it will only load what is directly related to Eleanor PHP Library
\spl_autoload_register(function(string$c){
	if(!\str_starts_with($c,__NAMESPACE__.'\\'))
		return;

	#LowerCase class
	$lcc=\substr($c,\strlen(__NAMESPACE__));
	$lcc=\strtolower($lcc);

	$path=__DIR__.DIRECTORY_SEPARATOR.\strtr($lcc,'\\',DIRECTORY_SEPARATOR).'.php';
	$exists=\is_file($path);

	if($exists)
	{
		$r=require$path;

		#Trying to make the class available from \Eleanor.
		if(\class_exists($c,false) or (\is_string($r) and \class_exists($r,false) and \class_alias($r,$c,false)))
			return;
	}

	if(!$exists or !\class_exists($c,false) and !\interface_exists($c,false) and !\trait_exists($c,false) and !\enum_exists($c,false))
	{
		$what=match(\strstr($lcc, '\\', true)){
			'enums'=>'Enum',
			'traits'=>'Trait',
			'interfaces'=>'Interface',
			'abstracts'=>'Abstract class',
			default=>'Class'
		};

		if(\class_exists('\Eleanor\Classes\E',false) or include(__DIR__.'/classes/e.php'))
			throw new E($what.' not found: '.$c,E::PHP,...BugFileLine());
	}
});

#Поддержка IDN
\define('Eleanor\PUNYCODE',\filter_var($_SERVER['HTTP_HOST'] ?? '',FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME) ? $_SERVER['HTTP_HOST'] : '');
\define('Eleanor\DOMAIN',\str_starts_with(PUNYCODE,'xn--') ? Classes\Punycode::Domain(PUNYCODE,false) : PUNYCODE);