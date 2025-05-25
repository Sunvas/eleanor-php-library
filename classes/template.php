<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use function Eleanor\BugFileLine;

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
	 * @param array $files List of files
	 * @return ?string */
	private function Dir(string$n,array$p,array$files):?string
	{
		if(!isset($files[$n]))
			return null;

		try
		{
			\ob_start();

			$content=\Eleanor\AwareInclude($files[$n],$p);

			if($content===null)
				return null;

			if($content===1)
				$content='';

			return$content.\ob_get_contents();
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
		$o=[$O,$n];

		//Supporting of explicit methods and __call
		if(\method_exists($O,$n) or \is_callable($o))
			return \call_user_func_array($o,$p);

		return null;
	}
}

/** Шаблонизатор */
class Template extends \Eleanor\Abstracts\Append
{
	/** Extension of processed files */
	const string EXT='.php';

	public array
		/** @var array $default Default variables being passed to all templates.*/
		$default=[],

		/** @var array $queue Queue to load (array works better than SplDoublyLinkedList). Accepted:
		 * paths to folders with files;
		 * paths to files returning array;
		 * paths to files returning object;
		 * objects;
		 * arrays; */
		$queue=[];

	/** @var array $loaded Loaded templates [type, contents] */
	protected array $loaded=[];

	/** @var array Property names that should become references to the original properties after cloning objects */
	protected static array $linking=['default','queue','loaded'];

	/** @param array|string $queue Queue to load */
	function __construct(array|string$queue=[])
	{
		$this->queue=(array)$queue;
		parent::__construct();
	}

	/** Templates source
	 * @param string $n Template name
	 * @param array $p List of variables
	 * @throws E
	 * @return string */
	protected function _(string$n,array$p=[]):string
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
				$found=\glob(\rtrim($item,'/\\').DIRECTORY_SEPARATOR.'*'.static::EXT);
				$files=[];

				if($found)
					foreach($found as $file)
						$files[ \basename($file,static::EXT) ]=$file;

				if($files)
					$this->loaded[]=[Template_Type::dir,$files];
			}

			#Templates on file: either object or array
			elseif(\is_file($item))
			{
				\ob_start();
				$content=\Eleanor\AwareInclude($item);
				\ob_end_clean();

				if(\is_array($content))
					$this->loaded[]=[Template_Type::array,$content];
				elseif(\is_object($content))
					$this->loaded[]=[Template_Type::object,$content];
			}
		}

		#For the template on directory, the only parameter passed as an array is unloaded as variables
		$extract=isset($p[0]) && \count($p)==1 && \is_array($p[0]);

		#Searching for the template
		foreach($this->loaded as [$Type,$item])
		{
			$result=$Type->Get($n,$extract && $Type===Template_Type::dir ? $p[0]+$this->default : $p+$this->default,$item);

			if(null!==$result)
				return$result;
		}

		throw new E("Template '{$n}' was not found",E::PHP,...BugFileLine($this));
	}
}

return Template::class;