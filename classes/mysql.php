<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

use function Eleanor\BugFileLine;

/** Database helper based on MySQLi driver */
class MySQL extends \Eleanor\Basic
{
	/** @var \MySQLi MySQLi driver instance */
	readonly \MySQLi $M;

	/** @var bool Whether the MySQLi instance is owned by this wrapper */
	readonly bool $owned;

	/** Connect to MySQL server
	 * @see https://www.php.net/manual/en/mysqli.construct.php
	 * @param null|string|\MySQLi $host MySQLi object, IP address, or hostname.
	 * @param ?string $user Username
	 * @param ?string $pass Password
	 * @param ?string $db Default database name
	 * @param string $charset Character set
	 * @param bool $sync Whether to synchronize MySQL session time zone with PHP time zone
	 * @param ?int $port Port number
	 * @param ?string $socket Socket path
	 * @throws EM */
	function __construct(null|string|\MySQLi$host=null,?string$user=null,#[\SensitiveParameter]?string$pass=null,?string$db=null,string$charset='utf8mb4',bool$sync=true,?int$port=null,?string$socket=null)
	{
		if($host instanceof \MySQLi)
		{
			$this->M=$host;
			$this->owned=false;
			return;
		}

		$M=\Eleanor\QuietExecution(fn()=>new \MySQLi($host,$user,$pass,$db,$port,$socket));

		if($M?->connect_errno or !$M?->server_version)
			throw new EM($M?->connect_error ?? 'Connect error',EM::CONNECT,...BugFileLine(),errno:$M?->connect_errno ?? 0,params:\compact('host','user','db','port','socket'));

		$M->autocommit(true);

		if(!$M->set_charset($charset))
			throw new EM($M->error,EM::OTHER,...BugFileLine(),errno:$M->errno,query:$charset);

		$this->M=$M;
		$this->owned=true;

		if($sync)
			$this->SyncTimeZone();
	}

	function __destruct()
	{
		if($this->owned)
			$this->M->close();
	}

	/** Proxy for accessing MySQLi driver properties
	 * @param string $n Property name
	 * @throws E
	 * @return mixed */
	function __get(string$n):mixed
	{
		if(\property_exists($this->M,$n))
			return $this->M->$n;

		return parent::__get($n);
	}

	/** Proxy for calling MySQLi driver methods
	 * @param string $n Method name
	 * @param array $a Arguments
	 * @throws \BadMethodCallException
	 * @return mixed */
	function __call(string$n,array$a):mixed
	{
		if(\method_exists($this->M,$n))
			return \call_user_func_array([$this->M,$n],$a);

		return parent::__call($n,$a);
	}

	/** Synchronize MySQL session time zone with PHP time zone. This affects only TIMESTAMP fields.
	 * @throws EM */
	function SyncTimeZone():void
	{
		$o=\date_offset_get(\date_create());
		$s=$o<0 ? '-' : '+';
		$o=\abs($o);
		$s.=\sprintf('%02d:%02d',\intdiv($o,3600),($o % 3600) / 60);

		$this->Query("SET TIME_ZONE='$s'");
	}

	/** Start transaction */
	function Transaction():void
	{
		$this->M->autocommit(false);
	}

	/** Commit transaction */
	function Commit():void
	{
		$this->M->commit();
		$this->M->autocommit(true);
	}

	/** Roll back transaction */
	function Rollback():void
	{
		$this->M->rollback();
		$this->M->autocommit(true);
	}

	/** Perform SQL query
	 * @param string $q SQL query
	 * @param int $mode Result mode
	 * @throws EM
	 * @return true|\mysqli_result */
	function Query(string$q,int$mode=MYSQLI_STORE_RESULT):true|\mysqli_result
	{
		try{
			$R=$this->M->query($q,$mode);
		}catch(\mysqli_sql_exception$E){
			throw new EM($E->getMessage(),EM::QUERY,$E,...BugFileLine($this),errno:$E->getCode(),query:$q);
		}

		if($R===false)
			throw new EM($q ? $this->M->error : 'Empty query',EM::QUERY,...BugFileLine($this),errno:$this->M->errno,query:$q);

		return $R;
	}


	const string IGNORE=' IGNORE';

	/** Execute INSERT query
	 * @param string $t Table name
	 * @param array $d Data. Formats are described in Insert4 method
	 * @param bool|string|array $ignore_odku IGNORE flag or contents for ON DUPLICATE KEY UPDATE structure
	 * @param array $params Prepared statement parameters for ODKU
	 * @return int Insert ID
	 * @throws EM */
	function Insert(string$t,array$d,bool|string|array$ignore_odku=true,array$params=[]):int
	{
		if(\is_bool($ignore_odku))
		{
			$ext=$ignore_odku ? self::IGNORE : '';
			$odku='';
		}
		else
		{
			$ext='';
			$odku=' ON DUPLICATE KEY UPDATE ';

			if(\is_string($ignore_odku))
				$odku.=$ignore_odku;
			else
			{
				foreach($ignore_odku as $k=>&$v)
					$v="`$k`=".$this->Escape($v);

				$odku.=\join(',',$ignore_odku);
			}
		}

		[$insert,$params2]=$this::Insert4($d);

		if(\array_unshift($params,...$params2)>0)
			return $this->Execute("INSERT$ext INTO `$t`".$insert.$odku,$params,false)->insert_id;

		$this->Query("INSERT$ext INTO `$t`".$insert.$odku);
		return $this->M->insert_id;
	}

	/** Execute REPLACE query
	 * @param string $t Table name
	 * @param array $d Data. Formats are described in Insert4 method
	 * @param bool $ignore IGNORE flag
	 * @return int Affected rows
	 * @throws EM */
	function Replace(string$t,array$d,bool$ignore=false):int
	{
		$ext=$ignore ? self::IGNORE : '';

		[$insert,$params]=$this::Insert4($d);

		if(!$params)
		{
			$this->Query("REPLACE$ext INTO `$t` ".$insert);

			return $this->M->affected_rows;
		}

		return $this->Execute("REPLACE$ext INTO `$t` ".$insert,$params,false)->affected_rows;
	}

	/** Generate INSERT clause for prepared statements
	 * @param array $d Must not be empty. Data in one of the formats:
	 *  [ 'field1'=>'value1', 'field2'=>'value2' ] or
	 *  [ 'field1'=>[ 'values11', 'value12' ], 'field2'=>[ 'value21', 'value22' ] ] or
	 *  [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
	 * @return array [string INSERT section,array $params] */
	static function Insert4(array$d):array
	{
		$params=[];

		if(\array_is_list($d))
		{
			$fields=\array_first($d) |> \array_keys(...);
			$values=[];

			foreach($d as $input)
			{
				$group=[];

				foreach($fields as $index=>$field)
				{
					$v=$input[$field] ?? $input[$index] ?? null;
					$bypass=\is_string($v) ? null : static::Bypass($v);

					if($bypass===null)
					{
						$params[]=$v;
						$v='?';
					}
					else
						$v=$bypass;

					$group[]=$v;
				}

				$values[]='('.\join(',',$group).')';
			}

			$fields='(`'.\join('`,`',$fields).'`)';
		}
		else
		{
			$fields='(`'.\join('`,`',\array_keys($d)).'`)';
			$map=function($v)use(&$params){
				$bypass=\is_string($v) ? null : static::Bypass($v);

				if($bypass!==null)
					return $bypass;

				$params[]=$v;
				return '?';
			};

			$values=\array_values($d);
			$values=\array_map(fn($item)=>(array)$item,$values);#PHP 8.5 settype array
			$values=isset($values[1]) ? \array_map(null,...$values) : [...$values];//Из строк в столбцы
			$values=\array_map(fn($item)=>'('.\join(',',\array_map($map,$item)).')',$values);
		}

		return[$fields.' VALUES '.\join(',',$values),$params];
	}

	/** Execute UPDATE query
	 * @param string $t Table name
	 * @param array $d Data for update
	 * @param string|array $w Conditions (WHERE section without WHERE keyword).
	 * @param array $params Prepared statement parameters for $w
	 * @param bool $ignore IGNORE flag
	 * @return int Amount of affected rows
	 * @throws EM */
	function Update(string$t,array$d,string|array$w='',array$params=[],bool$ignore=true):int
	{
		if(!$d)
			return 0;

		$ext=$ignore ? self::IGNORE : '';
		$q="UPDATE$ext `$t` SET ";
		$params2=[];

		foreach($d as $k=>$v)
		{
			$bypass=\is_string($v) ? null : $this::Bypass($v);

			if($bypass===null)
			{
				$params2[]=$v;
				$v='?';
			}
			else
				$v=$bypass;

			$q.="`$k`=$v,";
		}

		$q=\rtrim($q,',').$this->Where($w);

		if(\array_unshift($params,...$params2)>0)
			return $this->Execute($q,$params,false)->affected_rows;

		$this->Query($q);
		return $this->M->affected_rows;
	}

	/** Execute DELETE or TRUNCATE query
	 * @param string $t Table name
	 * @param string|array $w Conditions without WHERE keyword. When empty, the table will be truncated.
	 * @param array $params Prepared statement parameters. In this case $w should be a string.
	 * @param bool $ignore IGNORE flag
	 * @return int Number of affected rows
	 * @throws EM */
	function Delete(string$t,string|array$w='',array$params=[],bool$ignore=true):int
	{
		if(!$w and $params)
			throw new EM('Prepared parameters without WHERE clause',EM::OTHER,...BugFileLine($this));

		$ext=$ignore ? self::IGNORE : '';
		$q=$w ? "DELETE$ext FROM `$t`".$this->Where($w) : "TRUNCATE TABLE `$t`";

		if($params)
			return $this->Execute($q,$params,false)->affected_rows;

		$this->Query($q);
		return $this->M->affected_rows;
	}

	/** Generate escaped sequence for IN() statement
	 * @param array $a Data
	 * @param bool $not Flag for NOT IN
	 * @return string
	 * @throws EM */
	function In(array$a,bool$not=false):string
	{
		if(count($a)==1)
			return($not ? '!=' : '=').$this->Escape(\array_first($a));

		if(!$a)
			return $not ? ' IS NOT NULL' : ' IS NULL';

		#PHP 8.6
		$in=\join(',',\array_map($this->Escape(...),$a));

		return($not ? ' NOT' : '')." IN ($in)";
	}

	/** Generate WHERE clause
	 * @param string|array $w Conditions
	 * @return string
	 * @throws EM */
	protected function Where(string|array$w):string
	{
		if(\is_array($w))
		{
			foreach($w as $k=>&$v)
				$v="`$k`".($v===null ? ' IS NULL' : '='.$this->Escape($v));

			$w=\join(' AND ',$w);
		}

		return $w ? ' WHERE '.$w : '';
	}

	/** Bypass escaping for safe values
	 * @param mixed $p Value
	 * @return mixed Raw SQL value, or null when escaping is required */
	static function Bypass(mixed$p):mixed
	{
		if(\is_int($p) or \is_float($p))
			return $p;

		if(\is_bool($p))
			return+$p;

		if($p instanceof \Closure)
			return $p() ?? 'NULL';

		return $p===null ? 'NULL' : null;
	}

	/** Escape unsafe characters in SQL values.
	 * Closures are treated as trusted raw SQL expressions and are not escaped.
	 * @param null|int|float|string|bool|\Closure $p Value to escape
	 * @param bool $q Whether to wrap string result in quotes
	 * @param string $name Name used for debugging
	 * @return int|float|string
	 * @throws EM */
	function Escape(null|int|float|string|bool|\Closure$p,bool$q=true,string$name=''):int|float|string
	{
		if(!\is_string($p))
		{
			$bypass=$this::Bypass($p);

			if(\is_scalar($bypass))
				return $bypass;

			throw new EM(\is_object($p) ? 'Object of '.\get_class($p) : \gettype($p),EM::VALUE,query:$name);
		}

		if($p)
			$p=$this->M->real_escape_string($p);

		return $q ? "'$p'" : $p;
	}

	/** Prepared statements shortcut
	 * @param string $q Query
	 * @param array $params Parameters
	 * @param bool $result Flag for returning MySQLi_result, instead of MySQLi_stmt
	 * @throws EM
	 * @return \MySQLi_result | \MySQLi_stmt | false (depending on $result) */
	function Execute(string$q,array$params,bool$result=true):\MySQLi_result|\MySQLi_stmt|false
	{
		if(!$params)
			throw new EM('No data supplied for parameters of prepared statement',EM::PREPARED,...BugFileLine($this),query:$q,params:$params);

		try{
			$stmt=$this->M->prepare($q);
			$ok=$stmt && $this::BindParams($stmt,$params);
		}catch(\mysqli_sql_exception$E){
			throw new EM($E->getMessage(),EM::PREPARED,$E,...BugFileLine($this),errno:$E->getCode(),query:$q,params:$params);
		}

		if($ok)
			return $result ? $stmt->get_result() : $stmt;

		throw new EM($stmt ? $stmt->error : $this->M->error,EM::PREPARED,...BugFileLine($this),
			errno:$stmt ? $stmt->errno : $this->M->errno,
			query:$q,
			params:$params
		);
	}

	/** When parameters are passed directly to mysqli::execute_query, "Each value is treated as a string."
	 * This method binds parameters according to their types.
	 * @param \MySQLi_stmt $stmt
	 * @param array $params Parameters
	 * @return bool
	 * @throws \mysqli_sql_exception|EM */
	static function BindParams(\MySQLi_stmt$stmt,array$params):bool
	{
		$types='';

		foreach($params as $name=>&$p)
			if(\is_string($p))
				$types.='s';
			elseif(\is_int($p))
				$types.='i';
			elseif(\is_float($p))
				$types.='d';
			elseif(\is_bool($p))
			{
				$p=+$p;
				$types.='i';
			}
			else
				throw new EM(\is_object($p) ? 'Object of '.\get_class($p) : \gettype($p),EM::VALUE,...BugFileLine(static::class),query:$name);

		# Case when all parameters are strings
		if(!\trim($types,'s'))
			return $stmt->execute($params);

		$stmt->bind_param($types, ...$params);

		return $stmt->execute();
	}
}

# Not required here because class name matches filename
return MySQL::class;