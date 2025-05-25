<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use Eleanor;

/** Punycode support (cyrillic domains) */
class Punycode extends Eleanor\Basic
{
	/** Domain encoding and decoding to/from Punycode.
	 * @param string $domain Domain name
	 * @param bool $encode Punycode encoding flag
	 * @return ?string */
	static function Domain(string$domain,bool$encode=true):?string
	{
		if($encode)
		{
			if(Eleanor\CHARSET!='utf-8')
				$domain=\mb_convert_encoding($domain,'utf-8',Eleanor\CHARSET);

			$domain=\explode('.',$domain);

			foreach($domain as &$d)
				if(!\str_starts_with($d,'xn--') and \preg_match('#[^a-z.\-]#i',$d)>0)
					$d=self::Encode(\mb_strtolower($d,'utf-8'));

			$domain=join('.',$domain);
		}
		else
		{
			$domain=\explode('.',$domain);

			foreach($domain as &$d)
				$d=self::Decode(\strtolower($d));
			$domain=\join('.',$domain);

			if(Eleanor\CHARSET!='utf-8')
				$domain=\mb_convert_encoding($domain,Eleanor\CHARSET,'utf-8');
		}

		return$domain;
	}

	/** Decoding punycode to utf-8 string
	 * @param string $source
	 * @return string */
	static function Decode(string$source):string
	{
		if(!\str_starts_with($source,'xn--'))
			return$source;

		$first=700;
		$bias=72;
		$idx=0;
		$char=0x80;
		$decoded=[];

		$dpos=\strrpos($source,'-');
		if($dpos>4)#4 - length of xn-- prefix
			for($k=4;$k<$dpos;++$k)
				$decoded[]=\ord($source[$k]);

		$decol=\count($decoded);
		$encol=\strlen($source);

		for($enco_idx=$dpos ? $dpos+1 : 0;$enco_idx<$encol;++$decol)
		{
			$old_idx=$idx;
			$w=1;
			$k=36;

			while(true)
			{
				$cp=\ord($source[$enco_idx++]);
				$digit=$cp-48<10 ? $cp-22 : ($cp-65<26 ? $cp-65 : ($cp-97<26 ? $cp-97 : 36));
				$idx+=$digit*$w;
				$t=$k<=$bias ? 1 : ($k>=$bias+26 ? 26 : $k-$bias);
				if($digit<$t)
					break;
				$w*=36-$t;
				$k+=36;
			}

			$delta=\floor(($idx-$old_idx)/$first);
			$first=2;
			$delta+=\floor($delta/($decol+1));

			for($k=0;$delta>455;$k+=36)
				$delta=\floor($delta/35);

			$bias=\floor($k+36*$delta/($delta+38));
			$char+=\floor($idx/($decol+1));
			$idx%=$decol+1;

			if($decol>0)
				for($i=$decol;$i>$idx;$i--)
					$decoded[$i]=$decoded[$i-1];

			$decoded[$idx++]=$char;
		}

		$source='';

		foreach($decoded as &$v)
			if($v<128)
				$source.=\chr($v);#7bit are transferred literally
			elseif($v<(1<<11))
				$source.=\chr(192+($v>>6)).\chr(128+($v&63));#2 bytes
			elseif($v<(1<<16))
				$source.=\chr(224+($v>>12)).\chr(128+($v>>6&63)).\chr(128+($v&63));#3 bytes
			elseif($v<(1<<21))
				$source.=\chr(240+($v>>18)).\chr(128+($v>>12&63)).\chr(128+($v>>6&63)).\chr(128+($v&63));# 4 bytes
			else
				$source.=0xFFFC;

		return$source;
	}

	/** Encoding utf-8 string to punycode
	 * @param string $source
	 * @return ?string */
	static function Encode(string$source):?string
	{
		$values=$unicode=[];
		$n=\strlen($source);

		for($i=0;$i<$n;$i++)
		{
			$v=\ord($source[$i]);
			if($v<128)
				$unicode[]=$v;
			else
			{
				if(!$values)
					$cc=$v<224 ? 2 : 3;

				$values[]=$v;

				if(\count($values)==$cc)
				{
					$unicode[]=$cc==3 ? $values[0]%16*4096 + $values[1]%64*64 + $values[2]%64 : $values[0]%32*64 + $values[1]%64;
					$values=[];
				}
			}
		}

		#utf to unicode func
		unset($source,$values);

		$delta=$cc=0;
		$n=128;
		$bias=72;
		$first=700;
		$ex=$bs='';
		$ucnt=\count($unicode);

		foreach($unicode as &$v)
			if($v<128)
			{
				$bs.=\chr($v);
				$cc++;
			}

		while($cc<$ucnt)
		{
			$m=100000;

			foreach($unicode as &$v)
				if($v>=$n and $v<=$m)
					$m=$v;

			$delta+=($m-$n)*($cc+1);
			$n=$m;

			foreach($unicode as &$v)
			{
				if($v<$n)
					$delta++;
				elseif($v==$n)
				{
					$q=$delta;
					$k=36;
					while(true)
					{
						if($k<=$bias+1)
							$t=1;
						elseif($k>=$bias+26)
							$t=26;
						else
							$t=$k-$bias;

						if($q<$t)
							break;

						$ex.=self::EncodeDigit($t+($q-$t)%(36-$t));
						$q=\floor(($q-$t)/(36-$t));
						$k+=36;
					}
					$ex.=self::EncodeDigit($q);

					$delta=\floor($delta/$first);
					$delta+=\floor($delta/($cc+1));
					$first=2;
					$k=0;

					while($delta>455)
					{
						$delta=\floor($delta/35);
						$k+=36;
					}

					$bias=$k+\floor(36*$delta/($delta+38));

					$delta=0;
					$cc++;
				}
			}
			$delta++;
			$n++;
		}

		if($bs!='' and $ex=='')
			return$bs;

		if($bs!='' and $ex!='')
			return'xn--'.$bs.'-'.$ex;

		if($bs=='' and $ex!='')
			return'xn--'.$ex;

		return null;
	}

	protected static function EncodeDigit($d):string
	{
		return \chr($d+22+75*($d<26));
	}
}

return Punycode::class;