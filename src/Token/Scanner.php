<?php
namespace ITRocks\Class_Use\Token;

class Scanner
{

	//----------------------------------------------------------------------------------- BASIC_TYPES
	protected const BASIC_TYPES = [
		'array', 'bool', 'callable', 'false', 'float', 'int', 'null', 'object', 'string', 'true', 'void'
	];

	//------------------------------------------------------------------------------------ TOKEN SETS
	protected const CLASS_TOKENS    = [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE, T_STRING];
	protected const IGNORE_TOKENS   = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
	protected const VARIABLE_TOKENS = [T_CONST, T_FUNCTION, ...self::CLASS_TOKENS, T_VARIABLE];

	//-------------------------------------------------------------------------------- IGNORE_CLASSES
	protected const IGNORE_CLASSES = ['__CLASS__', '__TRAIT__', 'parent', 'self', 'static'];

	//------------------------------------------------------------------------------------ $attribute
	protected string $attribute = '';

	//--------------------------------------------------------------------------- $attribute_brackets
	protected int $attribute_brackets = -1;

	//------------------------------------------------------------------------ $attribute_parentheses
	protected int $attribute_parentheses = -1;

	//--------------------------------------------------------------------------------------- $braces
	protected int $braces = 0;

	//------------------------------------------------------------------------------------- $brackets
	protected int $brackets = 0;

	//---------------------------------------------------------------------------------------- $class
	protected string $class = '';

	//--------------------------------------------------------------------------------- $class_braces
	/** @var int[] */
	protected array $class_braces = [];

	//------------------------------------------------------------------------------------ $namespace
	protected string $namespace = '';

	//----------------------------------------------------------------------------- $namespace_braces
	protected int $namespace_braces = -1;

	//-------------------------------------------------------------------------------- $namespace_use
	/** @var string[] */
	protected array $namespace_use = [];

	//------------------------------------------------------------------------------ $next_references
	/**
	 * @var array< array{ string, int|string, string, int, int } >
	 * [[string $class, int|string $type, string $use, int $line, int $token_key]]
	 */
	public array $next_references = [];

	//---------------------------------------------------------------------------------- $parentheses
	protected int $parentheses = 0;

	//----------------------------------------------------------------------------------- $references
	/**
	 * @var array<array{string,int|string,string,int,int}>
	 * [[string $class, int|string $type, string $use, int $line, int $token_key]]
	 */
	public array $references = [];

	//-------------------------------------------------------------------------- appendNextReferences
	protected function appendNextReferences() : void
	{
		if ($this->class === '') {
			$this->references = array_merge($this->references, $this->next_references);
		}
		else foreach ($this->next_references as $reference) {
			$reference[0]       = $this->class;
			$this->references[] = $reference;
		}
		$this->next_references = [];
	}

