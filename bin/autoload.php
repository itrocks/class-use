<?php
include __DIR__ . '/../src/tokens.php';

spl_autoload_register(function(string $class_name) {
	include __DIR__ . '/../src/' . str_replace('\\', '/', substr($class_name, 18)) . '.php';
});
