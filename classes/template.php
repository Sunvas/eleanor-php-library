<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

use function Eleanor\{BugFileLine, AwareInclude};

/** Template loader */
enum Template_Type
{
	case dir;
	case array;
	case object;

	/** Obtaining a type-specific templates
	 * @param mixed ...$a See description in methods below
	 * @return ?string */
	function Get(...$a):?string
	{
		return match($this){
			self::dir => $this->Dir(...$a),
			self::array => $this->Array(...$a),
			self::object => $this->Object(...$a),
		};
	}

	/** Templates based on a directory with files
	 * @param string $n Template name
	 * @param array $p List of variables
	 * @param string $ext Extension of file
	 * @param string $path Path to files
	 * @param array $files List of files
	 * @return ?string */
	private function Dir(string$n,array$p,string$ext,string$path,array$files):?string
	{
		if(!\in_array($n,$files))
			return null;

		try
		{
			\ob_start();

			$content=AwareInclude($path.\DIRECTORY_SEPARATOR.$n.$ext,$p);

			if($content===null)
				return null;

			if($content===1)
				$content='';

			return $content.\ob_get_contents();
		}
		finally
		{
			\ob_end_clean();
		}
	}

	/** Templates based on array. Variables are supported only for \Closure-s. The requirements for \Closure are equal
	 * to those for object methods - see below.
	 * @param string $n Template name
	 * @param array $p List of variables
	 * @param array $a Array with string or \Closure templates
	 * @return ?string */
	private function Array(string$n,array$p,array$a):?string
	{
		if(isset($a[$n]))
			return($a[$n] instanceof \Closure ? \call_user_func_array($a[$n],$p) : $a[$n]);

		return null;
	}

	/** Templates based on object. Variables are passed to methods as named arguments, default values are recommended
	 * to be obtained via spread-operator ..., each method should return ?string.
	 * @param string $n Template name
	 * @param array $p List of variables
	 * @param object $O Object with methods
	 * @return ?string */
	private function Object(string$n,array$p,object$O):?string
	{
		# Support only for explicit methods
		return \method_exists($O,$n) ? \call_user_func_array([$O,$n],$p) : null;
	}
}

/** Шаблонизатор */
class Template extends \Eleanor\Abstracts\Append implements \ArrayAccess
{
	/** Extension of processed files, must start with . */
	const string EXT='.php';

	/** @var array $queue Template sources to load. Values appended via $T[]. Accepted values are:
	 * string as paths to folder with files;
	 * string as paths to file returning array or object;
	 * object;
	 * array; */
	protected(set) array $queue=[];

	/** @var bool Flag allowing appending results to storage property */
	protected bool $append=true;

	/** @var Template $content Accessing object though content property disables appending and passes content of storage as 'content' variable to the next template */
	public Template $content {
		get{
			$this->append=false;
			return $this;
		}
	}

	/** @var array $loaded Loaded templates [type, contents] */
	protected array $loaded=[];

	/** @param array|string $queue See description above
	 * @var array $default Default variables being passed to all templates. Are set via ArrayAccess offsetSet */
	function __construct(array|string$queue=[],protected(set) array$default=[])
	{
		$this->queue=(array)$queue;
		$this->linking=['default','queue','loaded'];

		parent::__construct();
	}

	/** Template source
	 * @param string $n Template name
	 * @param array $a Arguments
	 * @return string
	 * @throws E */
	protected function _(string$n,...$a):string
	{
		while($this->queue)
		{
			$item=\array_pop($this->queue);

			#Templates based on array
			if(\is_array($item))
				$this->loaded[]=[Template_Type::array,$item];

			#Templates based on object
			elseif(\is_object($item))
				$this->loaded[]=[Template_Type::object,$item];

			#Templates based on directory: there are files inside it
			elseif(\is_dir($item))
			{
				$files=[];

				foreach(\scandir($item) as $f)
					if(\str_ends_with($f,static::EXT))
						$files[]=\strrchr($f,'.',true);

				if($files)
					$this->loaded[]=[Template_Type::dir,[\rtrim($item,'/\\'),$files]];
			}

			#Templates on file: either object or array
			elseif(\is_file($item))
			{
				\ob_start();
				$content=AwareInclude($item);
				\ob_end_clean();

				if(\is_array($content))
					$this->loaded[]=[Template_Type::array,$content];
				elseif(\is_object($content))
					$this->loaded[]=[Template_Type::object,$content];
			}
		}

		$vars=$this->default;

		#Flushing storage as content variable
		if(!$this->append)
		{
			$vars['content']=$this->storage;

			$this->storage='';
			$this->append=true;
		}

		#Searching for the template
		foreach($this->loaded as [$Type,$item])
		{
			if($Type===Template_Type::dir)
			{
				#The only parameter passed as an array to the directory template is extracted as variables. This allows to pass &links.
				$extract??=isset($a[0]) && \count($a)==1 && \is_array($a[0]);

				$result=$Type->Get($n,($extract ? $a[0] : $a)+$vars,static::EXT,...$item);
			}
			else
				$result=$Type->Get($n,$a+$vars,$item);

			if(null!==$result)
				return $result;
		}

		throw new E("Template '$n' not found",E::PHP,...BugFileLine($this));
	}

	/** Set default variable or append template source to the queue
	 * @param ?string $offset Key
	 * @param mixed $value Value */
	function offsetSet(mixed$offset,mixed$value): void
	{
		if($offset===null)
			$this->queue[]=$value;
		else
			$this->default[$offset]=$value;
	}

	/** Checking availability of default variable
	 * @param string $offset Key
	 * @return bool */
	function offsetExists(mixed$offset): bool
	{
		return isset($this->default[$offset]);
	}

	/** Unset default variable
	 * @param string $offset Key */
	function offsetUnset(mixed$offset): void
	{
		unset($this->default[$offset]);
	}

	/** Get default variable value. Undefined variables will return null, allowing you to create multidimensional arrays in a single line.
	 * @param string $offset Key
	 * @return mixed */
	function &offsetGet(mixed$offset):mixed
	{
		if(!isset($this->default[$offset]))
			$this->default[$offset]=null;

		return $this->default[$offset];
	}
}

# Not required here because class name matches filename
return Template::class;