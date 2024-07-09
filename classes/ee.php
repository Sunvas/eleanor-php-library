<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/

namespace Eleanor\Classes;
use Eleanor;

/** Системное исключение EleanorException */
class EE extends \Exception
{
	/** @var array Дополнительные параметры исключения */
	public array $extra=[];

	const int
		/** Размер лог файла, после которого он будет сжат */
		SIZE_TO_COMPRESS=2097152,#2 Mb

		/** Ошибка в php коде: ответственный тот, кто писал этот код (разработчик) */
		PHP=1,

		/** Ошибка в системе (например, нет доступа для к файлу): ответственный тот, кто может это исправить */
		SYSTEM=2,

		/** Ошибка в данных (например, некорректных языковой файл): ответственный тот, кто эти данные создавал */
		DATA=3,

		/** Ошибка пользователя (например, некорректно переданные данные): ответственных нет 😆 */
		USER=4;

	/** Конструктор системных исключений
	 * @param string $message Описание исключения
	 * @param int $code Код исключения
	 * @param ?\Throwable $previous Предыдущее исключение
	 * @param array $extra Дополнительные данные исключения
	 *  [string file] Имя файла
	 *  [int line] Строка с исключением
	 *  [int hint] Подсказка по исправлению
	 *  [string input] Входящие данные, которые вызвали исключение */
	public function __construct(string$message,int$code=self::USER,?\Throwable$previous=null,array$extra=[])
	{
		if(isset($previous))
			$extra+=$previous->extra ?? [ 'file'=>$previous->getFile(), 'line'=>$previous->getLine() ];

		if(isset($extra['file']))
			$this->file=$extra['file'];

		if(isset($extra['line']))
			$this->line=$extra['line'];

		unset($extra['file'],$extra['line']);
		$this->extra=$extra;

		parent::__construct($message,$code,$previous);
	}

	/** Логирование исключения. Основной наследуемый метод */
	public function Log():void
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
			md5($this->line.$this->file.$this->code.$this->message),
			function($data)
			{
				#Запись в переменные нужна для последующего удобного чтения лог-файла любыми читалками
				$data['n']??=0;#Counter
				$data['n']++;

				$data['u']=Url::$current;
				$data['d']=date('Y-m-d H:i:s');
				$data['l']=$this->line;
				$data['m']=$this->getMessage();
				$data['f']=$this->file;

				$log=<<<LOG
{$data['m']}
File: {$data['f']}[{$data['l']}]
URL: {$data['u']}
Last happened: {$data['d']}, total: {$data['n']}
LOG;

				return[$data,$log];
			}
		);
	}

	/** Преобразование в строку */
	public function __toString():string
	{
		return$this->getMessage();
	}

	/** Непосредственная запись в лог файл. Лог ошибок состоит из двух файлов: *.log и *.json Первый представляет собой
	 * текстовый файл для открытия любым удобным способом. Второй - содержит служебную информацию для группировки
	 * идентичных записей.
	 * @param string $pathfile Путь к файлу и его имя без расширения (дописывается методом)
	 * @param string $id Уникальный идентификатор записи
	 * @param callback $F Функция для генерации записей в .log файле. Первым параметром получает данные, которые вернула
	 * в прошлый раз. Должна вернуть массив из двух элементов 0 - служебные данные, которые при следующем исключении
	 * будут переданы ей первым параметром, 1 - содержимое записи в .log файл
	 * @return bool */
	protected function LogWriter(string$pathfile,string$id,callable$F):bool
	{
		$dir=dirname($pathfile);

		if(!is_dir($dir))
			Files::MkDir($dir);

		$path2log=$pathfile.'.log';
		$path2json=$pathfile.'.json';

		$is_log=is_file($path2log);
		$is_json=is_file($path2json);

		if($is_log and !is_writeable($path2log) or !$is_log and !is_writeable(dirname($path2log)))
			return trigger_error("File {$path2log} is write-protected!",E_USER_ERROR);

		//Архивация .log файла и удаление json файла (если размер превысил порог, значит его никто не читает)
		if($is_log and filesize($path2log)>static::SIZE_TO_COMPRESS)
		{
			if(static::CompressFile($path2log,substr_replace($path2log,'_'.date('Y-m-d_H-i-s'),strrpos($path2log,'.'),0)))
			{
				unlink($path2log);

				if($is_json)
					unlink($path2json);
			}

			clearstatcache();
		}

		$json=$is_json ? file_get_contents($path2json) : false;
		$json=$json ? json_decode($json,true) : [];

		$change=isset($json[$id]);
		$data=$F($change ? $json[$id]['d'] : []);

		if(!is_array($data) or !isset($data[0],$data[1]))
			return false;

		[$data,$log]=$data;

		if($change and !isset($json[$id]['o'],$json[$id]['l']))
		{
			$change=false;

			unset($json[$id]);
		}

		if($change)
		{
			$offset=$json[$id]['o'];
			$length=$json[$id]['l'];

			unset($json[$id]);
			$size=$is_log ? filesize($path2log) : 0;

			if($size<$offset+$length)
			{
				$change=false;

				foreach($json as &$v)
					if($size<$v['o']+$v['l'])
						unset($v['o'],$v['l']);
				unset($v);
			}
		}

		if($change)
		{
			$fh=fopen($path2log,'rb+');

			if(flock($fh,LOCK_EX))
				$diff=Files::FReplace($fh,$log,$offset,$length);
			else
			{
				fclose($fh);
				return false;
			}

			if(is_int($diff))
				$length+=$diff;

			foreach($json as &$v)
				if($v['o']>$offset)
					$v['o']+=$diff;
			unset($v);
		}
		else
		{
			$fh=fopen($path2log,'a');

			if(flock($fh,LOCK_EX))
			{
				$size=fstat($fh);
				$offset=$size['size'];
				$length=strlen($log);

				fwrite($fh,$log.PHP_EOL.PHP_EOL);
			}
			else
			{
				fclose($fh);

				return false;
			}
		}

		$json[$id]=['o'=>$offset,'l'=>$length,'d'=>$data];

		flock($fh,LOCK_UN);
		fclose($fh);

		file_put_contents($path2json,json_encode($json,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		return true;
	}

	/** Создание архива лог файла для экономии места.
	 * @param string $source Путь к сжимаемому файлу
	 * @param string $dest Путь с сжатому файлу (результату)
	 * @return bool */
	public static function CompressFile(string$source,string$dest):bool
	{
		if(!is_file($source) or file_exists($dest) or !is_writable(dirname($dest)))
			return false;

		$hf=fopen($source,'r');
		$r=false;

		if(function_exists('bzopen') and $hbz=bzopen($dest.'.bz2','w'))
		{
			while(!feof($hf))
				bzwrite($hbz,fread($hf,1024*16));

			bzclose($hbz);
			$r=true;
		}
		elseif(function_exists('gzopen') and $hgz=gzopen($dest.'.gz','w9'))
		{
			while(!feof($hf))
				gzwrite($hgz,fread($hf,1024*64));

			gzclose($hgz);
			$r=true;
		}

		fclose($hf);

		return$r;
	}
}

return EE::class;