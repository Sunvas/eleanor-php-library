<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Abstracts;

use Eleanor\Classes\Files,
	Eleanor\Interfaces\Loggable;

/** The base of library exceptions: basic methods for logging them */
abstract class E extends \Exception implements Loggable
{
	/** Maximum size of the file, after reaching it file will be compressed */
	const int SIZE_TO_COMPRESS=2097152;#2 Mb

	/** For BSOD */
	function __toString():string
	{
		return$this->message;
	}

	/** Logging */
	abstract function Log();

	/** Entry in a .log file
	 * @param array $data The accumulated data of this exception
	 * @return string Item for log file */
	abstract protected function LogItem(array&$data):string;

	/** Writing to a log file. Errors log consists of 2 files: *.log и *.json. The first one is a text file to be
	 * opened in any suitable way. The second one contains data for grouping identical items.
	 * @param string $path Path to the file and its name without extension (added by method)
	 * @param string $id Unique id of item
	 * @return bool */
	protected function LogWriter(string$path,string$id):bool
	{
		$dir=\dirname($path);

		if(!\is_dir($dir))
			\mkdir($dir,0744,true);

		$path2log=$path.'.log';
		$path2json=$path.'.json';

		$is_log=\is_file($path2log);
		$is_json=\is_file($path2json);

		if($is_log and !\is_writeable($path2log) or !$is_log and !\is_writeable(\dirname($path2log)))
			return \trigger_error("File {$path2log} is write-protected!",E_USER_WARNING);

		//Архивация .log файла и удаление json файла (если размер превысил порог, значит его никто не читает)
		if($is_log and \filesize($path2log)>static::SIZE_TO_COMPRESS)
		{
			if(static::CompressFile($path2log,\substr_replace($path2log,'_'.\date('Y-m-d_H-i-s'),\strrpos($path2log,'.'),0)))
			{
				\unlink($path2log);

				if($is_json)
					\unlink($path2json);
			}

			\clearstatcache();
		}

		$json=$is_json ? \file_get_contents($path2json) : false;
		$json=$json ? \json_decode($json,true) : [];

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
			$size=$is_log ? \filesize($path2log) : 0;

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
			$fh=\fopen($path2log,'rb+');

			if(\flock($fh,LOCK_EX))
				$diff=Files::FReplace($fh,$item,$offset,$length);
			else
			{
				\fclose($fh);
				return false;
			}

			if(\is_int($diff))
				$length+=$diff;

			foreach($json as &$v)
				if($v['o']>$offset)
					$v['o']+=$diff;
			unset($v);
		}
		else
		{
			$fh=\fopen($path2log,'a');

			if(\flock($fh,LOCK_EX))
			{
				$size=\fstat($fh);
				$offset=$size['size'];
				$length=\strlen($item);

				\fwrite($fh,$item.PHP_EOL.PHP_EOL);
			}
			else
			{
				\fclose($fh);

				return false;
			}
		}

		$json[$id]=['o'=>$offset,'l'=>$length,'d'=>$data];

		\flock($fh,LOCK_UN);
		\fclose($fh);

		\file_put_contents($path2json,\json_encode($json,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		return true;
	}

	/** Compressing log files for saving disk space
	 * @param string $source Path to source file
	 * @param string $dest Path to destination file
	 * @return bool */
	static function CompressFile(string$source,string$dest):bool
	{
		if(!\is_file($source) or \file_exists($dest) or !\is_writable(\dirname($dest)))
			return false;

		$hf=\fopen($source,'r');
		$r=false;

		if(\function_exists('bzopen') and $hbz=\bzopen($dest.'.bz2','w'))
		{
			while(!\feof($hf))
				\bzwrite($hbz,\fread($hf,1024*16));

			\bzclose($hbz);
			$r=true;
		}
		elseif(\function_exists('gzopen') and $hgz=\gzopen($dest.'.gz','w9'))
		{
			while(!\feof($hf))
				\gzwrite($hgz,\fread($hf,1024*64));

			\gzclose($hgz);
			$r=true;
		}

		\fclose($hf);

		return$r;
	}
}

#Not necessary here, since class name equals filename
return E::class;