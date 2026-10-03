<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor;

use Eleanor\Classes\{E, Output};
use Eleanor\Traits\FL4E;

/** Encoding of Eleanor's files and internal string operations */
const CHARSET = 'UTF-8';

\mb_internal_encoding(CHARSET);

/** Base domain name */
\defined('Eleanor\DOMAIN')||\define('Eleanor\DOMAIN',\filter_var($_SERVER['HTTP_HOST'] ?? '',\FILTER_VALIDATE_DOMAIN,\FILTER_FLAG_HOSTNAME) ? $_SERVER['HTTP_HOST'] : '');

/** Base site path relative to the domain root with trailing slash */
\defined('Eleanor\SITEDIR')||\define('Eleanor\SITEDIR',\rtrim(\dirname($_SERVER['SCRIPT_NAME'] ?? '/'),'/\\').'/');

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

	# Bug in previous step
	if($filter===null)
		return[
			'file'=>$db[1]['file'] ?? '',
			'line'=>$db[1]['line'] ?? 0,
		];

	# Last mention of the object (or its clone)
	if($iso)
	{
		$found=[];

		foreach(\array_slice($db,1) as $item)
		{
			if(isset($item['object']))
			{
				# Extracting classname from protected link property of Assign class
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

		return $found;
	}

	# The first non-mention
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

	# Storing include path in the unnamed variable so extract(EXTR_OVERWRITE) cannot replace it with a user-supplied variable.
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

	return $r;
}

/** Execute callback quietly with temporary error suppression.
 * PHP errors are ignored and thrown exceptions are converted to null.
 * @param callable $Func Callback to execute
 * @param int $level Error level to suppress
 * @param array $params Arguments passed to the callback
 * @return mixed Callback return value or null on exception */
function QuietCall(callable$Func,int$level=\E_WARNING|\E_NOTICE,array$params=[]):mixed
{
	\set_error_handler(fn()=>1,$level);

	try{
		return $Func(...$params);
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
 * @param ?array $input Data associated with the crash
 * @return never */
function BSOD(string$error,int|string$code,?string$file,?int$line,?string$hint=null,?array$input=null):never
{
	try{
		$Tpl=new Classes\Template(Library::$bsod);
		$type=Library::$cli ? 'cli' : match(Library::$bsodtype){
			Output::HTML=>'html',
			Output::JSON=>'json',
			default=>'text'
		};

		$out=$Tpl($type,$error,$code,$file,$line,$hint,$input);
	}catch(\Throwable$E){
		$out=$E->getMessage();
		Library::$bsodtype='text/plain';
	}

	while(\ob_get_level())
		\ob_end_clean();

	if(Library::$cli)
		\fwrite(\STDERR, $out);
	else
	{
		Output::SendHeaders(Library::$bsodtype,503);
		echo$out;
	}

	exit(1);
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

	$lc=\strtolower($dest);
	$path=$dir.$lc;
	$exists=\is_file($path);

	# Support of kebab-case filenames
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

		if(\class_exists($c,false) or \interface_exists($c,false) or \enum_exists($c,false) or \trait_exists($c,false))
			return;

		# Trying to make the symbol available from namespace
		if(\is_string($r) and \class_alias($r,$c,false))
			return;
	}

	if(!$exists or !\class_exists($c,false) and !\interface_exists($c,false) and !\enum_exists($c,false) and !\trait_exists($c,false))
	{
		$what=match(\strstr(\ltrim($lc,'/'), '/', true)){
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

# Autoloader loads only files from Eleanor PHP Library
\spl_autoload_register(Autoloader(...));

/** Base class recommended for inheritance by all framework classes.
 * Provides common debugging and error-handling hooks that simplify bug detection and diagnostics. */
abstract class Basic
{
	/** Handle calls to undefined static methods.
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
		if(\property_exists($this,$n) and \is_object($this->$n) and \is_callable($this->$n))
			return \call_user_func_array($this->$n,$a);

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
	/** @var array|\Closure Arguments passed to the creator closure */
	public array|\Closure $args;

	/** @param ?object &$link Reference that will receive the created object
	 * @param \Closure $Creator Closure that creates and returns the target object
	 * @param mixed ...$args Arguments passed to the creator closure */
	function __construct(protected ?object&$link,protected \Closure$Creator,...$args)
	{
		$this->args=$args;
	}

	/** Create the target object and replace the proxy reference with it */
	function Create():void
	{
		$this->link=\call_user_func_array(
			$this->Creator,
			\is_array($this->args) ? $this->args : (array)\call_user_func($this->args)
		);
	}

	/** Bind the variable to the lazy object creator */
	static function Bind(?object&$link,\Closure$Creator,...$args):void
	{
		$link=new static($link,$Creator,...$args);
	}

	/** Create the target object and read its property by reference.
	 * If the property cannot be returned by reference (readonly, no &__get), falls back to by-value access.
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

		return $link;
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

	/** Create the target object and invoke it
	 * @return mixed */
	function __invoke(...$a):mixed
	{
		$this->Create();
		return \call_user_func_array($this->link,$a);
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
abstract class Library extends Basic
{
	static
		/** @var ?callable Previous error handler */
		$old_error_handler,

		/** @var ?callable Previous exception handler */
		$old_exception_handler,

		/** @var callable Selective handler for errors (file, code): should return true if the error should be handled */
		$handle_errors,

		/** @var callable Selective handler for exceptions (Exception): should return true if the exception should be handled */
		$handle_exceptions;

	/** @var bool Whether the script is running in CLI mode */
	static bool $cli=false;

	static string
		/** @var string Directory path where log files are stored */
		$logs,

		/** @var string Path to the Blue Screen of Death template */
		$bsod=__DIR__.'/bsod.php',

		/** @var string MIME type of the Blue Screen of Death response */
		$bsodtype='text/html';
}

if(\PHP_SAPI==='cli')
{
	Library::$cli=true;
	Library::$bsodtype='cli';

	if(empty($_SERVER['DOCUMENT_ROOT']))
		$_SERVER['DOCUMENT_ROOT']=\getcwd();
}

# By default, logs are stored in the site's ./logs directory. Web access to this directory should be restricted.
Library::$logs=\rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).'/logs/';

# The filter receives the source file path and decides whether the error/exception should be logged.
Library::$handle_errors=fn($f)=>\str_starts_with($f,__DIR__.\DIRECTORY_SEPARATOR) || \str_starts_with($f,\rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR);
Library::$handle_exceptions=fn(\Throwable$E)=>\call_user_func(Library::$handle_errors,$E->getFile());

Library::$old_error_handler=\set_error_handler(function($c,$error,$f,$l,$context=null):void{
	# Skip @ suppressed errors
	if(!(\error_reporting() & $c))
		return;

	if(!\call_user_func(Library::$handle_errors,$f,$c))
	{
		if(Library::$old_error_handler)
			\call_user_func(Library::$old_error_handler,$c,$error,$f,$l,$context);

		return;
	}

	if($c & (\E_ERROR | \E_USER_ERROR | \E_RECOVERABLE_ERROR))
		$type='Error ';
	elseif($c & (\E_WARNING | \E_USER_WARNING))
		$type='Warning ';
	elseif($c & (\E_NOTICE | \E_USER_NOTICE))
		$type='Notice ';
	elseif($c & (\E_DEPRECATED | \E_USER_DEPRECATED))
		$type='Deprecated ';
	else
		$type='';

	new E($type.$error,E::PHP,file:$f,line:$l,input:$context)->Log();
});

Library::$old_exception_handler=\set_exception_handler(function(\Throwable$E):void{
	if(!\call_user_func(Library::$handle_exceptions,$E))
	{
		if(Library::$old_exception_handler)
			\call_user_func(Library::$old_exception_handler,$E);

		return;
	}

	$f=$E->getFile();
	$l=$E->getLine();
	$c=$E->getCode();
	$m=$E->getMessage();

	if($E instanceof Interfaces\Loggable)
		$E->Log();

	# Patch for the case when autoloader is off
	elseif(\class_exists('\Eleanor\Classes\E',false) or include(__DIR__.'/classes/e.php'))
	{
		$t=match(true){
			$E instanceof \ValueError=>E::DATA,
			$E instanceof \LogicException || $E instanceof \Error=>E::PHP,
			$E instanceof \RuntimeException=>E::SYSTEM,
			default=>E::USER
		};

		new E($m,$t,$E,file:$f,line:$l,input:['code'=>$c])->Log();
	}

	BSOD($m,$c,$f,$l,\property_exists($E,'hint') ? $E->hint : '',\property_exists($E,'input') ? $E->input : null);
});