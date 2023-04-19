<?php
namespace ITRocks\Depend\Repository;

abstract class Type
{

	//-------------------------------------------------------------------------------- TYPE CONSTANTS
	const CLASS_          = 'class';
	const CLASS_TYPE      = 'class_type';
	const DEPENDENCY      = 'dependency';
	const DEPENDENCY_TYPE = 'dependency_type';
	const FILE            = 'file';
	const TYPE            = 'type';
	const TYPE_CLASS      = 'type_class';
	const TYPE_DEPENDENCY = 'type_dependency';

	//--------------------------------------------------------------------------------------- EXTENDS
	const EXTENDS = [
		self::CLASS_     => [self::CLASS_,     self::CLASS_TYPE],
		self::DEPENDENCY => [self::DEPENDENCY, self::DEPENDENCY_TYPE],
		self::TYPE       => [self::TYPE_CLASS, self::TYPE_DEPENDENCY]
	];

	//------------------------------------------------------------------------------------------ SAVE
	const SAVE = [
		self::FILE, self::CLASS_, self::CLASS_TYPE, self::DEPENDENCY, self::DEPENDENCY_TYPE,
		self::TYPE_CLASS, self::TYPE_DEPENDENCY
	];

}
