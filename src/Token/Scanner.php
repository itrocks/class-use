<?php
namespace ITRocks\Class_Use\Token;

class Scanner
{

	//----------------------------------------------------------------------------- TESTED VALUE SETS
	protected const BASIC_TYPES = ['array', 'bool', 'callable', 'false', 'float', 'int', 'null', 'object', 'string', 'true', 'void'];
	protected const PARENT      = 'parent';
	protected const SELF        = 'self';
	protected const STATIC      = 'static';

	//------------------------------------------------------------------------------------ TOKEN SETS
	protected const CLASS_NAME_END    = [T_EXTENDS, T_IMPLEMENTS, T_STRING, '{'];
	protected const CLASS_TOKENS      = [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_STRING];
	protected const IGNORE            = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
	protected const VISIBILITY_TOKENS = [T_CONST, T_FUNCTION, ...self::CLASS_TOKENS, T_VARIABLE];

	//------------------------------------------------------------------------------------ $attribute
	protected string $attribute;

	//---------------------------------------------------------------------- $attribute_bracket_depth
	protected int $attribute_bracket_depth;

	//----------------------------------------------------------------------- $attribute_square_depth
	protected int $attribute_square_depth;

	//-------------------------------------------------------------------------------- $bracket_depth
	protected int $bracket_depth;

	//---------------------------------------------------------------------------------------- $class
	protected string $class;

	//--------------------------------------------------------------------------------- $class_depths
	/** @var int[] */
	protected array $class_depths;

	//---------------------------------------------------------------------------------- $curly_depth
	protected int $curly_depth;

	//------------------------------------------------------------------------------------ $namespace
	protected string $namespace;

	//------------------------------------------------------------------------------ $namespace_depth
	protected int $namespace_depth;

	//-------------------------------------------------------------------------------- $namespace_use
	/** @var string[] */
	protected array $namespace_use;

	//------------------------------------------------------------------------------ $next_references
	/** [string $class, string $use, int $type, int $line, int $token_key] */
	public array $next_references;

	//--------------------------------------------------------------------------------- $parent_token
	public array $parent_token = [];

	//----------------------------------------------------------------------------------- $references
	/** [string $class, string $use, int $type, int $line, int $token_key] */
	public array $references;

	//--------------------------------------------------------------------------------- $square_depth
	protected int $square_depth;

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
				$this->bracket_depth ++;
				continue 2;

			case ')':
				$this->bracket_depth --;
				continue 2;

			case T_CURLY_OPEN:
			case '{':
				$this->curly_depth ++;
				continue 2;

			case '}':
				$this->curly_depth --;
				if ($this->curly_depth === end($this->class_depths)) {
					array_pop($this->class_depths);
					$this->class        = '';
					$this->parent_token = [];
					continue 2;
				}
				if ($this->curly_depth === $this->namespace_depth) {
					$this->namespace       = '';
					$this->namespace_depth = -1;
					$this->namespace_use   = [];
				}
				continue 2;

			case '[':
				$this->square_depth ++;
				continue 2;

			case ']':
				$this->square_depth --;
				if ($this->square_depth === $this->attribute_square_depth) {
					$this->attribute               = '';
					$this->attribute_bracket_depth = -1;
					$this->attribute_square_depth  = -1;
				}
				continue 2;

			case T_ATTRIBUTE:
				$this->attribute_bracket_depth = $this->bracket_depth;
				$this->attribute_square_depth  = $this->square_depth;
				$this->square_depth ++;
				continue 2;

			case T_CLASS:
				$this->class_depths[] = $this->curly_depth;
				do $token = next($tokens); while (!in_array($token[0], self::CLASS_NAME_END, true));
				if ($token[0] === T_STRING) {
					$this->class = $this->reference(T_DECLARE_CLASS, $token, key($tokens));
				}
				else {
					prev($tokens);
				}
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_CLOSE_TAG:
				next($tokens);
				return;

			case T_CONST:
				// skip constant name as it may be a reserved word token (e.g. T_USE for constant USE)
				do $token = next($tokens); while ($token[0] !== ';');
				continue 2;

