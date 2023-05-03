<?php
namespace ITRocks\Class_Use\Token;

class Scanner
{

	//----------------------------------------------------------------------------------- BASIC_TYPES
	protected const BASIC_TYPES = [
		'array', 'bool', 'callable', 'false', 'float', 'int', 'null', 'object', 'string', 'true', 'void'
	];

	//------------------------------------------------------------------------------------ TOKEN SETS
	protected const CLASS_TOKENS    = [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_STRING];
	protected const IGNORE_TOKENS   = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
	protected const VARIABLE_TOKENS = [T_CONST, T_FUNCTION, ...self::CLASS_TOKENS, T_VARIABLE];

	//-------------------------------------------------------------------------------- IGNORE_CLASSES
	protected const IGNORE_CLASSES = ['__CLASS__', '__TRAIT__', 'parent', 'self', 'static'];

	//------------------------------------------------------------------------------------ $attribute
	protected string $attribute;

	//--------------------------------------------------------------------------- $attribute_brackets
	protected int $attribute_brackets;

	//------------------------------------------------------------------------ $attribute_parentheses
	protected int $attribute_parentheses;

	//--------------------------------------------------------------------------------------- $braces
	protected int $braces;

	//------------------------------------------------------------------------------------- $brackets
	protected int $brackets;

	//---------------------------------------------------------------------------------------- $class
	protected string $class;

	//--------------------------------------------------------------------------------- $class_braces
	/** @var int[] */
	protected array $class_braces;

	//------------------------------------------------------------------------------------ $namespace
	protected string $namespace;

	//----------------------------------------------------------------------------- $namespace_braces
	protected int $namespace_braces;

	//-------------------------------------------------------------------------------- $namespace_use
	/** @var string[] */
	protected array $namespace_use;

	//------------------------------------------------------------------------------ $next_references
	/** [string $class, int|string $type, string $use, int $line, int $token_key] */
	public array $next_references;

	//---------------------------------------------------------------------------------- $parentheses
	protected int $parentheses;

	//----------------------------------------------------------------------------------- $references
	/** [string $class, int|string $type, string $use, int $line, int $token_key] */
	public array $references;

	//-------------------------------------------------------------------------- appendNextReferences
	protected function appendNextReferences() : void
	{
		if (!$this->class) {
			$this->references = array_merge($this->references, $this->next_references);
		}
		else foreach ($this->next_references as $reference) {
			$reference[0]       = $this->class;
			$this->references[] = $reference;
		}
		$this->next_references = [];
	}

