<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/

namespace Eleanor\Abstracts;
use Eleanor\Classes\Files;

/** Основа системных исключений: базовые методы для их логирования */
abstract class E extends \Exception
{
	/** Размер лог файла, после которого он будет сжат */
	const int SIZE_TO_COMPRESS=2097152;#2 Mb

	/** Запись в системных логах и в BSOD */
	function __toString():string
	{
		return$this->message;
	}

	/** Логирование исключения */
	abstract function Log();

	/** Формирование записи в .log файле
	 * @param array $data Накопленные данные этого исключения
	 * @return string Запись для .log файла */
	abstract protected function LogItem(array&$data):string;

	/** Непосредственная запись в лог файл. Лог ошибок состоит из двух файлов: *.log и *.json Первый представляет собой
	 * текстовый файл для открытия любым удобным способом. Второй - содержит служебную информацию для группировки
	 * идентичных записей.
	 * @param string $path Путь к файлу и его имя без расширения (дописывается методом)
	 * @param string $id Уникальный идентификатор записи
	 * @return bool */
	protected function LogWriter(string$path,string$id):bool
	{
		$dir=dirname($path);

		if(!is_dir($dir))
			Files::MkDir($dir);

		$path2log=$path.'.log';
		$path2json=$path.'.json';

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

		$exists=isset($json[$id]);
		$data=$exists ? $json[$id]['d'] : [];
		$item=$this->LogItem($data);

		if($exists and !isset($json[$id]['o'],$json[$id]['l']))
		{
			$exists=false;

			unset($json[$id]);
		}

		if($exists)
		{
			$offset=$json[$id]['o'];
			$length=$json[$id]['l'];

			unset($json[$id]);
			$size=$is_log ? filesize($path2log) : 0;

			if($size<$offset+$length)
			{
				$exists=false;

				foreach($json as &$v)
					if($size<$v['o']+$v['l'])
						unset($v['o'],$v['l']);
				unset($v);
			}
		}

		if($exists)
		{
			$fh=fopen($path2log,'rb+');

			if(flock($fh,LOCK_EX))
				$diff=Files::FReplace($fh,$item,$offset,$length);
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
				$length=strlen($item);

				fwrite($fh,$item.PHP_EOL.PHP_EOL);
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
	static function CompressFile(string$source,string$dest):bool
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

return E::class;