			case T_EXTENDS:
				do $token = next($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
				/** @noinspection PhpFieldAssignmentTypeMismatchInspection $token is an array here */
				$this->parent_token = $token;
				$this->reference(T_EXTENDS, $token, key($tokens));
				continue 2;

			case T_IMPLEMENTS:
				do {
					$token = next($tokens);
					if (in_array($token[0], self::CLASS_TOKENS, true)) {
						$this->reference(T_IMPLEMENTS, $token, key($tokens));
					}
				} while ($token[0] !== '{');
				$this->curly_depth ++;
				continue 2;

			case T_INTERFACE:
				$this->class_depths[] = $this->curly_depth;
				do $token = next($tokens); while ($token[0] !== T_STRING);
				$this->class = $this->reference(T_DECLARE_INTERFACE, $token, key($tokens));
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_TRAIT:
				$this->class_depths[] = $this->curly_depth;
				do $token = next($tokens); while ($token[0] !== T_STRING);
				$this->class = $this->reference(T_DECLARE_TRAIT, $token, key($tokens));
				if ($this->next_references) $this->appendNextReferences();
				continue 2;

			case T_FUNCTION:
				if ($this->next_references) $this->appendNextReferences();
				do $token = next($tokens); while ($token !== '(');
				while (($token = next($tokens)) !== ')') {
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						$this->reference(T_ARGUMENT, $token, key($tokens));
						$token = next($tokens);
					}
					if ($token[0] !== T_VARIABLE) {
						continue;
					}
					do $token = next($tokens); while (is_array($token) || !str_contains(',)', $token));
					if ($token === ')') {
						break;
					}
				}
				do $token = next($tokens); while (is_array($token) || !str_contains(':{;', $token));
				while (is_array($token) || !str_contains('{;', $token)) {
					if (
						in_array($token[0], self::CLASS_TOKENS, true)
						&& !in_array($token[1], self::BASIC_TYPES, true)
					) {
						$this->reference(T_RETURN, $token, key($tokens));
					}
					$token = next($tokens);
				}
				if ($token === '{') {
					$this->curly_depth ++;
				}
				continue 2;

			case T_INSTANCEOF:
			case T_NEW:
				$type = $token[0];
				do $token = next($tokens); while (in_array($token[0], self::IGNORE, true));
				if (in_array($token[0], self::CLASS_TOKENS, true)) {
					$this->reference($type, $token, key($tokens));
				}
				elseif (is_string($token) && str_contains('{([])}', $token)) {
					prev($tokens);
				}
				continue 2;

			case T_NAME_QUALIFIED:
			case T_NAME_FULLY_QUALIFIED:
			case T_STRING:
				if ($this->attribute_bracket_depth === $this->bracket_depth) {
					$this->attribute = '';
					$this->attribute = $this->reference(T_ATTRIBUTE, $token, key($tokens));
				}
				continue 2;

			case T_NAMESPACE:
				do $token = next($tokens); while (in_array($token[0], self::IGNORE, true));
				$this->reference(T_NAMESPACE, [T_NAME_FULLY_QUALIFIED, $token[1], $token[2]], key($tokens));
				$this->namespace = $token[1];
				do $token = next($tokens); while (is_array($token) || !str_contains(';{', $token));
				if ($token === '{') {
					$this->namespace_depth = $this->curly_depth;
					$this->curly_depth ++;
				}
				continue 2;

			case T_PAAMAYIM_NEKUDOTAYIM:
				do $token = prev($tokens); while (in_array($token[0], self::IGNORE, true));
				$token_key = in_array($token, ['}', ')'], true) ? null : key($tokens);
				do $token = next($tokens); while ($token[0] !== T_PAAMAYIM_NEKUDOTAYIM);
				do $token = next($tokens); while (in_array($token[0], self::IGNORE, true));
				if (!isset($token_key)) {
					continue 2;
				}
				$type  = ($token[0] === T_CLASS) ? T_CLASS : T_STATIC;
				$token = $tokens[$token_key];
				if ($token[1] === self::PARENT) {
					if ($this->parent_token) {
						$token[0] = $this->parent_token[0];
						$token[1] = $this->parent_token[1];
					}
					else {
						$token = [];
					}
				}
				elseif (in_array($token[1], [self::SELF, self::STATIC], true)) {
					$token[0] = T_NAME_FULLY_QUALIFIED;
					$token[1] = $this->class;
				}
				if ($token) {
					$this->reference($type, $token, $token_key);
				}
				continue 2;

