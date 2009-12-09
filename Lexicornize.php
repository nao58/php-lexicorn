<?php

require_once dirname(__FILE__).'/Lexicornizer.php';

$path = realpath($_SERVER['argv'][1]);
if(file_exists($path)){
	$tree = Lexicornizer::create($path);
}
