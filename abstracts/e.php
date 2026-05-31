<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Abstracts;

use Eleanor\Classes\Files,
	Eleanor\Interfaces\Loggable;

/** Base class for library exceptions with built-in logging support. */
abstract class E extends \Exception implements Loggable
{
	const int
		/** Maximum log file size before automatic compression */
		SIZE_TO_COMPRESS=2097152,# 2 MB

		/** Chunk size used during log file compression */
		CHUNK_SIZE=1024*128;# 128 KB

	/** String representation */
	function __toString():string
	{
		return $this->message;
	}

	/** Write exception information to logs */
	abstract function Log():void;

	/** Build textual log entry for this exception.
	 * @param array $data Accumulated exception data
	 * @return string Formatted log entry */
	abstract protected function LogItem(array&$data):string;

	/** Write exception data to log files. Logging uses three files:
	 *     - *.log Human-readable text log
	 *     - *.json Structured data used for grouping identical entries
	 *     - *.lock File lock for concurrent access
	 * @param string $path Log file path without extension
	 * @param string $id Unique log entry identifier
	 * @return bool */
	protected function LogWriter(string$path,string$id):bool
	{
		$dir=\dirname($path);

		if(!\is_dir($dir) and !\mkdir($dir,0755,true))
			return !\trigger_error("Directory $dir is write-protected!",\E_USER_WARNING);

		$path2log=$path.'.log';
		$path2json=$path.'.json';
		$path2lock=$path.'.lock';

		$flh=Files::LockFile($path2lock);

		if($flh===false)
			return false;

		$is_log=\is_file($path2log);
		$is_json=$is_log && \is_file($path2json);

		if($is_log and !\is_writeable($path2log) or !$is_log and !\is_writeable(\dirname($path2log)))
		{
			\fclose($flh);
			return !\trigger_error("File $path2log is write-protected!",\E_USER_WARNING);
		}

		# Compressing oversized .log files and removing related .json metadata. Oversized logs are assumed to be inactive.
		if($is_log and \filesize($path2log)>static::SIZE_TO_COMPRESS)
		{
			$archive=\substr_replace($path2log,\date('_Y-m-d_H-i-s'),\strrpos($path2log,'.'),0);

			if(static::CompressFile($path2log,$archive))
			{
				\unlink($path2log);

				if($is_json)
					\unlink($path2json);

				$is_log=false;
				$is_json=false;
			}
		}

		$json=$is_json ? \file_get_contents($path2json) : '';
		$corrupted=$json===false;
		$json=$json ? (array)\json_decode($json,true) : [];

		if($corrupted or $is_json and \json_last_error()!==\JSON_ERROR_NONE)
		{
			$salt=\bin2hex(\random_bytes(2));
			\rename($path2json,$path.".$salt.corrupt");
		}

		$exists=isset($json[$id][0],$json[$id][1],$json[$id][2]);
		$size=$is_log ? \filesize($path2log) : 0;

		# Check if the entry fits into the existing log file
		if($exists)
		{
			[$offset,$length]=$json[$id];

			if($offset+$length>$size)
			{
				$json=[];
				$exists=false;
			}
		}

		$data=$exists ? $json[$id][2] : [];
		$item=$this->LogItem($data);
		$fh=Files::LockFile($path2log,$exists ? 'r+' : 'a');

		if($fh===false)
		{
			\fclose($flh);

			return false;
		}

		if($exists)
		{
			$diff=Files::FReplace($fh,$item,$offset,$length);
			$success=\is_int($diff);

			if($success)
			{
				$length+=$diff;

				# Moving offsets of all entries after the affected one
				foreach($json as &$v)
					if(isset($v[0]) and $v[0]>$offset)
						$v[0]+=$diff;

				unset($v);
			}
		}
		else
		{
			$offset=$size;
			$length=\strlen($item);
			$item.=\PHP_EOL.\PHP_EOL;

			$bytes=\fwrite($fh,$item);
			$success=$bytes===\strlen($item);
		}

		if($success and \fflush($fh))
		{
			$json[$id]=[$offset,$length,$data];
			$json=\json_encode($json,\JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

			$r=\json_last_error()===\JSON_ERROR_NONE and \file_put_contents($path2json,$json,\LOCK_EX)===\strlen($json);
		}
		else
			$r=false;

		\fclose($fh);
		\fclose($flh);

		return $r;
	}

	/** Compress log file to reduce disk usage.
	 * @param string $source Source file path
	 * @param string $dest Destination file path
	 * @return bool */
	static function CompressFile(string$source,string$dest):bool
	{
		if(!\is_file($source) or !\is_writable(\dirname($dest)))
			return false;

		$fh=Files::LockFile($source,'r',\LOCK_SH | \LOCK_NB);

		if($fh===false)
			return false;

		if(\function_exists('bzopen') and !\is_file($dest.'.bz2') and $hbz=\bzopen($dest.'.bz2','w'))
		{
			while(!\feof($fh))
				\bzwrite($hbz,\fread($fh,static::CHUNK_SIZE));

			\bzflush($hbz);
			$r=\bzclose($hbz);
		}
		elseif(\function_exists('gzopen') and !\is_file($dest.'.gz') and $hgz=\gzopen($dest.'.gz','w9'))
		{
			while(!\feof($fh))
				\gzwrite($hgz,\fread($fh,static::CHUNK_SIZE));

			$r=\gzclose($hgz);
		}
		else
			$r=false;

		\fclose($fh);

		return $r;
	}
}

# Not required here because class name matches filename
return E::class;