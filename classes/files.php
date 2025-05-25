<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use Eleanor;

/** Collection of functions for working with files */
class Files extends Eleanor\Basic
{
	/** Making copy of file/directory
	 * @param string $source
	 * @param string $destination
	 * @param ?string $orig_dest Ignore. For internal use
	 * @return bool */
	static function Copy(string $source, string $destination, ?string $orig_dest=null):bool
	{
		$orig_dest??=$destination;

		#Preving coping itself into itself
		if($source=='' or !\file_exists($source) or \str_starts_with($source,$orig_dest))
			return false;

		$source=\realpath($source);

		$dest_dir=\dirname($destination);
		static::MkDir($dest_dir);
		$destination=\realpath($dest_dir).DIRECTORY_SEPARATOR.\basename($destination);

		if(\is_link($source) or Eleanor\W and \readlink($source)!=$source)
			return \symlink(\readlink($source),$destination);

		if(\is_file($source))
			return \copy($source,$destination);

		$files=\array_diff(\scandir($source),['.','..']);

		foreach($files as $entry)
			static::Copy($source.DIRECTORY_SEPARATOR.$entry,$destination.DIRECTORY_SEPARATOR.$entry,$orig_dest);

		return true;
	}

	/** Recursive folder creation
	 * @param string $path
	 * @return bool */
	static function MkDir(string$path):bool
	{
		if($path!='' and !\is_dir($path))
		{
			static::MkDir(d\irname($path));
			return \mkdir($path);
		}

		return true;
	}

	/** Removing of file/folder
	 * @param string $path
	 * @return bool */
	static function Delete(string$path):bool
	{
		$path=\rtrim($path,'/\\');

		if(\is_dir($path))
		{
			$entries=\array_diff(\scandir($path),['.','..']);
			$empty=array_all($entries,fn($entry)=>static::Delete($path.DIRECTORY_SEPARATOR.$entry));

			return $empty && rmdir($path);
		}

		#Is symlinks is broken, file_exists can't identify it
		return (is_link($path) || file_exists($path)) && unlink($path);
	}

	/** Ideological copy of \substr_replace, but for files.
	 * @param resource $stream File resource pointer, returned by \fopen. File should be open in rb+ mode!
	 * Mode a (appending to file) is NOT supported by PHP.
	 * @param string $replace
	 * @param int $offset
	 * @param int $length
	 * @param int $buf Size of reading-writing buffer
	 * @return int|null Difference in bytes or NULL if $stream is not a resource or $length===0 and $string==='' */
	static function FReplace($stream,string$replace,int$offset,int$length=0,int$buf=4096):?int
	{
		$len=strlen($replace);

		if(!is_resource($stream) or $len==0 and $length==0)
			return null;

		$size=fstat($stream)['size'];
		$diff=$len-$length;

		if($diff==0 and $offset<$size)
		{
			fseek($stream,$offset);
			fwrite($stream,$replace);
		}
		elseif($offset>=$size)
		{
			fseek($stream,0,SEEK_END);
			fwrite($stream,$replace);
		}
		else
		{
			//If we're trying to write more than written
			if($diff>0)
			{
				$step=1;
				$limiter=$offset+$length;

				do
				{
					#Понемногу перемещаем содержимое файла, расширяя зону вставки
					$i=max($size-$buf*$step++,$limiter);

					fseek($stream,$i);
					$data=fread($stream,$buf);

					fseek($stream,$i+$diff);
					fwrite($stream,$data);
				}while($i>$limiter);
			}
			else
			{
				for($i=$offset+$length; $i<$size; $i+=$buf)
				{
					fseek($stream,$i);

					$data=fread($stream,min($buf,$size-$i));
					fseek($stream,$i+$diff);
					fwrite($stream,$data);
				}

				ftruncate($stream,$size+$diff);
			}

			if($len>0)
			{
				fseek($stream,$offset);
				fwrite($stream,$replace);
			}
		}

		return$diff;
	}
}

return Files::class;