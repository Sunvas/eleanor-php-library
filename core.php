<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor;

use Eleanor\Classes\{E, Output};
use Eleanor\Traits\FL4E;

/** Encoding of Eleanor's files */
const CHARSET = 'UTF-8';

\mb_internal_encoding(CHARSET);

/** Base site path relative to the domain root with trailing slash */
\defined('Eleanor\SITEDIR')||\define('Eleanor\SITEDIR',\rtrim(\dirname($_SERVER['PHP_SELF'] ?? '/'),'/\\').'/');

/** Current request protocol prefix (http:// or https://) */
\defined('Eleanor\PROTOCOL')||\define('Eleanor\PROTOCOL',($_SERVER['HTTPS'] ?? '')=='on' ? 'https://' : 'http://');

/** Internal base timestamp used for compact relative time storage */
\defined('Eleanor\BASE_TIME')||\define('Eleanor\BASE_TIME',\mktime(0,0,0,1,1,2025));

/** Get the file path and line number where the error occurred.
 * @param null|string|object $filter Stack trace filter:
 *     - null: use previous stack frame
 *     - object: use last mention of object class
 *     - string: use first frame outside specified class
 * @return array ['file' => string, 'line' => int] */
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
		{
			if(isset($item['object']))
			{
				#Extracting classname from protected link property of Assign class
				if($item['object']::class===Assign::class)
				{
					$F=(function(){ return $this->link; })
						->bindTo($item['object'],$item['object']);
					$cmp=($F())::class;
				}
				else
					$cmp=$item['object']::class;

				if($cmp===$filter::class)
				{
					$found=[
						'file'=>$item['file'],
						'line'=>$item['line'],
					];
					continue;
				}
			}

			if($found)
				break;
		}

		return$found;
	}

	#The first non-mention
	foreach(\array_slice($db,1) as $item)
		if(($item['class'] ?? '')!=$filter and ($item['function'] ?? '')!=$filter)
			return[
				'file'=>$item['file'],
				'line'=>$item['line'],
			];

	return[];
}

/** Safely include the PHP file with custom scope variables.
 * Output buffering is automatically handled and exceptions are rethrown after cleanup.
 * @param string $file Absolute file path
 * @param array $vars Variables extracted into file scope. Invalid variable names are prefixed with "var".
 * @return mixed
 * @throws E */
function AwareInclude(string$file,array$vars=[]):mixed
{
	if(!\is_file($file))
		throw new E('Missing file '.(\str_starts_with($file,SITEDIR) ? \substr($file,\strlen(SITEDIR)) : $file),E::SYSTEM);

	#Storing include path in the unnamed variable so extract(EXTR_OVERWRITE) cannot replace it with a user-supplied variable.
	${''}=$file;

	if($vars)
		\extract($vars,EXTR_PREFIX_INVALID|EXTR_OVERWRITE|EXTR_REFS,'var');

	\ob_start();

	try
	{
		$r=include ${''};
		\ob_end_flush();
	}
	catch(\Throwable$E)
	{
		\ob_end_clean();
		throw$E;
	}

	return$r===null ? true : $r;
}

/** Execute callback quietly with temporary error suppression.
 * PHP errors are ignored and thrown exceptions are converted to null.
 * @param callable $Func Callback to execute
 * @param mixed ...$params Arguments passed to the callback
 * @return mixed Callback return value or null on exception */
function QuietExecution(callable$Func,...$params):mixed
{
	\set_error_handler(fn()=>null);

	try{
		return \call_user_func_array($Func,$params);
	}
	catch(\Throwable){
		return null;
	}
	finally{
		\restore_error_handler();
	}
}

/** Eleanor's Blue Screen of Death.
 * Displays fatal error information and terminates script execution.
 * @param string $error Error message
 * @param int|string $code Error code
 * @param ?string $file Source file path
 * @param ?int $line Source line number
 * @param ?string $hint Suggested fix or additional diagnostic hint
 * @param ?array $payload Data associated with the crash
 * @return never */
