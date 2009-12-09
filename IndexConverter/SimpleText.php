<?php

require_once dirname(__FILE__).'/Interface.php';

class Lexicorn_IndexConverter_SimpleText implements Lexicorn_IndexConverter_Interface
{
	private $_text='';

	public function __toString()
	{
		return $this->_text;
	}

	public function initialize(){
	}

	public function finalize(){
	}

	public function down()
	{
	}

	public function up()
	{
	}

	public function item($depth, $prop, $url)
	{
		$this->_text .= str_repeat("\t", $depth-1).$url."\t".$prop->title."\n";
	}
}
