<?php
/* 
* version 0.1nd
*/
class ClassFiles
{
	private static function dir_name($dir)
	{
		if(!preg_match("#\/$#", $dir)) 
				$dir = $dir."/";
				
		return $dir;
	}
	
	public function add_folder($folder)
	{
		$dirs = explode("/", $folder);
			
		$r = true;
		
		$d	= '';
		foreach($dirs as $dir)
		{
			$d .= $dir."/";
			
			if(!is_dir($d))
				$r = @mkdir($d);
		}
						
		if ($r) 
			return true;
				
	}
	
	
	public function del_folder ($directory)
	{
		$directory = $this->dir_name($directory);
		
	    $dir = @opendir($directory);
	    if(!$dir) 
		{
			return false;
		}
	  
		while($file = readdir($dir))
		{
			if (is_file ($directory.$file))
			  unlink ($directory.$file);
			
			else if (is_dir($directory.$file) && $file != "." && $file != "..")
			  $this->del_folder ($directory.$file);  
		}
	  
	  closedir ($dir);
	  rmdir ($directory);
	  
	  return true;
	}
	
	public static function get_files ($directory, $pattern = false)
	{
		$directory = self::dir_name($directory);
		
		$files = array();
		$pattern = ($pattern)? $pattern: "(.)*";
		
		if(preg_match("/^\!./iu", $pattern))
		{
			$negative = true;
			$pattern = preg_replace("/^\!/iu", '', $pattern);
		}
		else
			$negative = false;
		
		$dir = @opendir($directory);
						
		if(!$dir) 
		{
			return false;
		}
		
		while($file = readdir($dir))
		{
			$clause = ($negative)? !preg_match("/".$pattern."/ui", $file): preg_match("/".$pattern."/ui", $file);
			
			if ($clause && $file != "." && $file != "..")
				$files[] = $file;
		}
	  
	   closedir ($dir);
	   
	   return $files;
		
	}
	
	public function del_files ($directory, $pattern = false)
	{
		$directory = $this->dir_name($directory);
		
		$pattern = ($pattern)? $pattern: "(.)*";
		
		$dir = @opendir($directory);
			
		if(!$dir) 
		{
			return false;
		}
		
		while($file = readdir($dir))
		{
			if (preg_match("/".$pattern."/iu", $file))
			{
				unlink($directory.$file);
			}
		}
	  
	   closedir ($dir);
	   
	   return true;
	}
}
?>