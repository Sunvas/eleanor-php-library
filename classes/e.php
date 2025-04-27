<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Основное системное исключение */
class E extends Eleanor\Abstracts\E
{
	/** @var string Подсказка как устранить исключение */
	readonly string $hint;

	/** @var mixed Входящие данные, которые привели к ошибке */
	readonly mixed $input;

	const int
		/** Ошибка в php коде: ответственный тот, кто писал этот код (разработчик) */
		PHP=1,

		/** Ошибка в системе (например, нет доступа для к файлу): ответственный тот, кто может это исправить */
		SYSTEM=2,

		/** Ошибка в данных (например, некорректный формат файла): ответственный тот, кто эти данные создавал */
		DATA=3,

		/** Ошибка пользователя (например, некорректно переданные данные): ответственных нет 😆 */
		USER=4;

	/** @param string $message Описание исключения
	 * @param int $code Код исключения
	 * @param ?\Throwable $previous Предыдущее исключение
	 * @param ?string $file Путь к файлу, где произошло исключение
	 * @param ?int $line Номер строки, где произошло исключение
	 * @param string $hint Подсказка по исправлению ситуации
	 * @param mixed $input Входящие данные, которые привели к исключению */
	function __construct(string$message,int$code=self::USER,?\Throwable$previous=null,?string$file=null,?int$line=null,string$hint='',mixed$input=null)
	{
		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		$this->hint=$hint;
		$this->input=$input;

		parent::__construct($message,$code,$previous);
	}

	/** Для BSOD */
	function __toString():string
	{
		$intro=match($this->code){
			$this::PHP=>'PHP',
			$this::DATA=>'Data',
			$this::USER=>'User',
			$this::SYSTEM=>'System',
			default=>'Unknown'
		};

		return$intro.' exception: '.$this->message;
	}

	/** Логирование исключения */
	function Log():void
	{
		if(!Eleanor\Library::$logs_enabled)
			return;

		$type=match($this->code){
			self::PHP=>'php',
			self::DATA=>'data',
			self::USER=>'user',
			self::SYSTEM=>'system',
			default=>'unknown'
		};

		$this->LogWriter(
			Eleanor\Library::$logs.$type,
			md5($this->line.$this->file.$this->code.$this->message)
		);
	}

	/** Формирование записи в .log файле
	 * @param array $data Накопленные данные этого исключения
	 * @return string Запись для .log файла */
	protected function LogItem(array&$data):string
	{
		#Запись в переменные нужна для последующего удобного чтения лог-файла любыми читалками
		$data['n']??=0;#Counter
		$data['n']++;

		$data['u']=Uri::$current;
		$data['d']=date('Y-m-d H:i:s');
		$data['l']=$this->line;
		$data['m']=$this->getMessage();
		$data['f']=$this->file;

		$log=$this->message.PHP_EOL;

		if(isset($this->extra['input']))
			$log.='JSONed input: '.json_encode($this->extra['input'],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;

		$log.=<<<LOG
File: {$data['f']}[{$data['l']}]
URL: {$data['u']}
Last happened: {$data['d']}, total: {$data['n']}
LOG;

		return$log;
	}
}

return E::class;