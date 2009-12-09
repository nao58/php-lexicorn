<?php

require_once dirname(__FILE__).'/Lexicorn.php';
require_once dirname(__FILE__).'/IndexConverter/SimpleHtml.php';
require_once dirname(__FILE__).'/IndexConverter/SimpleText.php';

$lex = new Lexicorn(dirname(dirname(__FILE__)).'/tuitter/', 'ja');

$node = $lex->getNodeWithContent('/getting-started/use-tuitter/');
var_dump($node);

$gen = new Lexicorn_IndexConverter_SimpleHtml();
$lex->createIndex($gen);
echo $gen;

echo "\n\n";
$gen = new Lexicorn_IndexConverter_SimpleText();
$lex->createIndex($gen, '/ref/', 2);
echo $gen;
