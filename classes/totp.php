<?php
# Eleanor PHP Library © 2026 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Lightweight TOTP helper based on HMAC-SHA256.
 * The recommended size of a secret is 32 bytes (256 bits), can be generated via random_bytes(32). */
class TOTP extends \Eleanor\Basic
{
	const string
		ALGO='sha256',
		ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/** Generate current TOTP code.
	 * @param string $secret Secret in raw format
	 * @param ?int $time Unix timestamp. Current time is used when null
	 * @param int $digits Number of digits in generated code
	 * @param int $step Time step in seconds
	 * @return string */
	static function Code(string$secret,?int$time=null,int$digits=6,int$step=30):string
	{
		$time??=\time();
		$counter=\intdiv($time,$step);

		# 64-bit counter in network byte order
		$binary=\pack('N*',0).\pack('N*',$counter);

		$hash=\hash_hmac(static::ALGO,$binary,$secret,true);
		$offset=\ord($hash[-1]) & 0x0F;

		$value=(
			((\ord($hash[$offset]) & 0x7F) << 24) |
			((\ord($hash[$offset+1]) & 0xFF) << 16) |
			((\ord($hash[$offset+2]) & 0xFF) << 8) |
			(\ord($hash[$offset+3]) & 0xFF)
		);

		return \str_pad((string)($value % (10 ** $digits)),$digits,'0',\STR_PAD_LEFT);
	}

	/** Verify TOTP code.
	 * @param string $secret Secret in raw format
	 * @param string $code Code entered by user
	 * @param int $digits Number of digits in generated code
	 * @param int $step Time step in seconds
	 * @param int $window Number of previous/next time steps accepted
	 * @return bool */
	static function Verify(string$secret,string$code,int$digits=6,int$step=30,int$window=1):bool
	{
		$time=\time();

		for($i=-$window;$i<=$window;$i++)
			if(\hash_equals(static::Code($secret,$time+$i*$step,$digits,$step),$code))
				return true;

		return false;
	}

	/** Generate otpauth URI for QR code.
	 * @param string $issuer Site or application name
	 * @param string $account User login or account label
	 * @param string $secret Secret in raw format should be generated via random_bytes(32)
	 * @param int $digits Number of digits in generated code
	 * @param int $step Time step in seconds
	 * @return string */
	static function URI(string$issuer,string$account,string$secret,int$digits=6,int$step=30):string
	{
		$acc=\rawurlencode($issuer.':'.$account);
		$issuer=\rawurlencode($issuer);
		$algo=\strtoupper(static::ALGO);
		$secret=static::Base32Encode($secret);

		return "otpauth://totp/$acc?secret=$secret&issuer=$issuer&algorithm=$algo&digits=$digits&period=".$step;
	}

	protected static function Base32Encode(string$data):string
	{
		$bits=$result='';

		foreach(\unpack('C*',$data) as $byte)
			$bits.=\str_pad(\decbin($byte),8,'0',\STR_PAD_LEFT);

		foreach(\str_split($bits,5) as $chunk)
		{
			if(\strlen($chunk)<5)
				$chunk=\str_pad($chunk,5,'0');

			$result.=static::ALPHABET[\bindec($chunk)];
		}

		return $result;
	}
}

# Not required here because class name matches filename
return TOTP::class;