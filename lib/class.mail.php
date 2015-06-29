<?php
/* 
* version 1.1
*/
class Mail 
{
	public $events = array();
	
	public $charset         = "utf-8";
	public $content_type    = "html";
	public $strip_tag       = false;
	
	private $type           = "text/plain";
	private $attachment     = '';
	private $image          = '';
	private $headers;      
    private $multipart;  
	private $boundary_main;
	private $boundary_files;

	
	private $to; 
	private $subject;     
    private $from;
	private $name;
	
					
	function __construct()
	{}
			
    public function image_back($name = 'image')    
    {    
       $this->image = "Content-ID: <".$name."> \r\n\r\n";
    } 
	public function add_file($file, $name = '')
	{
		if (is_file($file))
		{
			
			$this->boundary_files = (isset($this->boundary_files))? $this->boundary_files: '=='.uniqid(time());
						
            $this->attachment .= "--".$this->boundary_files."\r\n"; 
			
			$buffer = file_get_contents($file);
			 
			if($name == '')
			 	$name = basename($file); 
			else
			    $name =  '=?'.$this->charset.'?b?'.base64_encode($name).'?=';
			
			$ext = pathinfo($file, PATHINFO_EXTENSION); 
			           
			$type = (empty($this->mime_types[$ext]))? 'application/octet-stream': $this->mime_types[$ext];
					
			$this->attachment .= "Content-type: ".$type.'; name="'.$name."\"\r\n";  
            $this->attachment .= "Content-disposition: attachment; filename=\"".$name."\"\r\n";  
            $this->attachment .= "Content-Transfer-Encoding: base64\r\n";
			
			if(!empty($this->image) && getimagesize($file))
                  $this->attachment .= $this->image;
            else
                  $this->attachment .= "\r\n";
				  
			$this->attachment .= chunk_split(base64_encode($buffer))."\r\n\r\n"; 
			
			$this->image = '';
		}
		else 
			$this->events[] = 3;
		
	}
	private function multipart()  
	{  
	
		if($this->content_type != "text" && $this->content_type != "html") 
			$this->content_type = "text";
		
	    $this->boundary_main = '=='.uniqid(time());
		    
		
		if ($this->content_type == "text" && empty($this->attachment))
		{
			$this->headers = "Content-type: text/plain; charset=\"".$this->charset."\"\r\n"; 
			
			$this->multipart = $this->message;
			
			if ($this->strip_tag)
				$this->multipart = strip_tags($this->multipart);
		}
		elseif($this->content_type == "text" && !empty($this->attachment))
		{
			if ($this->strip_tag) $this->message = strip_tags($this->message);
			
			$this->headers    = "Content-type: multipart/mixed; boundary=\"".$this->boundary_files."\"\r\n";
			
			$this->multipart  = "--".$this->boundary_files."\r\n";
			$this->multipart .= "Content-type: text/plain; charset=\"".$this->charset."\"\r\n";
			$this->multipart .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$this->multipart .= chunk_split(base64_encode($this->message))."\r\n"; 
			
			$this->multipart .= $this->attachment;
			$this->multipart .= "--".$this->boundary_files."--";
		}
		elseif ($this->content_type == "html" && empty($this->attachment))
		{
			$this->headers    = "Content-type: multipart/alternative; boundary=\"".$this->boundary_main."\"\r\n";
			 
			$this->multipart  = "--".$this->boundary_main."\r\n";
			$this->multipart .= "Content-type: text/plain; charset=\"".$this->charset."\"\r\n";
			$this->multipart .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$this->multipart .= chunk_split(base64_encode(strip_tags($this->message)))."\r\n"; 
			 
			$this->multipart .= "--".$this->boundary_main."\r\n";
			$this->multipart .= "Content-type: text/html; charset=\"".$this->charset."\"\r\n";  
			$this->multipart .= "Content-Transfer-Encoding: base64\r\n\r\n";  
			$this->multipart .= chunk_split(base64_encode($this->message)) ."\r\n"; 
			$this->multipart .= "--".$this->boundary_main."--";
		}
		else
		{
			$this->headers    = "Content-type: multipart/mixed; boundary=\"".$this->boundary_files."\"\r\n";
			
			$this->multipart  = "--".$this->boundary_files."\r\n";
			$this->multipart .= "Content-type: multipart/alternative; boundary=\"".$this->boundary_main."\"\r\n";
			 
			$this->multipart .= "--".$this->boundary_main."\r\n";
			$this->multipart .= "Content-type: text/plain; charset=\"".$this->charset."\"\r\n";
			$this->multipart .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$this->multipart .= chunk_split(base64_encode(strip_tags($this->message)))."\r\n"; 
			 
			$this->multipart .= "--".$this->boundary_main."\r\n";
			$this->multipart .= "Content-type: text/html; charset=\"".$this->charset."\"\r\n";  
			$this->multipart .= "Content-Transfer-Encoding: base64\r\n\r\n";  
			$this->multipart .= chunk_split(base64_encode($this->message)) ."\r\n"; 
			$this->multipart .= "--".$this->boundary_main."--";
			$this->multipart .= "\r\n";
			
			$this->multipart .= $this->attachment;
			$this->multipart .= "--".$this->boundary_files."--";
					
		}
				
		$host = str_replace('www.', '', $_SERVER['HTTP_HOST']); 
		
		$this->headers .= "Date: ". date('D, d M Y h:i:s O') ."\r\n";        
      	$this->headers .= "From: ". $this->name." <".$this->from."> \r\n";  
     	$this->headers .= "Message-ID: <". md5(uniqid(time())) ."@". $host .">\r\n";       
     	$this->headers .= "X-Mailer: ".$this->name."\r\n";       
      	$this->headers .= "MIME-Version: 1.0\r\n"; 
		
    } 
	public function send($message, $to, $subject, $from = MAIL)
	{
		$this->message  = $message;
		
		$this->to       = $to;
		$this->subject  = $subject;
		$this->from     = $from;
		$this->name     = $this->from;
		
		$this->subject = '=?'.$this->charset.'?b?'.base64_encode($this->subject).'?=';
		$this->from = trim(preg_replace('/[\r\n]+/', ' ', $this->from ) );
		
		//Проверка валидности мэйлов
		$result = $this->is_email($this->to);
		if(!$result) 
			$this->events[] = 1;
			
		$result = $this->is_email($this->from);
		if(!$result) 
			$this->events[] = 2;
		
		if(!count($this->events))
		{
			$this->multipart();
			$result_send = mail($this->to, $this->subject,  $this->multipart, $this->headers);
		}	
					
		if(!$result_send)
			$this->events[] = 0;
			
    }
	
	public function is_email($email)
	{
		if (preg_match("/^[a-z0-9\-\._]+@[a-z0-9\.]+\.[a-z]{1,6}$/i", $email))
			return true;
	}
	
	
}

?>