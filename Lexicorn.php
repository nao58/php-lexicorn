<?php

/**
 * Lexicorn - Parse lexicornized data for PHP API
 *
 * PHP versions 5
 *
 * @author    Naohiko MORI <naohiko.mori@gmail.com>
 * @copyright 2009 Naohiko MORI <naohiko.mori@gmail.com>
 * @license   Dual licensed under the MIT and GPL licenses.
 */

class Lexicorn
{
	private $_root;
	private $_tree;
	private $_urls;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $root root directory of lexicorn data
	 * @param string $lang target language
	 */
	public function __construct($root, $lang=null)
	{
		$this->_root = $root;
		if($lang===null){
			$conf = parse_ini_file($root.'/lxconf.ini', true);
			$lang = $conf['general']['base_lang'];
		}
		$lxn = $root."/.lexicorn/tree.{$lang}";
		if(($tree = self::_getData($lxn))===false){
			throw new Exception("No tree file for '{$lang}'");
		}
		$this->_tree = $tree;
		$this->_urls = self::_getData($root.'/.lexicorn/urls');
	}

	/**
	 * Get json data from file system
	 *
	 * @access private
	 * @param string $file full path to data file
	 * @return any json decoded value
	 */
	private static function _getData($file)
	{
		if($cont = self::_getContent($file)){
			$data = json_decode($cont);
		}
		return $data;
	}

	/**
	 * Return text content from file system
	 *
	 * @access private
	 * @param string $file full path to data file
	 * @return string content
	 */
	private static function _getContent($file)
	{
		if(file_exists($file)){
			$data = gzinflate(file_get_contents($file));
		}else{
			$data = false;
		}
		return $data;
	}

	/**
	 * Return node data by url
	 *
	 * @access public
	 * @param string $url specific url
	 * @return object node object (return false if url is not found)
	 */
	public function getNode($url)
	{
		$nodes = $this->getPathByUrl($url);
		if($nodes===false) return false;
		$ret = $this->_tree;
		foreach($nodes as $node){
			if(!$node) continue;
			if(isset($ret->children->$node)){
				$ret = $ret->children->$node;
			}else{
				$ret = false;
				break;
			}
		}
		return $ret;
	}

	/**
	 * Return path string by url
	 *
	 * @access public
	 * @param string $url target url
	 * @return string file system path
	 */
	public function getPathByUrl($url)
	{
		if(substr($url, -1)!='/') $url .= '/';
		$ret = false;
		if(isset($this->_urls->$url)){
			$ret = $this->_urls->$url;
		}
		return $ret;
	}

	/**
	 * Return node object with page content
	 *
	 * @access public
	 * @param string $url target url
	 * @return object node object with page content
	 */
	public function getNodeWithContent($url)
	{
		if($node = $this->getNode($url)){
			$text = self::_getContent($this->_root.'.lexicorn/pages'.$node->path.$node->lxn);
			$node->content = $text;
		}
		return $node;
	}

	/**
	 * Return generated index converter object
	 *
	 * @access public
	 * @param object $gen reference ob generator object
	 * @param string $url target url
	 * @param int $maxdepth max number of traversing depth
	 * @return bool true if it succeed
	 */
	public function createIndex(Lexicorn_IndexConverter_Interface &$gen, $url='/', $maxdepth=0)
	{
		if(($tree = $this->getNode($url))===false){
			return false;
		}
		$depth = 1;
		$gen->initialize();
		self::_traverseIndex($gen, $tree, $depth, $maxdepth);
		$gen->finalize();
		return true;
	}

	/**
	 * Call converter methods recursively
	 *
	 * @access private
	 * @param object $gen reference of parser object
	 * @param object $tree target node
	 * @param int $depth current depth by target node
	 * @param int $maxdepth max number of depth from target node
	 */
	private static function _traverseIndex(&$gen, $tree, $depth, $maxdepth)
	{
		$gen->item($depth, $tree->prop, $tree->url);
		if($tree->children){
			if($maxdepth == 0 or $depth < $maxdepth){
				$depth++;
				$gen->down();
				foreach($tree->children as $child){
					self::_traverseIndex($gen, $child, $depth, $maxdepth);
				}
				$gen->up();
			}
		}
	}
}
