<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor;
use Eleanor\Classes\Output, Eleanor\Classes\EE;

/** Кодировка файлов */
const CHARSET = 'UTF-8';

mb_internal_encoding(CHARSET);

/** Путь к сайту, относительно домена */
defined('Eleanor\SITEDIR')||define('Eleanor\SITEDIR',rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'),'/\\').'/');

/** Протокол доступа */
defined('Eleanor\PROTOCOL')||define('Eleanor\PROTOCOL',($_SERVER['HTTPS'] ?? '')=='on' ? 'https://' : 'http://');

/** Начальная временная точка используется для уменьшения используемых timestamp */
defined('Eleanor\BASE_TIME')||define('Eleanor\BASE_TIME',mktime(0,0,0,1,1,2024));

/** Windows detector */
define('Eleanor\W',stripos(PHP_OS,'win')===0);

/** Получение пути к файлу и номера строки с ошибкой
 * @param string $class - в нужном классе вызвать эту функцию с параметром static::class
 * @return array ['file'=>,'line'=>N] */
function BugFileLine(string$class):array
{
	$db=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$found=false;

	#2 это пропуск текущей функции и класс, который её вызвал
	foreach(array_slice($db,1) as $item)
	{
		if(!isset($item['class']))
			return$item;

		//Если в db встречается BaseClass, значит искомый элемент - следующий (вызов parent::__get)
		if($item['class']==BaseClass::class)
			$found=true;
		elseif($found or $item['class']!=$class)
			return$item;
	}

	return[];
}

/** Функция безопасного подключения файла: в случае ParseError-a, будет создан лог
 * @param string $file Полный путь к файлу, который нужно проинклудить
 * @param array $vars Переменные для файла в его области видимости
 * @throws EE
 * @return mixed */
function AwareInclude(string$file,array$vars=[]):mixed
{
	if(!is_file($file))
		throw new EE('Missing file '.(str_starts_with($file,SITEDIR) ? substr($file,strlen(SITEDIR)) : $file),EE::SYSTEM);

	if($vars)
		extract($vars,EXTR_PREFIX_INVALID|EXTR_OVERWRITE,'var');

	ob_start();

	try
	{
		$r=include func_get_arg(0);
		ob_end_flush();
	}
	catch(\Throwable$E)
	{
		ob_end_clean();
		throw$E;
	}

	return$r===null ? true : $r;
}

/** "Тихое" выполнение кода, когда отключается показ ошибок
 * @param callable $Func
 * @return mixed */
function QuietExecution(callable$Func):mixed
{
	set_error_handler(fn()=>null);

	try
	{
		$ret=call_user_func($Func);
	}
	catch(\Throwable)
	{
		$ret=null;
	}

	restore_error_handler();
	return$ret;
}

/** Системный BSOD
 * @param string $error Текст ошибки
 * @param int|string $code Код ошибки, по которому ошибку легко идентифицировать программно
 * @param ?string $file Путь к файлу, в котором возникла ошибка
 * @param ?int $line Номер строки на которой возникла ошибка
 * @param ?string $hint Подсказка для исправления
 * @param ?array $payload данные, которые привели к сбою */
function BSOD(string$error,int|string$code,?string$file,?int$line,?string$hint=null,?array$payload=null):never
{
	$Tpl=new Classes\Template(Library::$bsod);
	$type=match(Library::$bsodtype){
		Output::HTML=>'html',
		Output::JSON=>'json',
		default=>'text'
	};
	$out=(string)$Tpl->{$type}($error,$code,$file,$line,$hint,$payload);

	ob_clean();
	Output::SendHeaders(Library::$bsodtype,503);

	die($out);
}

/** Базовый класс, от которого рекомендуется наследовать все остальные: содержит необходимые заглушки, облегчающие поиск
 * багов и их исправление */
abstract class BaseClass
{
	/** Обработка ошибочных вызовов несуществующих статических методов.
	 * Наличие этого метода может показаться странным: ведь если вызвать несуществующий статический метод, будет
	 * сгенерирован Fatal error, который можно отловить и залогировать. Но удобство метода проявляется в классе
	 * наследнике с методом __callStatic который не может выполнить все вызываемые методы.
	 * @param string $n Название несуществующего метода
	 * @param array $p Массив входящих параметров вызываемого метода
	 * @throws EE
	 * @return mixed */
	public static function __callStatic(string$n,array$p):mixed
	{
		$E=new EE(
			'Called undefined method '.static::class.' :: '.$n,
			EE::PHP,null,BugFileLine(static::class)
		);
		$E->Log();

		throw $E;
	}

	/** Обработка ошибочных вызовов несуществующих методов.
	 * Наличие этого метода может показаться странным: ведь если вызвать несуществующий метод объекта, будет
	 * сгенерирован Fatal error, который можно отловить и залогировать. Но удобство метода проявляется в классе
	 * наследнике с методом __call который не может выполнить все вызываемые методы.
	 * @param string $n Название несуществующего метода
	 * @param array $p Массив входящих параметров вызываемого метода
	 * @throws EE
	 * @return mixed */
	public function __call(string$n,array$p):mixed
	{
		if(property_exists($this,$n) and is_object($this->$n) and method_exists($this->$n,'__invoke'))
			return call_user_func_array([ $this->$n,'__invoke' ],$p);

		$E=new EE(
			'Called undefined method '.$this::class.' -› '.$n,
			EE::PHP,null,BugFileLine($this::class)
		);
		$E->Log();

		throw $E;
	}

	/** Обработка получения несуществующих свойств
	 * Наличие этого метода может показаться странным: поскольку, при попытке получить неопределенное свойство
	 * генерируется Notice, который можно отловить и залогировать. Но удобство метода проявляется в классе наследнике с
	 * методом __get, который может вернуть не все запрашиваемые свойства.
	 * @param string $n Имя запрашиваемого свойства
	 * @throws EE
	 * @return mixed */
	public function __get(string$n):mixed
	{
		$E=new EE(
			'Trying to get value from the unknown variable '.$this::class.' -› '.$n,	EE::PHP,null,
			BugFileLine($this::class)
		);
		$E->Log();

		throw $E;
	}
}

/** Assign On Demand Создание объектов по запросу.
 * Довольно часто бывают ситуации, когда объект нужно создать без явной на то необходимости. Как правило, в CMS объект
 * \MySQLi централизовано создаётся на раннем этапе (из-за особенностей хранения параметров для подключения к БД), а
 * потом подгружаемые компоненты используют его по мере необходимости. Но на простых сайтов, далеко не на каждой странице
 * происходит взаимодействие с БД, поэтому нет смысла тратить ресурсы сервера на шаред хостинге. Этот класс позволяет
 * определить конструктор объекта в момент загрузки конфигурации, а в случае необходимости создат объект. Пример использования:
 * class A
 * {
 *    public function Say(){ echo 'Hi'; }
 * }
 *
 * class B
 * {
 *    public static null|Assign|A $o;
 * }
 *
 * Assign::For(B::$o,fn()=>new A);
 *
 * echo get_class(B::$o); //Assign
 * B::$o->Say(); //Hi
 * echo get_class(B::$o); //A
 */
class Assign extends BaseClass
{
	/** @param ?object &$link Ссылка, куда будет записан объект при создании
	 * @param \Closure $Creator Функция, которая должна вернуть объект */
	public function __construct(protected ?object &$link,protected \Closure$Creator){}

	/** Непосредственное создание объекта */
	protected function Create():void
	{
		$this->link=call_user_func($this->Creator);
	}

	/** Синтаксический сахар для связывания переменной с объектов, указав её один раз */
	public static function For(?object &$link,\Closure$Creator):void
	{
		$link=new static($link,$Creator);
	}

	public function __get(string$n):mixed
	{
		$this->Create();
		return$this->link->$n;
	}

	public function __call(string$n,array$p):mixed
	{
		$this->Create();
		return call_user_func_array([$this->link,$n],$p);
	}
}

/** Основной класс фреймворка Eleanor */
#[\AllowDynamicProperties]
class Library extends BaseClass
{
	public static
		/** @var ?callable $old_error_handler Предыдущий обработчик ошибок */
		$old_error_handler,

		/** @var ?callable $old_exception_handler Предыдущий перехватчик исключений */
		$old_exception_handler,

		/** @var callable $log_filter Фильтр выборочного логирования (если отключено $log_all_errors или $log_all_exceptions) */
		$log_filter;

	public static bool
		/** @var bool $log_all_errors Флаг включения логирования всех ошибок */
		$log_all_errors=true,

		/** @var bool $log_all_exceptions Флаг включения логирования всех исключений */
		$log_all_exceptions=true,

		/** @var bool $logs_enabled Флаг включения режима логирования */
		$logs_enabled=true;

	public static string
		/** @var string $logs Путь к каталогу, в который будут помещаться логи */
		$logs,

		/** @var string $bsod Путь к файлу экрана смерти */
		$bsod=__DIR__.'/bsod.php',

		/** @var string $bsodtype Тип экрана смерти */
		$bsodtype='text/html';

	/** @var array $creators Хранилище конструкторов будущих объектов */
	protected array $creators=[];

	/** Определение конструктора для создания будущих shared объектов внутри объекта-хранилища Eleanor
	 * @param string $n Название класса
	 * @param array $p Массив входящих параметров конструктора или \Closure
	 * @return mixed */
	public function __call(string$n,array$p):mixed
	{
		$this->creators[$n]=$p;
		return$this;
	}

	/** Получение объектов */
	public function __get(string$n):mixed
	{
		return$this->$n=$this($n);
	}

	/** Разовое создание объекта по заранее определённому конструктору
	 * @param string $n Имя класса
	 * @throws EE */
	public function __invoke(string$n):mixed
	{
		$creator=[];

		if(isset($this->creators[$n]))
		{
			$creator=$this->creators[$n];

			//Создание объектов через \Closure
			if(count($creator)==1 and $creator[0] instanceof \Closure)
				return call_user_func($creator[0]);
		}

		$lcc=strtolower($n);#LowerCase class
		$path=__DIR__."/classes/{$lcc}.php";

		if(is_file($path))
		{
			$class=require$path;

			if(class_exists($class,false))
				return new $class($creator);
		}

		$E=new EE(
			'Trying to construct object from unknown class '.$n,	EE::PHP,null,
			BugFileLine(static::class)
		);
		$E->Log();

		throw $E;
	}
}

#По умолчанию все логи хранятся в папке ./log от корня сайта. Доступ к этой папке лучше закрыть.
Library::$logs=$_SERVER['DOCUMENT_ROOT'].SITEDIR.'logs/';

#Фильтр на вход получает путь к файлу с ошибкой/исключением и разрешает или запрещает логирование.
Library::$log_filter=fn($f)=>str_starts_with($f,__DIR__.DIRECTORY_SEPARATOR);

Library::$old_error_handler=set_error_handler(function($c,$error,$f,$l,$context=null){
	#Возможно, ошибку должен был залогировать предыдущий скрипт
	if(!Library::$log_all_errors and !call_user_func(Library::$log_filter,$f,$c))
	{
		if(Library::$old_error_handler)
			call_user_func(Library::$old_error_handler,$c,$error,$f,$l,$context);

		return;
	}

	if($c&~E_STRICT and Library::$logs_enabled and class_exists('\Eleanor\Classes\EE'))#
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

		$E=new EE(
			$type.$error,
			EE::PHP,null,
			[ 'file'=>$f, 'line'=>$l, 'input'=>$context ]
		);

		$E->Log();

		//Отображаем ошибки только если они связаны с парсингом php кода или пользовательскими trigger_error
		if($c & (E_USER_ERROR | E_PARSE))
			BSOD($type.$error,$c,$f,$l,null,$context);
	}
},E_ALL);