function BSOD(string$error,int|string$code,?string$file,?int$line,?string$hint=null,?array$payload=null):never
{
	$Tpl=new Classes\Template(Library::$bsod);
	$type=Library::$cli ? 'cli' : match(Library::$bsodtype){
		Output::HTML=>'html',
		Output::JSON=>'json',
		default=>'text'
	};

	$out=$Tpl($type,$error,$code,$file,$line,$hint,$payload);

	\ob_clean();

	if(Library::$cli)
	{
		\fwrite(\STDERR, $out);
		die;
	}

	Output::SendHeaders(Library::$bsodtype,503);
	die($out);
}

/** Namespace-aware class autoloader with support for class aliases, lowercase filenames, and kebab-case filenames.
 * @param string $c Fully qualified class name
 * @param string $dir Base directory for class lookup
 * @param string $ns Namespace filter
 * @throws E */
function Autoloader(string$c,string$dir=__DIR__,string$ns=__NAMESPACE__):void
{
	if(!\str_starts_with($c,$ns.'\\'))
		return;

	$dest=\substr($c,\strlen($ns));
	$dest=\strtr($dest,'\\','/').'.php';

	#LowerCase
	$lc=\strtolower($dest);

	$path=$dir.$lc;
	$exists=\is_file($path);

	#Support of kebab-case filenames
	if(!$exists)
	{
		$count=0;
		$kebab=\preg_replace('#([a-z])([A-Z])#','\\1-\\2',$dest,count:$count);

		if($count>0)
		{
			$path=$dir.\strtolower($kebab);
			$exists=\is_file($path);
		}
	}

	if($exists)
	{
		$r=(fn()=>require$path)();

		#Trying to make the class available from namespace.
		if(\class_exists($c,false) or (\is_string($r) and \class_exists($r,false) and \class_alias($r,$c,false)))
			return;
	}

	if(!$exists or !\class_exists($c,false) and !\interface_exists($c,false) and !\trait_exists($c,false) and !\enum_exists($c,false))
	{
		$what=match(\strstr(\ltrim($lc,'\\'), '\\', true)){
			'enums'=>'Enum',
			'traits'=>'Trait',
			'interfaces'=>'Interface',
			'abstracts'=>'Abstract class',
			default=>'Class'
		};

		if(\class_exists('\Eleanor\Classes\E',false) or include(__DIR__.'/classes/e.php'))
			throw new E($what.' not found: '.$c,E::PHP,...BugFileLine(__NAMESPACE__.'\Autoloader'));
	}
}

#Autoloader loads only files from Eleanor PHP Library
\spl_autoload_register(Autoloader(...));

/** Base class recommended for inheritance by all framework classes.
 * Provides common debugging and error-handling hooks that simplify bug detection and diagnostics. */
abstract class Basic
{
	/** Handle calls undefined static methods.
	 * Intended as the fallback helper for descendant implementations of __callStatic(). Allows subclasses to delegate
	 * unsupported calls while preserving detailed diagnostic information.
	 * @param string $n Undefined method name
	 * @param array $a Method arguments
	 * @throws \BadMethodCallException */
	static function __callStatic(string$n,array$a)
	{
		throw new class(
			'Called undefined method '.static::class.' :: '.$n,
			1,...BugFileLine(static::class)
		) extends \BadMethodCallException{ use FL4E; };
	}

	/** Handle calls to undefined methods.
	 * If a property with the same name exists and contains an invokable object, the object is executed instead.
	 * Intended as the fallback helper for descendant implementations of __call().
	 * @param string $n Undefined method name
	 * @param array $a Method arguments
	 * @return mixed
	 * @throws \BadMethodCallException */
	function __call(string$n,array$a):mixed
	{
		if(\property_exists($this,$n) and \is_object($this->$n) and \method_exists($this->$n,'__invoke'))
			return \call_user_func_array([ $this->$n,'__invoke' ],$a);

		throw new class(
			'Called undefined method '.$this::class.' -› '.$n,
			0,...BugFileLine($this)
		) extends \BadMethodCallException{ use FL4E; };
	}

