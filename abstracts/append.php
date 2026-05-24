<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Abstracts;
use Eleanor\Classes\E;

/** Fluent string builder based on sequential method calls.
 * Each dynamic method call appends its generated string to an internal accumulator. Casting the object to string
 * returns the accumulated result and clears it.
 * Calling the object directly returns a single generated fragment without affecting the accumulator. */
abstract class Append extends \Eleanor\Basic implements \Stringable
{
	/** @var bool Whether this object is the primary builder instance. Each fluent chain operates on a cloned secondary instance. */
	readonly bool $primary;

	/** @var string Accumulator for generated method results */
	protected string $storage='';

	/** @var array Property names linked by reference from primary object to cloned instances */
	protected array $linking=[] {
		/** @throws E */
		set=>\array_all($value,fn($item)=>\property_exists($this,$item) || throw new E("Linked property $item does not exist",E::PHP,...\Eleanor\BugFileLine($this))) ? $value : [];
	}

	/** Initialize primary builder instance */
	function __construct()
	{
		$this->primary=true;
	}

	/** Return accumulated string and clear accumulator.
	 * @return string */
	function __toString():string
	{
		$s=$this->storage;
		$this->storage='';
		return$s;
	}

	/** Generate a single fragment without appending it to the accumulator.
	 * @param string $n Fragment name
	 * @param mixed ...$a Fragment arguments
	 * @return string */
	function __invoke(string$n,...$a):string
	{
		return$this->_($n,...$a);
	}

	/** Fluent interface realization: generates a fragment and appends it to the accumulator.
	 * @param string $n Fragment name
	 * @param array $a Fragment arguments
	 * @return static */
	function __call(string$n,array$a):static
	{
		if($this->primary)
		{
			$O=clone($this,[
				'primary'=>false
			]);

			foreach($O->linking as $v)
				$O->$v=&$this->$v;

			$O->storage.=$O->_($n,...$a);

			return$O;
		}

		$this->storage.=$this->_($n,...$a);

		return$this;
	}

	/** Generate a string fragment by name.
	 * @param string $n Fragment name
	 * @param mixed ...$a Fragment arguments */
	abstract protected function _(string$n,...$a):string;
}

# Not required here because class name matches filename
return Append::class;