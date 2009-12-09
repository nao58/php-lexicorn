<?php

require_once dirname(__FILE__).'/Interface.php';

class Lexicorn_IndexConverter_SimpleHtml implements Lexicorn_IndexConverter_Interface
{
	private $_html='';

	public function __toString()
	{
		return $this->_html;
	}

	public function initialize(){
		$this->_html .= '<ul>';
	}

	public function finalize(){
		$this->_html .= '</ul>';
	}

	public function down()
	{
		$this->_html .= '<li><ul>';
	}

	public function up()
	{
		$this->_html .= '</ul></li>';
	}

	public function item($depth, $prop, $url)
	{
		$this->_html .= '<li>'.$prop->title.'</li>';
	}
}
