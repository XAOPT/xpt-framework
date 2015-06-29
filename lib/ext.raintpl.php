<?php

/**
 *  RainTPL Extention
 *
 Rain272 fixes:

 replace
 $is_init_variable = preg_match( "/^[a-z_A-Z\.\[\](\-\>)]*=[^=]*$/", $extra_var );
 with
 $is_init_variable = preg_match( "/^[a-z_A-Z\.\[\](\-\>)]*(\s*)=(.*)$/", $extra_var );
 */


class RainTPL extends OriginalRainTPL {

	public $template_dir;

	function __construct($component_name = '', $type = 'component')
	{
		global $rewrite, $gLang, $gLocale;

		if ($gLocale !== DEFAULT_LOCALE)
			$this->assign('gDomain', DOMAIN.'/'.$gLocale);
		else
			$this->assign('gDomain', DOMAIN);

		$this->assign('gDefaultDomain', DOMAIN);
		$this->assign('gRewrite', $rewrite);
		$this->assign('component_name', $component_name);
		$this->assign('gADomain', ADOMAIN);
		$this->assign('gLang', $gLang);
		$this->assign('gLocale', $gLocale);
		$this->type = $type;

		$this->component_name = $component_name;
	}

	protected function _get_tpl_dir($tpl_name, $tpl_basedir)
	{
		## определяем путь к шаблону
		global $rewrite;

		if (!isset($this->template_dir))
			$this->template_dir = self::$tpl_dir . $tpl_basedir;
		else
			return $this->template_dir;

			//if (isset($this->including) && $this->including)
		//	return $this->tpl_dir;
		if ($this->type == 'core')
			return $this->template_dir . $rewrite->template .'/'. $tpl_basedir;

		if ($this->type == 'include')
			return $this->template_dir;

		if ($rewrite->admin_mod)
		{
			switch ($this->type)
			{
				case 'module':
					return "modules/".$this->component_name."/view/";
				case 'component':
				default:
					return "components/".$this->component_name."/admin/view/";
			}
		}
		else
		{
			switch ($this->type)
			{
				case 'module':
					return "modules/".$this->component_name."/view/" . $tpl_basedir;
				case 'component':
				default:
					return "components/".$this->component_name."/view/" . $tpl_basedir;
			}
		}
	}

	// check if has to compile the template
	// return true if the template has changed
	protected function check_template( $tpl_name ){

		if( !isset($this->tpl['checked']) ){

			$tpl_basename                       = basename( $tpl_name );														// template basename
			$tpl_basedir                        = strpos($tpl_name,"/") ? dirname($tpl_name) . '/' : null;						// template basedirectory
			$this->template_dir                 = self::_get_tpl_dir($tpl_name, $tpl_basedir);	// template directory
			$this->tpl['tpl_filename']          = $this->template_dir . $tpl_basename . '.' . self::$tpl_ext;	// template filename
			$temp_compiled_filename             = self::$cache_dir . $tpl_basename . "." . md5( $this->template_dir . serialize(self::$config_name_sum));
			$this->tpl['compiled_filename']     = $temp_compiled_filename . '.ztpl';	// cache filename
			$this->tpl['cache_filename']        = $temp_compiled_filename . '.s_' . $this->cache_id . '.ztpl';	// static cache filename

			// if the template doesn't exsist throw an error
			if( self::$check_template_update && !file_exists( $this->tpl['tpl_filename'] ) ){
				echo $this->tpl['tpl_filename'].' - not found <br />';
				$e = new RainTpl_NotFoundException( 'Template '. $tpl_basename .' not found!' );
				//throw $e->setTemplateFile($this->tpl['tpl_filename']);
			}

			$this->tpl['checked'] = true;

			// file doesn't exsist, or the template was updated, Rain will compile the template
			if( !ENABLE_CACHING || !file_exists( $this->tpl['compiled_filename'] ) || ( self::$check_template_update && filemtime($this->tpl['compiled_filename']) < filemtime( $this->tpl['tpl_filename'] ) ) ){
				$this->compileFile( $tpl_basename, $tpl_basedir, $this->tpl['tpl_filename'], self::$cache_dir, $this->tpl['compiled_filename'] );
				return true;
			}

		}
	}