	/** Handle access to undefined properties.
	 * Intended as the fallback helper for descendant implementations of __get() to provide detailed diagnostic information
	 * for unknown property access.
	 * @param string $n Requested property name
	 * @return mixed
	 * @throws E */
	function __get(string$n):mixed
	{
		throw new E('Reading unknown property '.$this::class.' -› '.$n,E::PHP,...BugFileLine($this));
	}
}

/** Assign on demand: lazy object proxy with reference replacement.
 * Delays object creation until first usage. Once created, the proxy replaces itself in the original referenced variable
 * with the real object instance. Useful for expensive services that may not be needed during every request. */
class Assign extends Basic implements \ArrayAccess
{
	/** @var ?array Arguments passed to the creator closure */
	public ?array $args;

	/** @param ?object &$link Reference that will receive the created object
	 * @param \Closure $Creator Closure that creates and returns the target object
	 * @param mixed ...$args Arguments passed to the creator closure */
	function __construct(protected ?object&$link,protected \Closure$Creator,...$args)
	{
		$this->args=$args ?: null;
	}

	/** Create the target object and replace the proxy reference with it */
	function Create():void
	{
		$this->link=\call_user_func_array($this->Creator,$this->args ?? []);
	}

	/** Bind the variable to the lazy object creator */
	static function Bind(?object&$link,\Closure$Creator,...$args):void
	{
		$link=new static($link,$Creator,...$args);
	}

	/** Create the target object and read its property by reference.
	 * @param string $n Property name
	 * @return mixed */
	function &__get(string$n):mixed
	{
		$this->Create();

		try{
			$link=&$this->link->$n;
		}catch(\Error){
			$link=$this->link->$n;
		}

		return$link;
	}

	/** Create the target object and call its method.
	 * @param string $n Method name
	 * @param array $a Method arguments
	 * @return mixed */
	function __call(string$n,array$a):mixed
	{
		$this->Create();
		return \call_user_func_array([$this->link,$n],$a);
	}

	function offsetSet(...$a):void
	{
		$this->__call(__FUNCTION__,$a);
	}

	function offsetExists(...$a):bool
	{
		return $this->__call(__FUNCTION__,$a);
	}

	function offsetUnset(...$a):void
	{
		$this->__call(__FUNCTION__,$a);
	}

	function &offsetGet(mixed$offset):mixed
	{
		$this->Create();
		return $this->link[$offset];
	}
}

/** Core class of Eleanor PHP Library.
 * Provides shared runtime settings, logging configuration, error/exception handler storage, and lazy object creation. */
#[\AllowDynamicProperties]
class Library extends Basic
{
	static
		/** @var ?callable Previous error handler */
		$old_error_handler,

		/** @var ?callable Previous exception handler */
		$old_exception_handler,

		/** @var callable Selective logging filter used when full error/exception logging is disabled */
		$log_filter;

	static bool
		/** @var bool Whether the script is running in CLI mode */
		$cli=false,

		/** @var bool Whether all errors should be logged */
		$log_all_errors=true,

		/** @var bool Whether all exceptions should be logged */
		$log_all_exceptions=true,

		/** @var bool Whether logging is enabled */
		$logs_enabled=true;

	static string
		/** @var string Directory path where log files are stored */
		$logs,

		/** @var string Path to the Blue Screen of Death template */
		$bsod=__DIR__.'/bsod.php',

		/** @var string MIME type of the Blue Screen of Death response */
		$bsodtype='text/html';

	/** @var array Registered factories for lazy object creation */
	protected(set) array $creators=[];