Library::$old_exception_handler=set_exception_handler(function(\Throwable$E){
	/** @var EE $E */
	$m=$E->getMessage();
	$f=$E->getFile();
	$l=$E->getLine();
	$c=$E->getCode();

	$is_ee=$E instanceof EE;

	if($is_ee)
		$E->Log();
	elseif(Library::$log_all_exceptions or call_user_func(Library::$log_filter,$f,$c)
		#Заплатка на случай отключенного автолоадера
		and (class_exists('\Eleanor\Classes\EE',false) or include(__DIR__.'/classes/ee.php')))
	{
		$c=EE::PHP;
		$E2=new EE($m,EE::PHP,$E);
		$E2->Log();
	}

	$type=match($c){
		EE::PHP=>'PHP',
		EE::DATA=>'Data',
		EE::USER=>'User',
		EE::SYSTEM=>'System',
		default=>'Unknown'
	};

	BSOD($type.' exception: '.$m,$c,$f,$l,$is_ee ? $E->extra['hint'] ?? null : null,$is_ee ? $E->extra['input'] ?? null : null);
});

#Реализация своей автозагрузки: загружаем только, что напрямую относится к Eleanor PHP Library
spl_autoload_register(function(string$c){
	if(!str_starts_with($c,__NAMESPACE__.'\\'))
		return;

	#LowerCase class
	$lcc=substr($c,strlen(__NAMESPACE__));
	$lcc=strtolower($lcc);

	$path=__DIR__.DIRECTORY_SEPARATOR.strtr($lcc,'\\',DIRECTORY_SEPARATOR).'.php';
	$exists=is_file($path);

	if($exists)
	{
		$r=require$path;

		//Попытка сделать класс "своим" (доступным из \Eleanor)
		if(class_exists($c,false) or (is_string($r) and class_exists($r,false) and class_alias($r,$c,false)))
			return;
	}

	if(!$exists or !class_exists($c,false) and !interface_exists($c,false) and !trait_exists($c,false) and !enum_exists($c,false))
	{
		$what=match(strstr($lcc, '\\', true)){
			'traits'=>'Trait',
			'enums'=>'Enum',
			'l10n'=>'L10n',
			'interfaces'=>'Interface',
			'abstracts'=>'Abstract class',
			default=>'Class'
		};

		if(class_exists('\Eleanor\Classes\EE',false) or include(__DIR__.'/classes/ee.php'))
		{
			$bt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$extra=$bt[2] ?? [];

			throw new EE($what.' not found: '.$c,EE::PHP,null,$extra);
		}
	}
});

#Поддержка IDN
define('Eleanor\PUNYCODE',filter_var($_SERVER['HTTP_HOST'] ?? '',FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME) ? $_SERVER['HTTP_HOST'] : '');
define('Eleanor\DOMAIN',str_starts_with(PUNYCODE,'xn--') ? Classes\Punycode::Domain(PUNYCODE,false) : PUNYCODE);