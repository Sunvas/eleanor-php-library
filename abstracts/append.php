<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Abstracts;
use Eleanor;

/** Making a string by sequential calls of methods with arguments (fluent interface). The result of each call is
 * appended to the "storage" property - accumulator. Example: $Obj->Part1([params])->Part2([params])->Part3([params])
 * Converting object to string, returns accumulator and clears it. Example: (string)$Obj . It is possible to get a
 * single method result through object invoking (without affecting the accumulator).
 * Example: $part=$Obj('Part1'[,params]); */
abstract class Append extends Eleanor\Basic implements \Stringable
{
	/** @var string Accumulator where results of methods calling are appended to */
	public string $storage='';

	/** @var bool Primary object flag: each fluent interface is a separate secondary object cloned from primary one */
	readonly bool $primary;

	/** @var array Property names that become references to the original properties of primary object when cloning */
	protected static array $linking=[];

	/** @var bool Flag allowing appending results to storage property */
	protected bool $append=true;

	/** @var Append $content Accessing object though content property disables appending and passes content of storage as content variable to the next template */
	public Append $content {
		get{
			$this->append=false;
			return $this;
		}
	}

	function __construct()
	{
		$this->primary=true;
	}

	function __clone()
	{
		$this->primary=false;
	}

	/** Fluent Interface terminator: accumulator is returned and cleaned */
	function __toString():string
	{
		$s=$this->storage;
		$this->storage='';
		return$s;
	}

	/** A single method result, without appending to accumulator
	 * @param string $n Template name
	 * @param mixed ...$a Variables (arguments)
	 * @return string */
	function __invoke(string$n,...$a):string
	{
		return$this->_($n,$a);
	}

	/** Fluent interface realization
	 * @param string $n Template name
	 * @param array $a Variables (arguments)
	 * @return static */
	function __call(string$n,array$a):static
	{
		if($this->primary)
		{
			$O=clone$this;

			foreach(static::$linking as $v)
				$O->$v=&$this->$v;

			return$O->__call($n,$a);
		}

		if($this->append)
			$this->storage.=$this->_($n,$a);
		else
		{
			$this->storage=$this->_($n,$a);
			$this->append=true;
		}

		return$this;
	}

	/** Source of fluent interface methods
	 * @param string $n Name
	 * @param array $a Arguments */
	abstract protected function _(string$n,array$a):string;
}

return Append::class;