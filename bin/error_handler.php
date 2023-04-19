<?php
namespace ITRocks\Depend;

//-------------------------------------------------------------------------------------- error_type
function error_type(int $errno) : string
{
	return match($errno) {
		E_ERROR             => 'Error',
		E_WARNING           => 'Warning',
		E_PARSE             => 'Parse Error',
		E_NOTICE            => 'Notice',
		E_CORE_ERROR        => 'Core Error',
		E_CORE_WARNING      => 'Core Warning',
		E_COMPILE_ERROR     => 'Compile Error',
		E_COMPILE_WARNING   => 'Compile Warning',
		E_USER_ERROR        => 'User Error',
		E_USER_WARNING      => 'User Warning',
		E_USER_NOTICE       => 'User Notice',
		E_STRICT            => 'Strict Notice',
		E_RECOVERABLE_ERROR => 'Recoverable Error',
		default             => "Unknown error ($errno)"
	};
}

set_error_handler(function(int $errno, string $error, string $file, int $line) {
	echo error_type($errno) . ': ' . $error . ' in ' . $file . ' on line ' . $line . "\n";
	foreach (array_slice(debug_backtrace(), 1) as $key => $trace) {
		echo '#' . $key . ' ' . $trace['file'] . '(' . $trace['line'] . '): ' . $trace['function'];
		$trace['args'] = array_map(function($arg) {
			return match(gettype($arg)) {
				'array', 'object', 'resource' => ucfirst(gettype($arg)),
				'resource (closed)' => 'Resource',
				'double', 'integer' => $arg,
				'boolean' => $arg ? 'true' : 'false',
				'string'  => "'" . (str_contains($arg, "\n") ? (substr($arg, strpos($arg, "\n")) . '...') : $arg) . "'",
				'NULL'    => 'null',
				default   => 'Unknown'
			};
		}, $trace['args']);
		echo '(' . join(', ', $trace['args']) . ")\n";
	}
	echo "\n";
});