	//-------------------------------------------------------------------------------------- phpBlock
	/** @param $tokens int[]|string[]|string */
	protected function phpBlock(array &$tokens) : void
	{
		while ($token = next($tokens)) switch ($token[0]) {

			case '(':
				$this->parentheses ++;
				continue 2;

			case ')':
				$this->parentheses --;
				continue 2;

			case T_CURLY_OPEN:
			case '{':
				$this->braces ++;
				continue 2;

			case '}':
				$this->braces --;
				if ($this->braces === end($this->class_braces)) {
					array_pop($this->class_braces);
					$this->class = '';
					continue 2;
				}
				if ($this->braces === $this->namespace_braces) {
					$this->namespace        = '';
					$this->namespace_braces = -1;
					$this->namespace_use    = [];
				}
				continue 2;

			case '[':
				$this->brackets ++;
				continue 2;

			case ']':
				$this->brackets --;
				if ($this->brackets === $this->attribute_brackets) {
					$this->attribute             = '';
					$this->attribute_parentheses = -1;
					$this->attribute_brackets    = -1;
				}
				continue 2;

			case T_ATTRIBUTE:
				$this->attribute_parentheses = $this->parentheses;
				$this->attribute_brackets    = $this->brackets;
				$this->brackets ++;
				continue 2;

			case T_CLASS:
				$this->class_braces[] = $this->braces;
				do $token = next($tokens); while ($token[0] !== T_STRING);
				$this->class = $this->reference(T_DECLARE_CLASS, $token, key($tokens));
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_CLOSE_TAG:
				next($tokens);
				return;

			case T_CONST:
				// skip constant name as it may be a reserved word token (e.g. T_USE for constant USE)
				do $token = next($tokens); while ($token[0] !== '=');
				continue 2;

			case T_EXTENDS:
				do $token = next($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
				$this->reference(T_EXTENDS, $token, key($tokens));
				continue 2;

			case T_FUNCTION:
				if ($this->next_references) $this->appendNextReferences();
				do $token = next($tokens); while ($token !== '(');
				$depth = 1;
				do {
					$token = next($tokens);
					if     ($token === '(') $depth ++;
					elseif ($token === ')') $depth --;
					elseif (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						$this->reference(T_ARGUMENT, $token, key($tokens));
					}
				} while ($depth && isset($token));
				do $token = next($tokens); while (is_array($token) || !str_contains(':{;', $token));
				while (is_array($token) || !str_contains('{;', $token)) {
					$token = next($tokens);
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						$this->reference(T_RETURN, $token, key($tokens));
					}
				}
				if ($token === '{') {
					$this->braces ++;
				}
				continue 2;

			case T_IMPLEMENTS:
				do {
					$token = next($tokens);
					if (in_array($token[0], self::CLASS_TOKENS, true)) {
						$this->reference(T_IMPLEMENTS, $token, key($tokens));
					}
				} while ($token[0] !== '{');
				$this->braces ++;
				continue 2;

			case T_INTERFACE:
				$this->class_braces[] = $this->braces;
				do $token = next($tokens); while ($token[0] !== T_STRING);
				$this->class = $this->reference(T_DECLARE_INTERFACE, $token, key($tokens));
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_INSTANCEOF:
			case T_NEW:
				$type = $token[0];
				do $token = next($tokens); while (in_array($token[0], self::IGNORE_TOKENS, true));
				if (in_array($token[0], self::CLASS_TOKENS, true)) {
					$this->reference($type, $token, key($tokens));
					continue 2;
				}
				// anonymous
				if (is_string($token) && str_contains('{([])}', $token)) {
					prev($tokens);
				}
				continue 2;

			case T_NAME_QUALIFIED:
			case T_NAME_FULLY_QUALIFIED:
			case T_STRING:
				if ($this->attribute_parentheses === $this->parentheses) {
					$this->attribute = '';
					$this->attribute = $this->reference(T_ATTRIBUTE, $token, key($tokens));
				}
				continue 2;

			case T_NAMESPACE:
				do $token = next($tokens); while (in_array($token[0], self::IGNORE_TOKENS, true));
				$this->namespace = $token[1];
				do $token = next($tokens); while (is_array($token) || !str_contains(';{', $token));
				if ($token === '{') {
					$this->namespace_braces = $this->braces;
					$this->braces ++;
				}
				continue 2;

			case T_PAAMAYIM_NEKUDOTAYIM:
				do $token = prev($tokens); while (in_array($token[0], self::IGNORE_TOKENS, true));
				$ignore = !in_array($token[0], self::CLASS_TOKENS, true)
					|| in_array($token[1], self::IGNORE_CLASSES, true);
				$token_key = key($tokens);
				do $token = next($tokens); while ($token[0] !== T_PAAMAYIM_NEKUDOTAYIM);
				do $token = next($tokens); while (in_array($token[0], self::IGNORE_TOKENS, true));
				if ($ignore) {
					continue 2;
				}
				$type  = ($token[0] === T_CLASS) ? T_CLASS : T_STATIC;
				$token = $tokens[$token_key];
				$this->reference($type, $token, $token_key);
				continue 2;

			case T_TRAIT:
				$this->class_braces[] = $this->braces;
				do $token = next($tokens); while ($token[0] !== T_STRING);
				$this->class = $this->reference(T_DECLARE_TRAIT, $token, key($tokens));
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_PRIVATE:
			case T_PROTECTED:
			case T_PUBLIC:
			case T_VAR:
				do $token = next($tokens); while (!in_array($token[0], self::VARIABLE_TOKENS, true));
				if (in_array($token[0], [T_CONST, T_FUNCTION], true)) {
					prev($tokens);
					continue 2;
				}
				while ($token[0] !== T_VARIABLE) {
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						$this->reference(T_VARIABLE, $token, key($tokens));
					}
					$token = next($tokens);
				}

				$back = 0;
				do {
					$token = prev($tokens);
					$back ++;
				} while (!in_array($token[0], [T_DOC_COMMENT, '{', '}', ';'], true));
				$doc_comment = ($token[0] === T_DOC_COMMENT) ? $token[1] : '';
				$token_key   = key($tokens);
				while ($back --) next($tokens);
				$start = strpos($doc_comment, '* @var ');
				if (!$start) {
					continue 2;
				}
				$start += 7;
				$token  = [null, null, $token[2]];
				while (str_contains("\t ", $doc_comment[$start])) $start ++;
				$stop = $start;
				while (!str_contains("\n\r\t ", $doc_comment[$stop])) {
					$stop ++;
					if (!str_contains("\n\r\t |&()", $doc_comment[$stop])) {
						continue;
					}
					$token[1] = rtrim(substr($doc_comment, $start, $stop - $start), '[]');
					$start = $stop + 1;
					if (($token[1] === '') || in_array($token[1], self::BASIC_TYPES)) {
						continue;
					}
					if     (str_starts_with($token[1], '\\')) $token[0] = T_NAME_FULLY_QUALIFIED;
					elseif (str_contains($token[1], '\\'))    $token[0] = T_NAME_QUALIFIED;
					else                                      $token[0] = T_STRING;
					$this->reference(T_VARIABLE, $token, $token_key);
				}
				continue 2;

			case T_USE:

				// class|trait T_USE
				if ($this->class_braces) {
					do {
						do $token = next($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
						$this->reference(T_USE, $token, key($tokens));
						$depth = 0;
						do {
							$token = next($tokens);
							switch ($token[0]) {
								case '{':
									$depth ++;
									break;
								case '}':
									$depth --;
									break;
								case T_INSTEADOF:
									do $token = next($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
									$this->reference(T_INSTEADOF, $token, key($tokens));
									$token = next($tokens);
									break;
								case T_PAAMAYIM_NEKUDOTAYIM:
									do $token = prev($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
									$this->reference(T_STATIC, $token, key($tokens));
									do $token = next($tokens); while ($token[0] !== T_PAAMAYIM_NEKUDOTAYIM);
							}
						} while ($depth || is_array($token) || !str_contains(',;}', $token));
					} while (is_array($token) || !str_contains(';}', $token));
					continue 2;
				}

				// namespace T_USE
				$prefix = $use = '';
				do {
					$token = next($tokens);
					if (in_array($token[0], self::CLASS_TOKENS, true)) {
						$use = ltrim($token[1], '\\');
					}
					else switch ($token[0]) {
						case T_AS:
							do $token = next($tokens); while ($token[0] !== T_STRING);
							$this->namespace_use[$token[1]] = $prefix . $use;
							$use = '';
							break;
						case T_NS_SEPARATOR:
							$use .= $token[1];
							break;
						case '{':
							$prefix = $use;
							break;
						case '}':
						case ',':
						case ';':
							if ($use !== '') {
								$key = str_contains($use, '\\') ? substr($use, strrpos($use, '\\') + 1) : $use;
								$this->namespace_use[$key] = $prefix . $use;
							}
							if ($token[0] === '}') {
								$prefix = $use = '';
							}
							break;
					}
				} while ($token !== ';');

		}
	}

	//------------------------------------------------------------------------------------- reference
	protected function reference(int $type, array|string $token, int $token_key) : string
	{
		switch ($token[0]) {
			case T_NAME_FULLY_QUALIFIED:
				$name = ltrim($token[1], '\\');
				break;
			case T_NAME_QUALIFIED:
				$use  = $this->namespace_use[substr($token[1], 0, $slash = strpos($token[1], '\\'))] ?? '';
				$name = $use
					? ($use . substr($token[1], $slash))
					: ltrim($this->namespace . '\\' . $token[1], '\\');
				break;
			case T_STRING:
				$name = ($this->namespace_use[$token[1]] ?? false)
					?: ltrim($this->namespace . '\\' . $token[1], '\\');
		}
		/** @noinspection PhpUndefinedVariableInspection Call it with a $token[0] value into switch */
		$reference = [$this->class, $type, $name, $token[2], $token_key];
		if (
			($type === T_CLASS)
			&& ($this->attribute !== '')
			&& (($this->parentheses - 1) === ($this->attribute_parentheses))
		) {
			$reference[1] = $this->attribute;
		}
		if (
			!$this->class
			&& (
				($this->attribute_brackets >= 0)
				|| in_array($type, [T_DECLARE_CLASS, T_DECLARE_INTERFACE, T_DECLARE_TRAIT], true)
			)
		) {
			$this->next_references[] = $reference;
		}
		else {
			$this->references[] = $reference;
		}
		return $name;
	}

	//------------------------------------------------------------------------------------------ scan
	public function scan(array $tokens) : void
	{
		$this->attribute             = '';
		$this->attribute_brackets    = -1;
		$this->attribute_parentheses = -1;
		$this->braces                = 0;
		$this->brackets              = 0;
		$this->class                 = '';
		$this->class_braces          = [];
		$this->namespace             = '';
		$this->namespace_braces      = -1;
		$this->namespace_use         = [];
		$this->next_references       = [];
		$this->parentheses           = 0;
		$this->references            = [];

		$token = reset($tokens);
		while (current($tokens)) {
			while ($token && ($token[0] !== T_OPEN_TAG)) {
				$token = next($tokens);
			}
			$this->phpBlock($tokens);
		}
		if ($this->next_references) {
			$this->appendNextReferences();
		}
	}

}
