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
 * @property \MySQLi $Driver Объект базы данных */
class MySQL extends Eleanor\BaseClass
{
	/** @var \MySQLi */
	public \MySQLi $Driver;

	/** @var string Название базы данных, с которой мы работаем */
	public string $db;

	/** @var array Промежуточное хранение параметров */
	protected array $params=[];

	/** Соединение с БД
	 * @param array|\MySQLi $p Объект MySQLi или параметры соединения с БД. Ключи массива:
	 *  [string host] Сервер БД.
	 *  [string user] Пользователь БД
	 *  [string pass] Пароль пользователя
	 *  [string db] Название базы данных
	 *  [string charset] Кодировка, необязательный параметр https://dev.mysql.com/doc/refman/8.0/en/charset-charsets.html
	 *  [bool now] Флаг немедленного подключения. Во всех остальных случаях, подключение происходит по требованию
	 *  [bool sync] Флаг синхронизации времени БД с временем PHP
	 * @throws EE_DB */
	public function __construct(\MySQLi|array$p)
	{
		if(is_object($p))
			$this->Driver=$p;
		else
		{
			$this->params=$p;
			$this->params['query']=[];

			if(isset($p['now']))
				$this->Connect();
			else#При некорректном запросе - отключим отображение файла и номера строки (в момент запроса не важно, где был создан объект этого класса)
				$this->params['file']=null;
		}
	}

	/** Деструктор */
	public function __destruct()
	{
		if(isset($this->Driver))
			$this->Driver->close();
	}

	/** Выполнение подключения к БД
	 * @throws EE_DB */
	protected function Connect():void
	{
		if(!isset($this->params['host'],$this->params['user'],$this->params['pass'],$this->params['db']))
			throw new EE_DB('lack_of_data',EE_DB::CONNECT,null,$this->params+BugFileLine(static::class));

		$M=Eleanor\QuietExecution(fn()=>new \MySQLi($this->params['host'],$this->params['user'],$this->params['pass'],$this->params['db']));

		if($M?->connect_errno or !$M?->server_version)
			throw new EE_DB('connect',EE_DB::CONNECT,null,$this->params+['error'=>$M?->connect_error ?? 'Connect error','errno'=>$M?->connect_errno ?? 0]+BugFileLine(static::class));

		$M->autocommit(true);
		$M->set_charset($this->params['charset'] ?? 'utf8mb4');

		$this->Driver=$M;
		$this->db=$this->params['db'];

		if($this->params['sync'])
			$this->SyncTimeZone();

		foreach($this->params['query'] as $q)
			$this->Driver->query($q);

		unset($this->params);
	}

	/** Получение $this->Driver
	 * @param string $n Имя
	 * @throws EE
	 * @return mixed */
	public function __get(string$n):mixed
	{
		if($n=='Driver')
		{
			$this->Connect();
			return$this->Driver;
		}

		return parent::__get($n);
	}

	/** Обертка для упрощенного доступа к методам объектов MySQLi и результата MySQLi
	 * @param string $n Имя вызываемого метода
	 * @param array $p Параметры вызова
	 * @throws EE
	 * @return mixed */
	public function __call(string$n,array$p):mixed
	{
		if(method_exists($this->Driver,$n))
			return call_user_func_array([$this->Driver,$n],$p);

		return parent::__call($n,$p);
	}

	/** Синхронизация времени БД со временем PHP (применение часового пояса). Синхронизируются только поля типа
	 * TIMESTAMP */
	protected function SyncTimeZone():void
	{
		$t=date_offset_get(date_create());
		$s=$t>0 ? '+' : '-';
		$t=abs($t);
		$s.=floor($t/3600).':'.($t%3600);

		if(isset($this->Driver))
			$this->Driver->query("SET TIME_ZONE='{$s}'");
		else
			$this->params['query']['sync']="SET TIME_ZONE='{$s}'";
	}

	/** Старт транзакции */
	public function Transaction():void
	{
		$this->Driver->autocommit(false);
	}

	/** Подтверждение транзакции */
	public function Commit():void
	{
		$this->Driver->commit();
		$this->Driver->autocommit(true);
	}

	/** Откат транзакции */
	public function RollBack():void
	{
		$this->Driver->rollback();
		$this->Driver->autocommit(true);
	}

	/** Выполнение SQL запроса в базу
	 * @param string|array $q SQL запрос (в случае array, будет использовано multi_query)
	 * @param int $mode
	 * @throws EE_DB
	 * @return bool|\mysqli_result|\mysqli */
	public function Query(string|array$q,int$mode=MYSQLI_STORE_RESULT):bool|\mysqli_result|\mysqli
	{
		$isa=is_array($q);
		if($isa)
			$q=join(';',$q);

		if(!isset($this->Driver))
			$this->Connect();

		if($isa)
		{
			$R=$this->Driver->multi_query($q);
			$return_r=false;
		}
		elseif($mode===false)
		{
			$R=$this->Driver->real_query($q);
			$return_r=false;
		}
		else
		{
			$R=$this->Driver->query($q,$mode);
			$return_r=$mode!==\MYSQLI_ASYNC;
		}

		if($R===false)
		{
			$extra=[
				'query'=>$q,
				'error'=>$q ? $this->Driver->error : 'Empty query',
				'errno'=>$q ? $this->Driver->errno : 0
			];

			throw new EE_DB('query',EE_DB::QUERY,null,$extra+BugFileLine(static::class));
		}

		return$return_r ? $R : $this->Driver;
	}