	//-------------------------------------------------------------------------------------- phpBlock
	/**
	 * @param array<int,array{int,string,int}|string> $tokens
	 * <int $token_key, $tokens {int $token_index, string $content, int $line} | string $character>>
	 */
	protected function phpBlock(array &$tokens) : void
	{
		while (($token = next($tokens)) !== false) switch ($token[0]) {

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
			case T_INTERFACE:
				$this->class_braces[] = $this->braces;
				$type = ($token[0] === T_CLASS) ? T_DECLARE_CLASS : T_DECLARE_INTERFACE;
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while ($token[0] !== T_STRING);
				/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
				$this->class = $this->reference($type, $token, key($tokens));
				if ($this->next_references !== []) $this->appendNextReferences();
				continue 2;

			case T_CLOSE_TAG:
				next($tokens);
				return;

			case T_CONST:
				// skip constant name as it may be a reserved word token (e.g. T_USE for constant USE)
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while ($token[0] !== '=');
				continue 2;

			case T_EXTENDS:
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (!in_array($token[0], self::CLASS_TOKENS, true));
				/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
				$this->reference(T_EXTENDS, $token, key($tokens));
				continue 2;

			case T_FUNCTION:
				if ($this->next_references !== []) $this->appendNextReferences();
				do $token = next($tokens); while ($token !== '(');
				$depth = 1;
				do {
					$token = next($tokens);
					if   ($token === false) return;
					elseif ($token === '(') $depth ++;
					elseif ($token === ')') $depth --;
					elseif (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
						$this->reference(T_ARGUMENT, $token, key($tokens));
					}
				} while ($depth);
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (is_array($token) || !str_contains(':{;', $token));
				while (is_array($token) || !str_contains('{;', $token)) {
					$token = next($tokens);
					if ($token === false) return;
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
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
					if ($token === false) return;
					if (in_array($token[0], self::CLASS_TOKENS, true)) {
						/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
						$this->reference(T_IMPLEMENTS, $token, key($tokens));
					}
				} while ($token[0] !== '{');
				$this->braces ++;
				continue 2;

			case T_INSTANCEOF:
			case T_NEW:
				/** @var int<283,284> $type */
				$type = $token[0];
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (in_array($token[0], self::IGNORE_TOKENS, true));
				if (in_array($token[0], self::CLASS_TOKENS, true)) {
					/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
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
					/** @var array{int,string,int} $token {int $token_index, string $content, int $line} */
					/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
					$this->attribute = $this->reference(T_ATTRIBUTE, $token, key($tokens));
				}
				continue 2;

			case T_NAMESPACE:
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (in_array($token[0], self::IGNORE_TOKENS, true));
				$this->namespace = $token[1];
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (is_array($token) || !str_contains(';{', $token));
				if ($token === '{') {
					$this->namespace_braces = $this->braces;
					$this->braces ++;
				}
				continue 2;

			case T_PAAMAYIM_NEKUDOTAYIM:
				do {
					$token = prev($tokens);
					if ($token === false) return;
				} while (in_array($token[0], self::IGNORE_TOKENS, true));
				$ignore = !in_array($token[0], self::CLASS_TOKENS, true)
					|| in_array($token[1], self::IGNORE_CLASSES, true);
				/** @var int $token_key key($tokens) valid: last prev($tokens) not false */
				$token_key = key($tokens);
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while ($token[0] !== T_PAAMAYIM_NEKUDOTAYIM);
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (in_array($token[0], self::IGNORE_TOKENS, true));
				if ($ignore) {
					continue 2;
				}
				$type  = ($token[0] === T_CLASS) ? T_CLASS : T_STATIC;
				/** @var array{int,string,int} $token {int $token_index, string $content, int $line} */
				$token = $tokens[$token_key];
				$this->reference($type, $token, $token_key);
				continue 2;

			case T_TRAIT:
				$this->class_braces[] = $this->braces;
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while ($token[0] !== T_STRING);
				/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
				$this->class = $this->reference(T_DECLARE_TRAIT, $token, key($tokens));
				if ($this->next_references !== []) $this->appendNextReferences();
				continue 2;

			case T_PRIVATE:
			case T_PROTECTED:
			case T_PUBLIC:
			case T_VAR:
				do {
					$token = next($tokens);
					if ($token === false) return;
				} while (!in_array($token[0], self::VARIABLE_TOKENS, true));
				if (in_array($token[0], [T_CONST, T_FUNCTION], true)) {
					prev($tokens);
					continue 2;
				}
				while ($token[0] !== T_VARIABLE) {
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
						$this->reference(T_VARIABLE, $token, key($tokens));
					}
					$token = next($tokens);
					if ($token === false) return;
				}

				$back = 0;
				do {
					$token = prev($tokens);
					if ($token === false) return;
					$back ++;
				} while (!in_array($token[0], [T_DOC_COMMENT, '{', '}', ';'], true));
				/** @var int $token_key The next/prev play/replay sequence is valid */
				$token_key = key($tokens);
				while ($back--) next($tokens);
				if ($token[0] !== T_DOC_COMMENT) {
					continue 2;
				}
				$doc_comment = $token[1];
				$start       = strpos($doc_comment, '* @var ');
				if ($start === false) {
					continue 2;
				}
				$start += 7;
				while (str_contains("\t ", $doc_comment[$start])) $start ++;
				$stop = $start;
				while (!str_contains("\n\r\t ", $doc_comment[$stop])) {
					$stop ++;
					if (!str_contains("\n\r\t |&()", $doc_comment[$stop])) {
						continue;
					}
					$token[1] = rtrim(substr($doc_comment, $start, $stop - $start), '[]');
					$start = $stop + 1;
					if (($token[1] === '') || in_array($token[1], self::BASIC_TYPES, true)) {
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
				if ($this->class_braces !== []) {
					do {
						do {
							$token = next($tokens);
							if ($token === false) return;
						} while (!in_array($token[0], self::CLASS_TOKENS, true));
						/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
						$this->reference(T_USE, $token, key($tokens));
						$depth = 0;
						do {
							$token = next($tokens);
							if ($token === false) return;
							switch ($token[0]) {
								case '{':
									$depth ++;
									break;
								case '}':
									$depth --;
									break;
								case T_INSTEADOF:
									do {
										$token = next($tokens);
										if ($token === false) return;
									} while (!in_array($token[0], self::CLASS_TOKENS, true));
									/** @phpstan-ignore-next-line key($tokens) valid: last next($tokens) not false */
									$this->reference(T_INSTEADOF, $token, key($tokens));
									$token = next($tokens);
									if ($token === false) return;
									break;
								case T_PAAMAYIM_NEKUDOTAYIM:
									do {
										$token = prev($tokens);
										if ($token === false) return;
									} while (!in_array($token[0], self::CLASS_TOKENS, true));
									/** @phpstan-ignore-next-line key($tokens) valid: last prev($tokens) not false */
									$this->reference(T_STATIC, $token, key($tokens));
									do {
										$token = next($tokens);
										if ($token === false) return;
									} while ($token[0] !== T_PAAMAYIM_NEKUDOTAYIM);
							}
						} while (($depth > 0) || is_array($token) || !str_contains(',;}', $token));
					} while (!str_contains(';}', $token));
					continue 2;
				}

				// namespace T_USE
				$prefix = $use = '';
				do {
					$token = next($tokens);
					if ($token === false) return;
					if (in_array($token[0], self::CLASS_TOKENS, true)) {
						$use = ltrim($token[1], '\\');
					}
					else switch ($token[0]) {
						case T_AS:
							do {
								$token = next($tokens);
								if ($token === false) return;
							} while ($token[0] !== T_STRING);
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
								$key = (($i = strrpos($use, '\\')) !== false) ? substr($use, $i + 1) : $use;
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
	/** @param array{int,string,int} $token {int $token_index, string $content, int $line} */
	protected function reference(int $type, array $token, int $token_key) : string
	{
		switch ($token[0]) {
			case T_NAME_FULLY_QUALIFIED:
				$name = ltrim($token[1], '\\');
				break;
			case T_NAME_QUALIFIED:
				$slash = strpos($token[1], '\\') ?: 0;
				$use   = $this->namespace_use[substr($token[1], 0, $slash)] ?? null;
				$name  = isset($use)
					? ($use . substr($token[1], $slash))
					: ltrim($this->namespace . '\\' . $token[1], '\\');
				break;
			case T_NAME_RELATIVE:
				$name = ltrim($this->namespace . substr($token[1], 9), '\\');
				break;
			case T_STRING:
				$name = ($this->namespace_use[$token[1]] ?? false)
					?: ltrim($this->namespace . '\\' . $token[1], '\\');
				break;
			default:
				return '';
		}
		$reference = [$this->class, $type, $name, $token[2], $token_key];
		if (
			($type === T_CLASS)
			&& ($this->attribute !== '')
			&& (($this->parentheses - 1) === ($this->attribute_parentheses))
		) {
			$reference[1] = $this->attribute;
		}
		if (
			($this->class === '')
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
	/**
	 * @param array<int,array{int,string,int}|string> $tokens
	 * <int $token_key, $tokens {int $token_index, string $content, int $line} | string $character>>
	 */
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
		if ($token === false) {
			return;
		}
		do {
			/** @phpstan-ignore-next-line bug "Cannot access offset 0 on mixed" */
			if ($token[0] === T_OPEN_TAG) {
				$this->phpBlock($tokens);
			}
			/** @phpstan-ignore-next-line bug "Parameter #1 $array of function next expects array|object, mixed given" */
			$token = next($tokens);
		} while ($token !== false);
		if ($this->next_references !== []) {
			$this->appendNextReferences();
		}
	}

}
