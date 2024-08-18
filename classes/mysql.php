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
			throw new EE_DB('connect',EE_DB::CONNECT,null,compact('host','user','pass','db','port','socket')+['error'=>$M?->connect_error ?? 'Connect error','errno'=>$M?->connect_errno ?? 0]+BugFileLine(static::class));

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

	/** Старт транзакции */
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
				'error'=>$q ? $this->M->error : 'Empty query',
				'errno'=>$q ? $this->M->errno : 0
			];

			throw new EE_DB('query',EE_DB::QUERY,null,$extra+BugFileLine(static::class));
		}

		return $R;
	}


	public const string IGNORE='IGNORE';

	/** Обертка для удобного осуществления INSERT запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $a Данные. С форматами можно ознакомиться в GenerateInsert.
	 * @param string $ext Для запросов INSERT IGNORE значение должно быть IGNORE, для ON DUPLICATE KEY UPDATE - содержимое обновления
	 * @return int Insert ID
	 * @throws EE_DB */
	public function Insert(string$t,array$a,string$ext=self::IGNORE):int
	{
		//Обычно после ON DUPLICATE KEY UPDATE используется =
		if(str_contains($ext,'='))
			[$odku,$ext]=['ON DUPLICATE KEY UPDATE '.$ext,''];
		else
			$odku='';

		$this->Query("INSERT {$ext} INTO `{$t}`".$this->GenerateInsert($a).$odku);

		return$this->M->insert_id;
	}

	/** Обертка для удобного осуществления REPLACE запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $a Данные. С форматами можно ознакомиться в GenerateInsert
	 * @param string $ext Для запросов REPLACE IGNORE значение должно быть IGNORE
	 * @return int Affected rows
	 * @throws EE_DB */
	public function Replace(string$t,array$a,string$ext=''):int
	{
		$this->Query("REPLACE {$ext} INTO `{$t}` ".$this->GenerateInsert($a));

		return$this->M->affected_rows;
	}

	/** Генерация INSERT запроса из данных в массиве
	 * @param array $a Массив данных в одном из форматов:
	 * [ 'field1'=>'value1', 'field2'=>'value2' ] или
	 * [ 'field1'=>[ 'values11', 'value12' ], 'field2'=>[ 'value21', 'value22' ] ] или
	 * [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
	 * @return string
	 * @throws EE_DB */
	public function GenerateInsert(array$a):string
	{
		#Detecting [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
		if(array_is_list($a))
		{
			$k=array_key_first($a);
			$fields=array_keys($a[$k]);
			$values=[];

			foreach($a as $input)
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
			$fields='(`'.join('`,`',array_keys($a)).'`)';

			$values=array_values($a);
			$values=array_map(fn($item)=>(array)$item,$values);//Преобразование всех значений в array
			$values=array_map(null,...$values);//Из строк в столбцы
			$escape=[$this,'Escape'];
			$values=array_map(fn($item)=>'('.join(',',array_map($escape,$item)).')',$values);
		}

		return $fields.'VALUES'.join(',',$values);
	}

	/** Обертка для удобного осуществления UPDATE запросов
	 * @param string $t Имя таблицы, где необходимо обновить данные
	 * @param array $a Массив изменяемых данных. С форматами можно ознакомиться в GenerateInsert
	 * @param string|array $w Условие обновления. Секция WHERE, без ключевого слова WHERE.
	 * @param string|array $ext [Для запросов UPDATE IGNORE значение должно быть IGNORE]
	 * @param array $params Параметры для Prepared statements
	 * @return int Affected rows
	 * @throws EE_DB */
	public function Update(string$t,array$a,string|array$w='',string|array$ext=self::IGNORE,array$params=[]):int
	{
		if(!$a)
			return 0;

		if(is_array($ext))
		{
			$params=$ext;
			$ext=self::IGNORE;
		}

		$q="UPDATE {$ext} `{$t}` SET ";

		foreach($a as $k=>$v)
			$q.="`{$k}`=".$this->Escape($v).',';

		$q=rtrim($q,',');
		$q.=$this->Where($w);

		if($params)
			$this->Execute($q,$params);
		else
			$this->Query($q);

		return$this->M->affected_rows;
	}

	/** Обертка для удобного осуществления DELETE запросов
	 * @param string $t Имя таблицы, откуда необходимо удалить данные
	 * @param string|array $w Секция WHERE, без ключевого слова WHERE. Если не заполнять - выполнится TRUNCATE запрос.
	 * @param string|array $ext [Для запросов DELETE IGNORE значение должно быть IGNORE]
	 * @param array $params Параметры для Prepared statements
	 * @throws EE_DB
	 * @return int Affected rows */
	public function Delete(string$t,string|array$w='',string|array$ext=self::IGNORE,array$params=[]):int
	{
		if(is_array($ext))
		{
			$params=$ext;
			$ext=self::IGNORE;
		}

		$q=$w ? "DELETE {$ext} FROM `{$t}`".$this->Where($w) : "TRUNCATE TABLE `{$t}`";

		if($params)
			$this->Execute($q,$params);
		else
			$this->Query($q);

		return$this->M->affected_rows;
	}

	/** Преобразование массива в последовательность для конструкции IN(). Данные автоматически экранируются
	 * @param mixed $a Данные для конструкции IN
	 * @param bool $not Включение конструкции NOT IN. Для оптимизации запросов, по возможности используется = вместо IN
	 * @return string
	 * @throws EE_DB */
	public function In(mixed$a,bool$not=false):string
	{
		if(is_array($a) and count($a)==1)
			$a=reset($a);

		if(is_array($a))
		{
			foreach($a as &$v)
				$v=$this->Escape($v);

			return($not ? ' NOT' : '').' IN ('.join(',',$a).')';
		}

		return($not ? '!' : '').'='.$this->Escape($a);
	}

	/** Генерация секции WHERE
	 * @param string|array $w Условия
	 * @return string
	 * @throws EE_DB */
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

	/** Экранирование опасных символов в строках
	 * @param mixed $d Значение для экранирования
	 * @param bool $q Флаг включения одинарных кавычек в начало и в конец результата
	 * @return mixed
	 * @throws EE_DB */
	public function Escape(mixed$d,bool$q=true):mixed
	{
		if($d ===null)
			return'NULL';

		if(is_int($d) or is_float($d))
			return$d;

		if(is_bool($d))
			return(int)$d;

		if($d instanceof \Closure)
			return$d();

		$d=$this->M->real_escape_string((string)$d);

		return$q ? "'{$d}'" : $d;
	}

	/** Prepared statements shortcut. Я знаю о существовании mysqli::execute_query, проблема в том что каждый элемент в params интерпретируется как строка "Each value is treated as a string.":
	 * @param string $q Запрос
	 * @param array $params Параметры запроса
	 * @param bool $result Флаг возврата результата
	 * @throws EE_DB
	 * @return \MySQLi_result | \MySQLi_stmt (в зависимости от $result) */
	public function Execute(string$q,array$params=[],bool$result=true):\MySQLi_result|\MySQLi_stmt
	{
		if(!$params)
			throw new EE_DB('No data supplied for parameters in prepared statement',EE_DB::PREPARED,null,BugFileLine(static::class));

		/** @var \MySQLi_stmt $stmt */
		$stmt=$this->M->prepare($q);
		$types='';

		foreach($params as $v)
			if(is_int($v))
				$types.='i';
			else
				$types.=is_float($v) ? 'd' : 's';

		if($types)
			$stmt->bind_param($types, ...$params);

		$stmt->execute();

		if($result)
		{
			$R=$stmt->get_result();

			if($R)
				return$R;

			if($stmt->errno)
			{
				$extra=[
					'params'=>$params,
					'errno'=>$stmt->errno,
					'error'=>$stmt->error
				];

				throw new EE_DB('prepared',EE_DB::PREPARED,null,$extra+BugFileLine(static::class));
			}
		}

		return$stmt;
	}
}

return MySQL::class;