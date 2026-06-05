<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Collection of functions for working with files */
class Files extends \Eleanor\Basic
{
	/** Open the file and exclusively lock it
	 * @param string $path Path to the file
	 * @param string $mode fopen mode
	 * @param int $lock Lock mode
	 * @return resource|false */
	static function LockFile(string$path,string$mode='w',int$lock=\LOCK_EX):mixed
	{
		$fh=\fopen($path,$mode);

		if($fh===false)
			return false;

		if(!\flock($fh,$lock))
		{
			\fclose($fh);
			return false;
		}

		return $fh;
	}

	/** Copy file or directory
	 * @param string $source
	 * @param string $destination
	 * @return bool */
	static function Copy(string$source,string$destination):bool
	{
		$dest_dir=\dirname($destination);
		$mk_dest_dir=fn()=>\is_dir($dest_dir) || \mkdir($dest_dir,0755,true);
		$real_dest=fn()=>\realpath($dest_dir).\DIRECTORY_SEPARATOR.\basename($destination);

		if(\is_link($source))
			return $mk_dest_dir() && \symlink(\readlink($source),$real_dest());

		if(\is_file($source))
			return $mk_dest_dir() && \copy($source,$real_dest());

		if(!\is_dir($source) or !\is_readable($source) or !$mk_dest_dir())
			return false;

		$source=\realpath($source);
		$destination=$real_dest();

		# Prevention copying directory into itself
		if($source==$destination or \str_starts_with($destination,$source.\DIRECTORY_SEPARATOR))
			return false;

		# PHP 8.6: migrate to pipe operator
		return \array_all(
			\array_diff(\scandir($source),['.','..']),
			fn($entry)=>static::Copy($source.\DIRECTORY_SEPARATOR.$entry,$destination.\DIRECTORY_SEPARATOR.$entry)
		);
	}

	/** Remove file or directory
	 * @param string $path
	 * @return bool */
	static function Delete(string$path):bool
	{
		$path=\rtrim($path,'/\\');

		if(\is_link($path))
			return \unlink($path);

		if(\is_dir($path))
		{
			$entries=\array_diff(\scandir($path),['.','..']);
			$empty=\array_all($entries,fn($entry)=>static::Delete($path.\DIRECTORY_SEPARATOR.$entry));

			return $empty && \rmdir($path);
		}

		# If symlink is broken, file_exists can't identify it
		return !file_exists($path) || unlink($path);
	}

	/** Ideological copy of \substr_replace, but for files.
	 * @param resource $stream File resource pointer, returned by \fopen. File should be open in rb+ mode!
	 * Mode a (appending to file) is NOT supported by PHP.
	 * @param string $replace
	 * @param int $offset
	 * @param int $length
	 * @param int $buf Size of reading-writing buffer
	 * @return int|null Difference in bytes or NULL if $stream is not a resource or $length===0 and $replace==='' */
	static function FReplace($stream,string$replace,int$offset,int$length=0,int$buf=4096):?int
	{
		$len=\strlen($replace);

		if(!\is_resource($stream) or $offset<0 or $length<0 or $buf<=0 or $len==0 and $length==0)
			return null;

		$size=\fstat($stream)['size'];
		$diff=$len-$length;

		if($diff==0 and $offset<$size)
		{
			\fseek($stream,$offset);

			if(\fwrite($stream,$replace)!==\strlen($replace))
				return null;
		}
		elseif($offset>=$size)
		{
			\fseek($stream,0,SEEK_END);

			if(\fwrite($stream,$replace)!==\strlen($replace))
				return null;
		}
		else
		{
			# If replacement is longer than removed fragment
			if($diff>0)
			{
				$step=1;
				$limiter=$offset+$length;

				do
				{
					# Gradually move the contents of the file, expanding the insertion area.
					$i=\max($size-$buf*$step++,$limiter);

					\fseek($stream,$i);
					$data=\fread($stream,$buf);
					\fseek($stream,$i+$diff);

					if(\fwrite($stream,$data)!==\strlen($data))
						return null;
				}while($i>$limiter);
			}
			else
			{
				for($i=$offset+$length; $i<$size; $i+=$buf)
				{
					\fseek($stream,$i);
					$data=\fread($stream,\min($buf,$size-$i));
					\fseek($stream,$i+$diff);

					if(\fwrite($stream,$data)!==\strlen($data))
						return null;
				}

				\ftruncate($stream,$size+$diff);
			}

			if($len>0)
			{
				\fseek($stream,$offset);

				if(\fwrite($stream,$replace)!==\strlen($replace))
					return null;
			}
		}

		return $diff;
	}
}

# Not required here because class name matches filename
return Files::class;