	/** Register a shared object factory
	 * @param string $n Property name
	 * @param array $a Factory definition where:
	 *     - $a[0] is a \Closure
	 *     - remaining elements are closure arguments
	 * @throws E
	 * @return static */
	function __call(string$n,array$a):static
	{
		if(\count($a)<1 or (!$a[0] instanceof \Closure))
			throw new E("First argument for '$n' constructor should be \\Closure",E::PHP,...BugFileLine($this));

		$this->creators[$n]=$a;
		return$this;
	}

	/** Lazily load and return the class object by property name.
	 * @throws E */
	function __get(string$n):mixed
	{
		return$this->$n=$this($n);
	}

	/** Create and return the object instance by class name.
	 * Uses registered factory if available; otherwise attempts to load the class file from the classes' directory.
	 * @param string $n Class name
	 * @param string $dir Base directory for class lookup
	 * @throws E */
	function __invoke(string$n,string$dir=__DIR__):mixed
	{
		$creator=[];

		if(isset($this->creators[$n]))
			return \call_user_func(...$this->creators[$n]);

		$lc=\strtolower($n);#LowerCase
		$path=$dir."/classes/$lc.php";
		$exists=\is_file($path);

		#Support of kebab-case filenames
		if(!$exists)
		{
			$count=0;
			$kebab=\preg_replace('#([a-z])([A-Z])#','\\1-\\2',$n,count:$count);

			if($count>0)
			{
				$path=$dir.'/classes/'.\strtolower($kebab).'.php';
				$exists=\is_file($path);
			}
		}

		if($exists)
		{
			$class=(fn()=>require$path)();

			if(!\is_string($class))
				$class=__NAMESPACE__.'\\'.$n;

			if(\class_exists($class,false))
				return new $class($creator);
		}

		throw new E('Trying to construct object from unknown class '.$n,E::PHP,...BugFileLine($this));
	}
}

if(php_sapi_name()==='cli')
{
	Library::$cli=true;
	Library::$bsodtype='cli';

	if(!$_SERVER['DOCUMENT_ROOT'])
		$_SERVER['DOCUMENT_ROOT']=\getcwd();
}

#By default, logs are stored in the site's ./logs directory. Web access to this directory should be restricted.
Library::$logs=\rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).'/logs/';

#The filter receives the source file path and decides whether the error/exception should be logged.
Library::$log_filter=fn($f)=>\str_starts_with($f,__DIR__.\DIRECTORY_SEPARATOR) || \str_starts_with($f,\rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR);

Library::$old_error_handler=\set_error_handler(function($c,$error,$f,$l,$context=null):void{
	#Skip @ suppressed errors
	if(!(\error_reporting() & $c))
		return;

	if(!Library::$log_all_errors and !\call_user_func(Library::$log_filter,$f,$c))
	{
		if(Library::$old_error_handler)
			\call_user_func(Library::$old_error_handler,$c,$error,$f,$l,$context);

		return;
	}

	if(Library::$logs_enabled and \class_exists('\Eleanor\Classes\E'))
	{
		if($c & \E_ERROR)
			$type='Error ';
		elseif($c & \E_WARNING)
			$type='Warning ';
		elseif($c & \E_NOTICE)
			$type='Notice ';
		elseif($c & \E_PARSE)
			$type='Parse error ';
		else
			$type='';

		new E($type.$error,E::PHP,file:$f,line:$l,input:$context)->Log();

		#Display errors only if they are related to php code parsing
		if($c & \E_PARSE)
			BSOD($type.$error,$c,$f,$l,null,$context);
	}
});

Library::$old_exception_handler=\set_exception_handler(function(\Throwable$E):void{
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

#IDN support
\define('Eleanor\PUNYCODE',\filter_var($_SERVER['HTTP_HOST'] ?? '',\FILTER_VALIDATE_DOMAIN,\FILTER_FLAG_HOSTNAME) ? $_SERVER['HTTP_HOST'] : '');
\define('Eleanor\DOMAIN',\str_starts_with(PUNYCODE,'xn--') ? Classes\Punycode::Domain(PUNYCODE,false) : PUNYCODE);