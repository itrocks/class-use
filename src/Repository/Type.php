<?php
namespace ITRocks\Class_Use\Repository;

abstract class Type
{

	//-------------------------------------------------------------------------------- TYPE CONSTANTS
	const CLASS_     = 'class';
	const CLASS_TYPE = 'class_type';
	const FILE       = 'file';
	const LINE       = 'line';
	const TOKEN_KEY  = 'token_key';
	const TYPE       = 'type';
	const TYPE_CLASS = 'type_class';
	const TYPE_USE   = 'type_use';
	const USE        = 'use';
	const USE_TYPE   = 'use_type';

	//--------------------------------------------------------------------------------------- EXTENDS
	const EXTENDS = [
		self::CLASS_ => [self::CLASS_,     self::CLASS_TYPE],
		self::USE    => [self::USE,        self::USE_TYPE],
		self::TYPE   => [self::TYPE_CLASS, self::TYPE_USE]
	];

	//------------------------------------------------------------------------------------------ SAVE
	const SAVE = [
		self::CLASS_, self::CLASS_TYPE, self::FILE, self::TYPE_CLASS, self::TYPE_USE, self::USE,
		self::USE_TYPE
	];

}