	/** Обертка для удобного осуществления INSERT запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $a Данные. С форматами можно ознакомиться в GenerateInsert.
	 * @param string $type Тип INSERT запроса
	 * @throws EE_DB
	 * @return int Insert ID */
	public function Insert(string$t,array$a,string$type='IGNORE'):int
	{
		//Обычно после ON DUPLICATE KEY UPDATE используется =
		if(str_contains($type,'='))
			[$odku,$type]=['ON DUPLICATE KEY UPDATE '.$type,''];
		else
			$odku='';

		$this->Query("INSERT {$type} INTO `{$t}`".$this->GenerateInsert($a).$odku);

		return$this->Driver->insert_id;
	}

	/** Обертка для удобного осуществления REPLACE запросов
	 * @param string $t Имя таблицы, куда необходимо вставить данные
	 * @param array $a Данные. С форматами можно ознакомиться в GenerateInsert
	 * @param string $type Тип REPLACE запроса
	 * @throws EE_DB
	 * @return int Affected rows */
	public function Replace(string$t,array$a,string$type=''):int
	{
		$this->Query("REPLACE {$type} INTO `{$t}` ".$this->GenerateInsert($a));

		return$this->Driver->affected_rows;
	}

	/** Генерация INSERT запроса из данных в массиве
	 * @param array $a Массив данных в одном из форматов:
	 * [ 'field1'=>'value1', 'field2'=>'value2' ] или
	 * [ 'field1'=>[ 'values11', 'value12' ], 'field2'=>[ 'value21', 'value22' ] ] или
	 * [ ['field1'=>'value11', 'field2'=>'value12' ], ['field1'=>'value21', 'field2'=>'value22' ]  ]
	 * @return string */
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
	 * @param string $w Условие обновления. Секция WHERE, без ключевого слова WHERE.
	 * @param string|array $type [Тип запроса, по умолчанию - ignore]
	 * @param array $params Параметры для Prepared statements
	 * @throws EE_DB
	 * @return int Affected rows */
	public function Update(string$t,array$a,string$w='',string|array$type='IGNORE',array$params=[]):int
	{
		if(!$a)
			return 0;

		if(is_array($type))
		{
			$params=$type;
			$type='IGNORE';
		}

		$q="UPDATE {$type} `{$t}` SET ";

		foreach($a as $k=>$v)
			$q.="`{$k}`=".$this->Escape($v).',';

		$q=rtrim($q,',');

		if($w)
			$q.=' WHERE '.$w;

		if($params)
			$this->Execute($q,$params);
		else
			$this->Query($q);

		return$this->Driver->affected_rows;
	}

	/** Обертка для удобного осуществления DELETE запросов
	 * @param string $t Имя таблицы, откуда необходимо удалить данные
	 * @param string $w Секция WHERE, без ключевого слова WHERE. Если не заполнять - выполнится TRUNCATE запрос.
	 * @param string|array $type [Тип запроса, по умолчанию - ignore]
	 * @param array $params Параметры для Prepared statements
	 * @throws EE_DB
	 * @return int Affected rows */
	public function Delete(string$t,string$w='',string|array$type='IGNORE',array$params=[]):int
	{
		if(is_array($type))
		{
			$params=$type;
			$type='IGNORE';
		}

		$q=$w ? "DELETE {$type} FROM `{$t}` WHERE ".$w : "TRUNCATE TABLE `{$t}`";

		if($params)
			$this->Execute($q,$params);
		else
			$this->Query($q);

		return$this->Driver->affected_rows;
	}

	/** Преобразование массива в последовательность для конструкции IN(). Данные автоматически экранируются
	 * @param mixed $a Данные для конструкции IN
	 * @param bool $not Включение конструкции NOT IN. Для оптимизации запросов, по возможности используется = вместо IN
	 * @return string */
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

	/** Экранирование опасных символов в строках
	 * @param string $s Строка для экранирования
	 * @param bool $qs Флаг включения одинарных кавычек в начало и в конец результата
	 * @throws EE_DB
	 * @return mixed */
	public function Escape($s,bool$qs=true):mixed
	{
		if($s===null)
			return'NULL';

		if(is_int($s) or is_float($s))
			return$s;

		if(is_bool($s))
			return(int)$s;

		if($s instanceof \Closure)
			return$s();

		if(!isset($this->Driver))
			$this->Connect();

		$s=$this->Driver->real_escape_string((string)$s);

		return$qs ? "'{$s}'" : $s;
	}

	/** Prepared statements shortcut. Я знаю о существовании mysqli::execute_query, проблема в том что каждый элемент в params интерпретируется как строка "Each value is treated as a string.":
	 * @param string $q Запрос
	 * @param array $params Параметры запроса
	 * @param bool $result Флаг возврата результата
	 * @throws EE_DB
	 * @return \MySQLi_stmt | \MySQLi_result в зависимости от $result В случае UPDATE или INSERT запросов, возвращает объект MySQLi_stmt вне зависимости от $result */
	public function Execute(string$q,array$params=[],bool$result=true):\MySQLi_stmt|\MySQLi_result
	{
		if(!$params)
			throw new EE_DB('No data supplied for parameters in prepared statement',EE_DB::PREPARED,null,BugFileLine(static::class));

		if(!isset($this->Driver))
			$this->Connect();

		/** @var \MySQLi_stmt $stmt */
		$stmt=$this->Driver->prepare($q);
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