<?php
namespace ITRocks\Depend\Tokens_Scanner;

abstract class Type
{

	const ARGUMENT          = 'argument';
	const ATTRIBUTE         = 'attribute';
	const CLASS_            = 'class';
	const DECLARE_CLASS     = 'declare-class';
	const DECLARE_INTERFACE = 'declare-interface';
	const DECLARE_TRAIT     = 'declare-trait';
	const EXTENDS           = 'extends';
	const IMPLEMENTS        = 'implements';
	const INSTANCE_OF       = 'instance_of';
	const NAMESPACE         = 'namespace';
	const NAMESPACE_USE     = 'namespace-use';
	const NEW               = 'new';
	const RETURN            = 'return';
	const STATIC            = 'static';
	const USE               = 'use';
	const VAR               = 'var';

}
