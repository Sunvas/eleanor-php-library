<?php
/**
	Eleanor PHP Library © 2024
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
	 * @param array $d переменные по-умолчанию (из шаблонизатора)
	 * @param array $files Перечень файлов из каталога
	 * @return ?string */
	private function Dir(string$n,array$p,array$d,array$files):?string
	{
		if(!isset($files[$n]))
			return null;

		//Если передан только один параметр в виде массива, в выгружаем в качестве параметров именно его
		if(isset($p[0]) and count($p)==1 and is_array($p[0]))
			$p=$p[0];

		try
		{
			ob_start();

			$content=\Eleanor\AwareInclude($files[$n],$p+$d);

			if($content==1)
				$content='';

			return$content.ob_get_contents();
		}
		finally
		{
			ob_end_clean();
		}
	}

	/** Шаблонизатор на массиве. Переменные поддерживаются только если значения массива - \Closure
	 * @param string $n Имя шаблона
	 * @param array $p Переменные шаблона
	 * @param array $d переменные по-умолчанию (из шаблонизатора), передаются последним параметром в \Closure
	 * @param array $a Массив с шаблонами
	 * @return ?string */
	private function Array(string$n,array$p,array$d,array$a):?string
	{
		$p[]=$d;

		if(isset($a[$n]))
			return(string)($a[$n] instanceof \Closure ? call_user_func_array($a[$n],$p) : $a[$n]);

		return null;
	}

	/** Шаблонизатор на объекте
	 * @param string $n Имя шаблона
	 * @param array $p Переменные шаблона
	 * @param array $d переменные по-умолчанию (из шаблонизатора), передаются последним параметром в методы
	 * @param object $O Объект с методами
	 * @return ?string */
	private function Object(string$n,array$p,array$d,object$O):?string
	{
		$o=[$O,$n];
		$p[]=$d;

		//Поддержка прямых методов и __call
		if(method_exists($O,$n) or is_callable($o))
			return(string)call_user_func_array($o,$p);

		return null;
	}
}

/** Шаблонизатор */
class Template extends \Eleanor\Abstracts\Append
{
	/** Тип обрабатываемых файлов */
	const string EXT='.php';

	/** @var array Переменные, которые будут переданы во все шаблоны по умолчанию (assign) */
	public array
		$default=[],

		/** @var array Очередь на загрузку. Принимаются:
		 * пути в каталоги с файлами;
		 * пути к файлам возвращающих массив;
		 * пути к файлам возвращающим объект; */
		$queue=[];

	/** @var array Массив загруженных шаблонов */
	protected array $loaded=[];

	/** @var array Названия свойств, которые при клонировании объектов должны стать ссылками на оригинальны свойства */
	protected static array $linking=['default','queue','loaded'];

	/** @param array|string $queue Очередь на загрузку */
	function __construct(array|string$queue=[])
	{
		$this->queue=(array)$queue;
	}

	/** Источник шаблонов
	 * @param string $n Название шаблона
	 * @param array $p Переменные шаблона
	 * @throws E
	 * @return string */
	protected function _(string$n,array$p=[]):string
	{
		#Поиск шаблона среди уже загруженных
		foreach($this->loaded as [$Type,$files])
			if(null!==$result=$Type->Get($n,$p,$this->default,$files))
				return$result;

		#Среди загруженных ничего не нашли, жаль, значит будем "шерстить" очередь.
		foreach($this->queue as $k=>$item)
		{
			unset($this->queue[$k]);
			$result=null;

			if(is_array($item))
			{#Шаблонизатор в виде массива: в файле return []
				$this->loaded[$k]=[Template_Type::array,$item];
				$result=Template_Type::array->Get($n,$p,$this->default,$item);
			}

			elseif(is_object($item))
			{#Шаблонизатор в виде объекта: в файле return new class {}
				$this->loaded[$k]=[Template_Type::object,$item];
				$result=Template_Type::object->Get($n,$p,$this->default,$item);
			}

			elseif(is_dir($item))
			{#Шаблонизатор в виде каталога: в каталоге файлы
				$found=glob(rtrim($item,'/\\').DIRECTORY_SEPARATOR.'*'.static::EXT);
				$files=[];

				if($found)
					foreach($found as $file)
						$files[ basename($file,static::EXT) ]=$file;

				if($files)
				{
					$this->loaded[$k]=[Template_Type::dir,$files];
					$result=Template_Type::dir->Get($n,$p,$this->default,$files);
				}
			}

			elseif(is_file($item))
			{#Шаблонизатор на файле: либо объект, либо массив
				ob_start();
				$content=\Eleanor\AwareInclude($item,$this->default);
				ob_end_clean();

				if(is_array($content))
				{
					$this->loaded[$k]=[Template_Type::array,$content];
					$result=Template_Type::array->Get($n,$p,$this->default,$content);
				}
				elseif(is_object($content))
				{
					$this->loaded[$k]=[Template_Type::object,$content];
					$result=Template_Type::object->Get($n,$p,$this->default,$content);
				}
			}

			if($result!==null)
				return$result;
		}

		throw new E("Template '{$n}' was not found",E::PHP,...BugFileLine($this));
	}
}

return Template::class;