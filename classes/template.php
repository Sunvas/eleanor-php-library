<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use function Eleanor\BugFileLine;

/** Загрузчик шаблона
 * @param string $n Имя шаблона
 * @param array $p Переменные шаблона
 * @oaram array $def переменные по-умолчанию (из шаблонизатора)
 * @param string $type f,c,a
 * @param object|array $data Содержимое хранилища шаблонов
 * @return ?string */
function TemplateLoader(string$n,array$p,array$def,string$type,object|array$data):?string
{
	switch($type)
	{
		case'f':#Files
			if(!isset($data[ $n ]))
				break;

			try
			{
				//Если передан только один параметр в виде массива, в выгружаем в качестве параметров именно его
				if(isset($p[0]) and count($p)==1 and is_array($p[0]))
					$p=$p[0];

				ob_start();

				$content=\Eleanor\AwareInclude($data[ $n ],$p+$def);

				if($content==1)
					$content='';

				$echoed=ob_get_contents();

				if($echoed)
					$content.=$echoed;

				return$content;
			}
			finally
			{
				ob_end_clean();
			}
		break;

		case'c':#Classes
			$c=[$data,$n];

			if($def)
				$p[]=$def;

			if(method_exists($data,$n))
				return call_user_func_array($c,$p);

			if(is_callable($c) and false!==$s=call_user_func_array($c,$p))
				return$s;
		break;

		case'a':#Arrays
			if($def)
				$p[]=$def;

			if(isset($data[$n]))
				return $data[$n] instanceof \Closure ? call_user_func_array($data[$n],$p) : (string)$data[$n];
	}

	return null;
}

/** Шаблонизатор */
class Template extends \Eleanor\Abstracts\Append
{
	/** Тип обрабатываемых файлов */
	const string EXT='.php';

	/** @var array Переменные, которые будут переданы во все шаблоны по умолчанию (assign) */
	public array
		$default=[],

		/** @var array Очередь на загрузку. Принимаются: пути в каталоги с файлами - для файловых шаблонов,
		 * пути к файлам возвращающих массив (шаблонизатор на массивах), пути к файлам не возвращающим ничего или
		 * возвращающим полное имя класса - шаблоны на классах */
		$queue=[];

	/** @var array Массив загруженных шаблонов */
	protected array $loaded=[];

	/** @var array Названия свойств, которые при клонировании объектов должны стать ссылками на оригинальны свойства */
	protected static array $linking=['default','queue','loaded'];

	/** @param array|string $queue Очередь на загрузку */
	public function __construct(array|string$queue=[])
	{
		$this->queue=(array)$queue;
	}

	/** Источник шаблонов
	 * @param string $n Название шаблона
	 * @param array $p Переменные шаблона
	 * @throws EE
	 * @return string */
	protected function _(string$n,array$p=[]):string
	{
		#Поиск шаблона среди уже загруженных
		foreach($this->loaded as $source)
			if(null!==$result=TemplateLoader($n,$p,$this->default,...$source))
				return$result;

		#Среди загруженных ничего не нашли, жаль, значит будем "шерстить" очередь.
		foreach($this->queue as $k=>$path)
		{
			unset($this->queue[$k]);
			$result=null;

			if(is_array($path))
			{#Шаблонизатор в виде массива: в файле return []
				$this->loaded[$k]=['a',$path];
				$result=TemplateLoader($n,$p,$this->default,'a',$path);
			}

			elseif(is_object($path))
			{#Шаблонизатор в виде объекта: в файле return new class {}
				$this->loaded[$k]=['c',$path];
				$result=TemplateLoader($n,$p,$this->default,'c',$path);
			}

			elseif(is_dir($path))
			{#Нашли каталог, значит перед нами - файловый шаблонизатор
				$files=glob(rtrim($path,'/\\').DIRECTORY_SEPARATOR.'*'.static::EXT);
				$data=[];

				if($files)
					foreach($files as $file)
						$data[ basename($file,static::EXT) ]=$file;

				if($data)
				{
					$this->loaded[$k]=['f',$data];
					$result=TemplateLoader($n,$p,$this->default,'f',$data);
				}
			}

			elseif(is_file($path))
			{#Шаблонизатор на файле: либо класс, либо массив
				ob_start();
				$content=\Eleanor\AwareInclude($path,$this->default);
				ob_end_clean();

				if(is_array($content))
				{
					$this->loaded[$k]=['a',$content];
					$result=TemplateLoader($n,$p,$this->default,'a',$content);
				}
				elseif(is_object($content))
				{
					$this->loaded[$k]=['c',$path];
					$result=TemplateLoader($n,$p,$this->default,'c',$path);
				}
			}

			if($result!==null)
				return$result;
		}

		throw new EE("Template {$n} was not found",EE::PHP,null,BugFileLine($this::class));
	}
}

return Template::class;