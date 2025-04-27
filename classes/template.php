<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use function Eleanor\BugFileLine;

/** Загрузчик шаблонов, в зависимости от типа */
enum Template_Type
{
	case dir;
	case array;
	case object;

	/** Получение шаблона в зависимости от типа
	 * @param mixed ...$a описание смотреть в методах ниже
	 * @return ?string */
	function Get(...$a):?string
	{
		return match($this){
			self::dir => $this->Dir(...$a),
			self::array => $this->Array(...$a),
			self::object => $this->Object(...$a),
		};
	}

	/** Шаблонизатор на каталогах
	 * @param string $n Имя шаблона
	 * @param array $p Переменные шаблона
	 * @param array $files Перечень файлов из каталога
	 * @return ?string */
	private function Dir(string$n,array$p,array$files):?string
	{
		if(!isset($files[$n]))
			return null;

		try
		{
			ob_start();

			$content=\Eleanor\AwareInclude($files[$n],$p);

			if($content===null)
				return null;

			if($content===1)
				$content='';

			return$content.ob_get_contents();
		}
		finally
		{
			ob_end_clean();
		}
	}

	/** Шаблонизатор на массиве. Переменные поддерживаются только если значения это \Closure. Требования к \Closure
	 * аналогичны требованиям к методам объекта - смотри ниже.
	 * @param string $n Имя шаблона
	 * @param array $p Переменные шаблона
	 * @param array $a Массив с шаблонами
	 * @return ?string */
	private function Array(string$n,array$p,array$a):?string
	{
		if(isset($a[$n]))
			return($a[$n] instanceof \Closure ? call_user_func_array($a[$n],$p) : $a[$n]);

		return null;
	}

	/** Шаблонизатор на объекте. Переменные передаются в методы в виде именованных аргументов, значения по умолчанию
	 *  рекомендуется получать через spread-операторор ..., каждый метод должен возвращать ?string.
	 * @param string $n Имя шаблона
	 * @param array $p Переменные шаблона
	 * @param object $O Объект с методами
	 * @return ?string */
	private function Object(string$n,array$p,object$O):?string
	{
		$o=[$O,$n];

		//Поддержка прямых методов и __call
		if(method_exists($O,$n) or is_callable($o))
			return call_user_func_array($o,$p);

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

	/** @var array Названия свойств, которые при клонировании объектов должны стать ссылками на оригинальны свойства */
	protected static array $linking=['default','queue','loaded'];

	/** @param array|string $queue Queue to load */
	function __construct(array|string$queue=[])
	{
		$this->queue=(array)$queue;
		parent::__construct();
	}

	/** Источник шаблонов
	 * @param string $n Название шаблона
	 * @param array $p Переменные шаблона
	 * @throws E
	 * @return string */
	protected function _(string$n,array$p=[]):string
	{
		while($this->queue)
		{
			$item=array_pop($this->queue);

			#Шаблонизатор в виде массива
			if(is_array($item))
				$this->loaded[]=[Template_Type::array,$item];

			#Шаблонизатор в виде объекта
			elseif(is_object($item))
				$this->loaded[]=[Template_Type::object,$item];

			#Шаблонизатор в виде каталога: в каталоге файлы
			elseif(is_dir($item))
			{
				$found=glob(rtrim($item,'/\\').DIRECTORY_SEPARATOR.'*'.static::EXT);
				$files=[];

				if($found)
					foreach($found as $file)
						$files[ basename($file,static::EXT) ]=$file;

				if($files)
					$this->loaded[]=[Template_Type::dir,$files];
			}

			#Шаблонизатор на файле: либо объект, либо массив
			elseif(is_file($item))
			{
				ob_start();
				$content=\Eleanor\AwareInclude($item);
				ob_end_clean();

				if(is_array($content))
					$this->loaded[]=[Template_Type::array,$content];
				elseif(is_object($content))
					$this->loaded[]=[Template_Type::object,$content];
			}
		}

		#Если передан единственный параметр в виде массива, в выгружаем в качестве параметров именно его
		if(isset($p[0]) and count($p)==1 and is_array($p[0]))
			$p=$p[0];

		$p+=$this->default;

		#Поиск шаблона
		foreach($this->loaded as [$Type,$item])
		{
			$result=$Type->Get($n,$p,$item);

			if(null!==$result)
				return$result;
		}

		throw new E("Template '{$n}' was not found",E::PHP,...BugFileLine($this));
	}
}

return Template::class;