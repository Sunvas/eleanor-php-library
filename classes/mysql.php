<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;
use function Eleanor\BugFileLine;

/** Библиотека для работы с MySQL, с использованием драйвера MySQLi
 * Не учитывается SELECT @@max_allowed_packet
 * @property \MySQLi $M Объект базы данных */
class MySQL extends Eleanor\BaseClass
{
	/** @var \MySQLi */
	public \MySQLi $M;

	/** Соединение с БД
	 * @url https://www.php.net/manual/ru/mysqli.construct.php
	 * @param null|string|\MySQLi $host Объект MySQLi (остальные параметры будут проигнорированы), имя хоста или IP
	 * @param ?string $user Имя пользователя MySQL
	 * @param ?string $pass Пароль MySQL
	 * @param ?string $db База данных по умолчанию
	 * @param string $charset Кодировка базы данных по умолчанию https://dev.mysql.com/doc/refman/8.0/en/charset-charsets.html
	 * @param bool $sync Флаг синхронизации времени БД с временем сервера
	 * @param ?int $port Номер порта для попытки подключения к серверу MySQL
	 * @param ?string $socket Сокет или именованный пайп
	 * @throws EE_DB */
	public function __construct(null|string|\MySQLi$host=null,?string$user=null,#[\SensitiveParameter]?string$pass=null,?string$db=null,string$charset='utf8mb4',bool$sync=true,?int$port=null,?string$socket=null)
	{
		if($host instanceof \MySQLi)
		{
			$this->M=$host;
			return;
		}

		$M=Eleanor\QuietExecution(fn()=>new \MySQLi($host,$user,$pass,$db,$port,$socket));

		if($M?->connect_errno or !$M?->server_version)
			throw new EE_DB($M?->connect_error ?? 'Connect error',EE_DB::CONNECT,null,compact('host','user','pass','db','port','socket')+['errno'=>$M?->connect_errno ?? 0]+BugFileLine($this::class));

		$M->autocommit(true);
		$M->set_charset($charset);

		$this->M=$M;

		if($sync)
			$this->SyncTimeZone();
	}

	public function __destruct()
	{
		$this->M->close();
	}

	/** "Прокси" для доступа к свойствам объекта MySQLi
	 * @param string $n Имя
	 * @throws EE
	 * @return mixed */
	public function __get(string$n):mixed
	{
		if(property_exists($this->M,$n))
			return $this->M->$n;

		return parent::__get($n);
	}

	/** "Прокси" для доступа к методам объектов MySQLi
	 * @param string $n Имя вызываемого метода
	 * @param array $p Параметры вызова
	 * @throws EE
	 * @return mixed */
	public function __call(string$n,array$p):mixed
	{
		if(method_exists($this->M,$n))
			return call_user_func_array([$this->M,$n],$p);

		return parent::__call($n,$p);
	}

	/** Синхронизация времени БД со временем PHP (применение часового пояса). Синхронизируются только поля типа
	 * TIMESTAMP */
	public function SyncTimeZone():void
	{
		$t=date_offset_get(date_create());
		$s=$t>0 ? '+' : '-';
		$t=abs($t);
		$s.=floor($t/3600).':'.($t%3600);

		$this->M->query("SET TIME_ZONE='{$s}'");
	}

	/** Начало транзакции */
	public function Transaction():void
	{
		$this->M->autocommit(false);
	}

	/** Подтверждение транзакции */
	public function Commit():void
	{
		$this->M->commit();
		$this->M->autocommit(true);
	}

	/** Откат транзакции */
	public function RollBack():void
	{
		$this->M->rollback();
		$this->M->autocommit(true);
	}

	/** Выполнение SQL запроса в базу
	 * @param string $q SQL запрос
	 * @param int $mode
	 * @throws EE_DB
	 * @return bool|\mysqli_result */
	public function Query(string$q,int$mode=MYSQLI_STORE_RESULT):bool|\mysqli_result
	{
		$R=$this->M->query($q,$mode);

		if($R===false)
		{
			$extra=[
				'query'=>$q,
				'errno'=>$q ? $this->M->errno : 0
			];

			throw new EE_DB($q ? $this->M->error : 'Empty query',EE_DB::QUERY,null,$extra+BugFileLine($this::class));
		}

		return $R;
	}


	public const string IGNORE=' IGNORE';

	/** Обертка для удобного осуществления INSERT запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $d Данные. Форматы описаны в методе Insert4Query
	 * @param bool|string $ignore_odku Флаг IGNORE или содержимое ON DUPLICATE KEY UPDATE
	 * @param ?array $params Параметры для Prepared statements, при NULL будет вызвана Query
	 * @return int Insert ID
	 * @throws EE_DB */
	public function Insert(string$t,array$d,bool|string$ignore_odku=true,?array$params=[]):int
	{
		if(is_bool($ignore_odku))
		{
			$ext=$ignore_odku ? self::IGNORE : '';
			$odku='';
		}
		else
		{
			$ext='';
			$odku='ON DUPLICATE KEY UPDATE '.$ignore_odku;
		}

		$insert=null;

		if($params!==null)
		{
			[$insert,$params1]=$this::Insert4Prepared($d);
			array_unshift($params,...$params1);
		}

		if($params)
			return$this->Execute("INSERT{$ext} INTO `{$t}`".$insert.$odku,$params,false)->insert_id;

		$insert??=$this->Insert4Query($d);
		$this->Query("INSERT{$ext} INTO `{$t}`".$insert.$odku);

		return$this->M->insert_id;
	}

	/** Обертка для удобного осуществления REPLACE запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $d Данные. Форматы описаны в методе Insert4Query
	 * @param bool $ignore Флаг IGNORE
	 * @param bool $query Флаг вызова query, вместо execute
	 * @return int Affected rows
	 * @throws EE_DB */
	public function Replace(string$t,array$d,bool$ignore=false,bool$query=false):int
	{
		$ext=$ignore ? self::IGNORE : '';

		if($query)
		{
			$this->Query("REPLACE{$ext} INTO `{$t}` ".$this->Insert4Query($d));

			return$this->M->affected_rows;
		}

		[$insert,$params]=$this::Insert4Prepared($d);
		return$this->Execute("REPLACE{$ext} INTO `{$t}` ".$insert,$params,false)->affected_rows;
	}

	/** Генерация секции INSERT для Query
	 * @param array $d Данные в одном из форматов:
	 * [ 'field1'=>'value1', 'field2'=>'value2' ] или
	 * [ 'field1'=>[ 'values11', 'value12' ], 'field2'=>[ 'value21', 'value22' ] ] или
	 * [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
	 * @return string */
	public function Insert4Query(array$d):string
	{
		#Detecting [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
		if(array_is_list($d))
		{
			$k=array_key_first($d);
			$fields=array_keys($d[$k]);
			$values=[];

			foreach($d as $input)
			{
				$group=[];

				foreach($fields as $index=>$field)
					$group[]=$this->Escape($input[$field] ?? $input[$index] ?? null);

				$values[]='('.join(',',$group).')';
			}

			$fields='(`'.join('`,`',$fields).'`)';
		}
		else
		{
			$fields='(`'.join('`,`',array_keys($d)).'`)';

			$values=array_values($d);
			$values=array_map(fn($item)=>(array)$item,$values);//Преобразование всех значений в array
			$values=array_map(null,...$values);//Из строк в столбцы
			$values=array_map(fn($item)=>'('.join(',',array_map($this->Escape(...),$item)).')',$values);
		}

		return $fields.'VALUES'.join(',',$values);
	}

	/** Генерация секции INSERT для Prepared Statements
	 * @param array $d Описание смотреть в методе Insert4Query
	 * @return array [string INSERT,array $params] */
	public static function Insert4Prepared(array$d):array
	{
		$params=[];

		if(array_is_list($d))
		{
			$k=array_key_first($d);
			$fields=array_keys($d[$k]);
			$values=[];

			foreach($d as $input)
			{
				$group=[];

				foreach($fields as $index=>$field)
				{
					$v=$input[$field] ?? $input[$index] ?? null;
					$bypass=static::Bypass($v);

					if($bypass===null)
					{
						$params[]=$v;
						$v='?';
					}
					else
						$v=$bypass;

					$group[]=$v;
				}

				$values[]='('.join(',',$group).')';
			}

			$fields='(`'.join('`,`',$fields).'`)';
		}
		else
		{
			$fields='(`'.join('`,`',array_keys($d)).'`)';
			$map=function($v)use(&$params){
				$bypass=static::Bypass($v);

				if($bypass!==null)
					return$bypass;

				$params[]=$v;
				return'?';
			};

			$values=array_values($d);
			$values=array_map(fn($item)=>(array)$item,$values);//Преобразование всех значений в array
			$values=array_map(null,...$values);//Из строк в столбцы
			$values=array_map(fn($item)=>'('.join(',',array_map($map,$item)).')',$values);
		}

		return[$fields.'VALUES'.join(',',$values),$params];
	}

	/** Обертка для удобного осуществления UPDATE запросов
	 * @param string $t Имя таблицы, где необходимо обновить данные
	 * @param array $d Массив изменяемых данных. С форматами можно ознакомиться в Insert4Query
	 * @param string|array $w Условие обновления. Секция WHERE, без ключевого слова WHERE.
	 * @param ?array $params Параметры для Prepared statements, при NULL будет вызвана Query
	 * @param bool $ignore Флаг IGNORE
	 * @return int Affected rows
	 * @throws EE_DB */
	public function Update(string$t,array$d,string|array$w='',?array$params=[],bool$ignore=true):int
	{
		if(!$d)
			return 0;

		$ext=$ignore ? self::IGNORE : '';
		$q="UPDATE{$ext} `{$t}` SET ";

		if($params===null)
		{
			foreach($d as $k=>$v)
				$q.="`{$k}`=".$this->Escape($v).',';
		}
		else
		{
			foreach($d as $k=>$v)
			{
				$bypass=$this::Bypass($v);

				if($bypass===null)
					$v='?';
				else
				{
					unset($d[$k]);
					$v=$bypass;
				}

				$q.="`{$k}`={$v},";
			}

			array_unshift($params,...array_values($d));
		}

		$q=rtrim($q,',');
		$q.=$this->Where($w);

		if($params)
			return$this->Execute($q,$params,false)->affected_rows;

		$this->Query($q);
		return$this->M->affected_rows;
	}

	/** Обертка для удобного осуществления DELETE запросов
	 * @param string $t Имя таблицы, откуда необходимо удалить данные
	 * @param string|array $w Секция WHERE, без ключевого слова WHERE. Если не заполнять - выполнится TRUNCATE запрос.
	 * @param array $params Параметры для Prepared statements, значение $w в этом случае должно быть строковым
	 * @param bool $ignore Флаг IGNORE
	 * @return int Affected rows
	 * @throws EE_DB */
	public function Delete(string$t,string|array$w='',array$params=[],bool$ignore=true):int
	{
		$ext=$ignore ? self::IGNORE : '';
		$q=$w ? "DELETE{$ext} FROM `{$t}`".$this->Where($w) : "TRUNCATE TABLE `{$t}`";

		if($params)
			return$this->Execute($q,$params,false)->affected_rows;

		$this->Query($q);
		return$this->M->affected_rows;
	}

	/** Преобразование массива в последовательность для конструкции IN(). Данные автоматически экранируются
	 * @param array $a Данные для конструкции IN
	 * @param bool $not Включение конструкции NOT IN. Для оптимизации запросов, по возможности используется = вместо IN
	 * @return string */
	public function In(array$a,bool$not=false):string
	{
		if(count($a)==1)
		{
			$a=reset($a);
			return($not ? '!' : '').'='.$this->Escape($a);
		}

		foreach($a as &$v)
			$v=$this->Escape($v);

		return($not ? ' NOT' : '').' IN ('.join(',',$a).')';
	}

	/** Генерация секции WHERE
	 * @param string|array $w Условия
	 * @return string */
	public function Where(string|array$w):string
	{
		if(is_array($w))
		{
			foreach($w as $k=>&$v)
				$v="`{$k}`=".$this->Escape($v);

			$w=implode(' AND ',$w);
		}

		return $w ? ' WHERE '.$w : '';
	}

	/** Обход экранирование, если значение параметра является целым, дробным и т.п.
	 * @param mixed $p Значение параметра
	 * @return mixed NULL если значение нуждается в экранировании */
	public static function Bypass(mixed$p):mixed
	{
		if($p===null)
			return'NULL';

		if(is_int($p) or is_float($p))
			return$p;

		if(is_bool($p))
			return(int)$p;

		if($p instanceof \Closure)
			return$p() ?? 'NULL';

		return null;
	}

	/** Экранирование опасных символов в строках
	 * @param mixed $p Значение параметра для экранирования
	 * @param bool $q Флаг включения одинарных кавычек в начало и в конец результата
	 * @return mixed */
	public function Escape(mixed$p,bool$q=true):mixed
	{
		$bypass=$this::Bypass($p);

		if($bypass!==null)
			return$bypass;

		if(is_array($p))
			return$this->In($p);

		$p=$this->M->real_escape_string((string)$p);

		return$q ? "'{$p}'" : $p;
	}

	/** Prepared statements shortcut.
	 * @param string $q Запрос
	 * @param array $params Параметры запроса
	 * @param bool $result Флаг возврата результата
	 * @throws EE_DB
	 * @return \MySQLi_result | \MySQLi_stmt (в зависимости от $result) */
	public function Execute(string$q,array$params,bool$result=true):\MySQLi_result|\MySQLi_stmt
	{
		if(!$params)
			throw new EE_DB('No data supplied for parameters of prepared statement',EE_DB::PREPARED,null,['query'=>$q]+BugFileLine($this::class));

		$stmt=$this->M->prepare($q);
		$this::BindParams($stmt,$params);

		if($result)
		{
			$R=$stmt->get_result();

			if($R)
				return$R;

			if($stmt->errno)
			{
				$extra=[
					'query'=>$q,
					'params'=>$params,
					'errno'=>$stmt->errno
				];

				throw new EE_DB($stmt->error,EE_DB::PREPARED,null,$extra+BugFileLine($this::class));
			}
		}

		return$stmt;
	}

	/** При прямой передаче параметров в mysqli::execute_query, каждый элемент из params интерпретируется как строка "Each value is treated as a string."
	 * Этот же метод позволяет передать в prepared statement числа */
	public static function BindParams(\MySQLi_stmt$stmt,array$params):bool
	{
		//Если массив целиком состоит из строковых значений
		if(array_reduce($params,fn($carry,$item)=>$carry && is_string($item),true))
			return$stmt->execute($params);

		$types='';

		foreach($params as &$p)
			if(is_int($p))
				$types.='i';
			elseif(is_float($p))
				$types.='d';
			else
			{
				$types.='s';

				if(!is_string($p))
					$p=(string)$p;
			}

		$stmt->bind_param($types, ...$params);

		return$stmt->execute();
	}
}

return MySQL::class;