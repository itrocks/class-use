<?php
namespace ITRocks\Class_Use\Tests\Index\Load_And_Filter;

use Attribute;

#[Attribute]
class A
{

	//----------------------------------------------------------------------------------- __construct
	public function __construct(string $class_name)
	{
		echo $class_name;
	}

}
