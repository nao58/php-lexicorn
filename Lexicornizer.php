<?php

/**
 * Lexicornizer - Lexicornize data to Lexicorn
 *
 * PHP versions 5
 *
 * @author    Naohiko MORI <naohiko.mori@gmail.com>
 * @copyright 2009 Naohiko MORI <naohiko.mori@gmail.com>
 * @license   Dual licensed under the MIT and GPL licenses.
 */

require_once dirname(__FILE__).'/Lib/classTextile.php';

/**
 * Lexicornizer
 */
class Lexicornizer
{
	/**
	 * Lexicornize data
	 *
	 * @access public
	 * @param string $root root directory of lexicornzing data
	 */
	public static function create($root)
	{
		if(!file_exists($root)){
			throw new Exception("The root directory '{$root}' does not exists.");
		}

		$ctrl = $root.'/.lexicorn';

		$conf = parse_ini_file($root.'/lxconf.ini', true);
		
		$base_lang = $conf['general']['base_lang'];
		$base_tree = self::_traverse($root, '/', $base_lang);
		foreach($conf['lang'] as $lang => $lang_name){
			if($lang == $base_lang){
				$tree = $base_tree;
			} else {
				$tree = self::_i18n($base_tree, $root, $lang);
			}
			self::_writeCtrlFile($ctrl."/tree.{$lang}", $tree);
			self::_createPageCache($root, $tree);
		}

		$urls = array();
		self::_createUrls($urls, $tree);
		self::_writeCtrlFile($ctrl.'/urls', $urls);		
	}

	/**
	 * Write data to file system
	 *
	 * @access private
	 * @param string $file full path of writing file
	 * @param any $data you can put any data
	 */
	private static function _writeCtrlFile($file, $data)
	{
		$dir = dirname($file);
		if(!file_exists($dir)){
			mkdir($dir, 0755, true);
		}
		if(is_array($data)){
			$data = json_encode($data);
		}
		file_put_contents($file, gzdeflate($data));
	}

	/**
	 * Traverse directories to lexicornize
	 *
	 * @access private
	 * @param string $root path to root directory
	 * @param string $path path to current directory
	 * @param string $lang target language
	 * @return array properties array
	 */
	private static function _traverse($root, $path, $lang)
	{
		$dirs = array();
		$files = array();
		$lxn = '';
		if($h = opendir($root.$path)){
			while(false !== ($f = readdir($h))){
				if(substr($f, 0, 1)!='.'){
					if(is_dir($root.$path.$f)){
						$dirs[$f] = $f;
					}else if(substr($f, -4)=='.lxn'){
						if($f == $lang.'.lxn') $lxn = $f;
					}else{
						$files[$f] = $f;
					}
				}
			}
			closedir($h);
			ksort($dirs);
		}
		$children = array();
		foreach($dirs as $dir){
			if($child = self::_traverse($root, $path.$dir.'/', $lang)){
				$children[$dir] = $child;
			}
		}
		if(!$lxn and !$children){
			$ret = false;
		}else{
			$ret = array(
				'path' => $path,
				'url' => self::_createUrl($path),
				'lxn' => $lxn,
				'prop' => self::_getProperties($root.$path, $lxn),
				'files' => $files,
				'child_count' => count($children),
				'children' => $children
				);
		}
		return $ret;
	}

	/**
	 * Return node properties
	 *
	 * @access private
	 * @param string $path file system path
	 * @param string $lxn target lxn file name
	 * @return object generated properties
	 */
	private static function _getProperties($path, $lxn)
	{
		$base = basename($path);
		$def_prop = array(
			'title' => $base
			);
		$lines = file($path.'/'.$lxn, FILE_IGNORE_NEW_LINES | FILE_TEXT);
		$lang = basename($lxn, '.lxn');
		$lang_prop = $def_prop;
		foreach($lines as $line){
			if(!$line) break;
			if($sp = strpos($line, ':')){
				$key = strtolower(trim(substr($line, 0, $sp)));
				$val = trim(substr($line, $sp+1));
				$lang_prop[$key] = $val;
			}
		}
		return $lang_prop;
	}

	/**
	 * Internationalization
	 *
	 * @param object $tree target node array
	 * @param string $root root directory of lexicorn data
	 * @param string $lang target language
	 * @return object Internationalized properties
	 */
	private static function _i18n($tree, $root, $lang)
	{
		$path = $root.$tree['path'];
		$lxn = $lang.'.lxn';
		if(file_exists($path.'/'.$lxn)){
			$tree['lxn'] = $lxn;
			$tree['prop'] = self::_getProperties($path, $lxn);
		}
		foreach($tree['children'] as $key => $child){
			$tree['children'][$key] = self::_i18n($child, $root, $lang);
		}
		return $tree;
	}

	/**
	 * Return created url from path
	 *
	 * @access private
	 * @param string $path file system path from root directory
	 * @return string created url
	 */
	private static function _createUrl($path)
	{
		$pathes = explode('/', $path);
		foreach($pathes as $inx => $path){
			if(preg_match('/^[0-9]+_(.+)$/', $path, $m)){
				$pathes[$inx] = $m[1];
			}
		}
		return implode('/', $pathes);
	}

	/**
	 * Create page contents cache
	 *
	 * @access private
	 * @param string $root root directory of lexicorn data
	 * @param array $tree target node
	 */
	private static function _createPageCache($root, $tree)
	{
		if(isset($tree['lxn'])){
			$file = $tree['path'].$tree['lxn'];
			$lines = file($root.$file, FILE_IGNORE_NEW_LINES | FILE_TEXT);
			$body = false;
			$text = '';
			foreach($lines as $line){
				if($body){
					$text .= $line."\n";
				}
				if(!$line) $body = true;
			}
			if($text){
				$textile = new Textile();
				$text = $textile->TextileThis($text);
				self::_writeCtrlFile($root.'/.lexicorn/pages'.$file, $text);
			}
		}
		foreach($tree['children'] as $child){
			self::_createPageCache($root, $child);
		}
	}

	/**
	 * Create urls file
	 *
	 * @access private
	 * @param object $urls reference of urls array
	 * @param array $tree target node
	 */
	private static function _createUrls(&$urls, $tree)
	{
		$url = $tree['url'];
		if(isset($urls[$url])){
			throw new Exception("duplicated url '{$url}'");
		}
		$urls[$url] = explode('/', trim($tree['path'], '/'));
		foreach($tree['children'] as $child){
			self::_createUrls($urls, $child);
		}
	}
}