			case T_USE:
				// class|trait T_USE
				if ($this->class_depths) {
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
				do {
					do $token = next($tokens); while (!in_array($token[0], self::CLASS_TOKENS, true));
					$use = ltrim($token[1], '\\');
					$this->reference(
						T_NAMESPACE_USE, [T_NAME_FULLY_QUALIFIED, $token[1], $token[2]], key($tokens)
					);
					while ($token = next($tokens)) switch ($token[0]) {
						case T_AS:
							do $token = next($tokens); while ($token[0] !== T_STRING);
							$this->namespace_use[$token[1]] = $use;
							do $token = next($tokens); while (is_array($token) || !str_contains(',;', $token));
							break 2;
						case ',':
						case ';':
							$this->namespace_use[substr($use, strrpos($use, '\\') + 1)] = $use;
							break 2;
					}
				} while ($token !== ';');
				continue 2;

			case T_PRIVATE:
			case T_PROTECTED:
			case T_PUBLIC:
			case T_VAR:
				do $token = next($tokens); while (!in_array($token[0], self::VISIBILITY_TOKENS, true));
				if ($token[0] === T_FUNCTION) {
					prev($tokens);
					continue 2;
				}
				if (
					in_array($token[0], self::CLASS_TOKENS, true)
					&& !in_array($token[1], self::BASIC_TYPES, true)
				) {
					$this->reference(T_VAR, $token, key($tokens));
					continue 2;
				}
				$doc_comment = '';
				$line        = $token[2];
				$back        = 0;
				while (is_array($token) || !str_contains('{;', $token)) {
					if ($token[0] === T_DOC_COMMENT) {
						$doc_comment = $token[1] . $doc_comment;
						$line        = $token[2];
					}
					$token = prev($tokens);
					$back  ++;
				}
				$token = [null, null, $line];
				if ($start = strpos($doc_comment, '* @var ')) {
					$start += 7;
					while (str_contains("\n\r\t *", $doc_comment[$start])) $start ++;
					$stop = $start;
					do {
						$stop ++;
						if (!str_contains("\n\r\t *|&", $doc_comment[$stop])) {
							continue;
						}
						$token[1] = rtrim(substr($doc_comment, $start, $stop - $start), '[]');
						$start    = $stop + 1;
						if ($token[1] === '') {
							continue;
						}
						if     (str_starts_with($token[1], '\\')) $token[0] = T_NAME_FULLY_QUALIFIED;
						elseif (str_contains($token[1], '\\'))    $token[0] = T_NAME_QUALIFIED;
						else                                      $token[0] = T_STRING;
						$this->reference(T_VAR, $token, key($tokens));
					} while (str_contains("|&", $doc_comment[$stop]));
				}
				while ($back--) next($tokens);
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
				break;
			default:
				return '';
		}
		$reference = [$this->class, $name, $type, $token[2], $token_key];
		if ($this->attribute !== '') {
			$reference[2] = $this->attribute;
		}
		if (
			!$this->class
			&& (
				($this->attribute_square_depth >= 0)
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
	public function scan(array &$tokens) : void
	{
		$this->attribute               = '';
		$this->attribute_bracket_depth = -1;
		$this->attribute_square_depth  = -1;
		$this->bracket_depth           = 0;
		$this->class                   = '';
		$this->class_depths            = [];
		$this->curly_depth             = 0;
		$this->namespace               = '';
		$this->namespace_depth         = -1;
		$this->namespace_use           = [];
		$this->next_references         = [];
		$this->references              = [];
		$this->square_depth            = 0;

		$token = reset($tokens);
		while (current($tokens)) {
			while ($token && ($token[0] !== T_OPEN_TAG)) $token = next($tokens);
			$this->phpBlock($tokens);
		}
		if ($this->next_references) {
			$this->appendNextReferences();
		}
	}

}
