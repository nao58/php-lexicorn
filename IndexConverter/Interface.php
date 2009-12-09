<?php

interface Lexicorn_IndexConverter_Interface
{
	public function initialize();
	public function finalize();
	public function down();
	public function up();
	public function item($depth, $title, $url);
}