	/**
	 * Compile template
	 * @access protected
	 */
	protected function compileTemplate( $template_code, $tpl_basedir ){

		//tag list
		$tag_regexp = array( 'loop'         => '(\{loop(?: name){0,1}="\${0,1}[^"]*"\})',
                             'loop_close'   => '(\{\/loop\})',
                             'if'           => '(\{if(?: condition){0,1}="[^"]*"\})',
                             'elseif'       => '(\{elseif(?: condition){0,1}="[^"]*"\})',
                             'else'         => '(\{else\})',
                             'if_close'     => '(\{\/if\})',
                             'function'     => '(\{function="[^"]*"\})',
                             'noparse'      => '(\{noparse\})',
                             'noparse_close'=> '(\{\/noparse\})',
                             'ignore'       => '(\{ignore\}|\{\*)',
                             'ignore_close' => '(\{\/ignore\}|\*\})',
                             'include'      => '(\{include="[^"]*"(?: cache="[^"]*")?\})',
                             'template_info'=> '(\{\$template_info\})',
                             'function'	    => '(\{function="(\w*?)(?:.*?)"\})',
							 'isset'	    => '(\{isset="[^"]*"\})',
							 'language'	    => '(\{\:.+?\})',
							);

		$tag_regexp = "/" . join( "|", $tag_regexp ) . "/";

		//split the code with the tags regexp
		$template_code = preg_split ( $tag_regexp, $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		//path replace (src of img, background and href of link)
		$template_code = $this->path_replace( $template_code, $tpl_basedir );

		//compile the code
		$template_code = $this->extendCompileCode( $template_code );
		$compiled_code = $this->compileCode( $template_code );

		//return the compiled code
		return $compiled_code;
	}

	/**
	 * Compile the code
	 * @access protected
	 */
	protected function extendCompileCode( $parsed_code ){

		foreach ($parsed_code AS &$p)
		{
			if( preg_match( '/\{isset="([^"]*)"\}/', $p, $code ) ){

				//tag
				$tag = $code[ 0 ];

				//condition attribute
				$condition = $code[ 1 ];

				//variable substitution into condition (no delimiter into the condition)
				$parsed_condition = $this->var_replace( $condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, 0);

				//if code
				$p =   "<?php if(isset($parsed_condition) ){echo $parsed_condition;} ?>";
			}
			elseif( preg_match( '/\{include="([^"]*)"(?: cache="([^"]*)"){0,1}\}/', $p, $code ) ){

				//variables substitution
				$include_var = $this->var_replace( $code[ 1 ], $left_delimiter = null, $right_delimiter = null, $php_left_delimiter = '".' , $php_right_delimiter = '."', null );

				$compiled_code = '';
				// if the cache is active
				if( isset($code[ 2 ]) ) {

					//dynamic include
					$compiled_code .= '<?php $tpl = new '.get_class($this).'("","include");' .
								 'if( $cache = $tpl->cache( $template = dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'") ) )' .
								 '	echo $cache;' .
								 'else{' .
								 //'	$tpl_dir_temp = self::$tpl_dir;' .
								 //'	$tpl->check_template;' .
								 '	$tpl->assign( $this->var );' .
								//	( !$loop_level ? null : '$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );' ).
								 '	$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'"), false );'.
								 '} ?>';
				}
				else {

					//dynamic include
					$compiled_code .= '<?php $tpl = new '.get_class($this).'("","include");' .
									  '$tpl_dir_temp = self::$tpl_dir;' .
									  '$tpl->assign( $this->var );' .
									 // ( !$loop_level ? null : '$tpl->assign( "key", $key'.$loop_level.' ); $tpl->assign( "value", $value'.$loop_level.' );' ).
									  '$tpl->draw( dirname("'.$include_var.'") . ( substr("'.$include_var.'",-1,1) != "/" ? "/" : "" ) . basename("'.$include_var.'"), false );'.
									  '?>';


				}
				$p = $compiled_code;
			}
			elseif( preg_match( '/\{\:(.*?)\}/', $p, $code ) ){
				$tag = $code[ 0 ];
				$condition = $code[ 1 ];
				$p =   '<?php if(isset($gLang["' .$condition. '"]) ){echo $gLang["' .$condition. '"];} ?>';
			}
		}

		return $parsed_code;
	}

	function draw( $tpl_name, $return_string = true )
	{
		return parent::draw($tpl_name, $return_string);
	}

	private function _get_hard_cache_file_name($tpl_name)
	{
		$tpl_basedir = strpos($tpl_name,"/") ? dirname($tpl_name) . '/' : null;
		$this->template_dir = $this->_get_tpl_dir($tpl_name, $tpl_basedir);

		return $this->template_dir . $tpl_name.'_hard_cache.'.self::$tpl_ext;
	}

	function draw_hard_cache( $tpl_name, $life_time = 0 )
	{
		$cache_path = $this->_get_hard_cache_file_name($tpl_name);

		if (!file_exists($cache_path) || TIME - filemtime($cache_path) > $life_time)
			return false;
		else
			return file_get_contents($cache_path);
	}

	function save_hard_cache($tpl_name)
	{
		$cache_path = $this->_get_hard_cache_file_name($tpl_name);

		$html = $this->draw($tpl_name);

		$Handle = fopen($cache_path, 'w');
		fwrite($Handle, $html );
		fclose($Handle);

		return $html;
	}
}




?>
