<?php
class CommentsView extends ContComments
{
	function __construct()
	{
		parent::__construct();
	}
	
	function CommentsBlock($option = array())
	{
		global $sql, $gUserid, $gUser;

		if (!$option[0] || !$option[1])
			return '';
			
		$component = $option[0];
		$param     = $option[1];
		$redir     = isset($option[2])?$option[2]:'';
		
		$this->tpl->assign( "component", $component );
		$this->tpl->assign( "param", $param );
		$this->tpl->assign( "redir", $redir );
		$this->tpl->assign( "gUser", $gUser );
		$this->tpl->assign( "gUserid", $gUserid );
		
		###
		$where = "component = '{$component}' AND param='{$param}'";
		
		$this->traverse = $this->GetTraverse($where);

		$query = "
		SELECT c.*, u.login AS username, u.avatar,u.email, u.karma
		FROM `comments` AS c 
		LEFT JOIN `users` AS u ON u.userid=c.user_id
		WHERE c.component = '{$component}' AND c.param='{$param}' 
		ORDER BY c.utime
		";

		$sql->SetQuery($query);
		$this->comments = $sql->LoadByUniq('comm_id');
	
		$this->sorted_comments = array();
		
		$this->SortCommentTree(0, 0);
		
		foreach ($this->sorted_comments as &$c)
		{
			//$c['date'] = date('d.m.Y', $c['utime']);
			//$c['time'] = date('H:i', $c['utime']);
			$c['date'] = $this->RelativeTime($c['utime']);
			$c['old']  = TIME - $c['utime'];
		}
		
		if (count($this->sorted_comments))
			$this->tpl->assign( "comments", $this->sorted_comments );
		else
			$this->tpl->assign( "no_comments", 1 );
		###
		
		return $this->tpl->draw( "comments_block_ajax"); 
	}
	
	function GetTraverse($where = '1=1')
	{
		global $sql;
		
		$q = "
		SELECT `parent` , GROUP_CONCAT( `comm_id` ORDER BY utime ) AS `childes`
		FROM `comments`
		WHERE {$where}
		GROUP BY `parent`
		";
		
		$sql->SetQuery($q);
		
		return $sql->LoadByUniq('parent');
	}
	
	
	function SortCommentTree($parent = 0, $level = 0)
	{
		$childes = array();
		
		if (isset($this->traverse[$parent]['childes']))
			$childes = explode(',', $this->traverse[$parent]['childes']);
		
		if (!count($childes)) return;
		
		foreach ($childes as $child)
		{
			if ($child > 0)
			{
				$this->comments[$child]['level'] = $level;
				$this->sorted_comments[] = $this->comments[$child];
				$this->SortCommentTree($child, $level+1);
			}
			else
				return;
		}
		
		return;
	}
	
	function RelativeTime($time)
	{
		//return $time.'-'.TIME;
		if ($time+60 > TIME) //меньше Минуты
		{
			return "только что";
		}		
		else if ($time+3600 > TIME) //меньше часа
		{
			$minutes = ceil((TIME-$time)/60);
			return "{$minutes} мин. назад";
		}
		else if ($time+86400 > TIME) // меньше суток
		{
			$hours = floor((TIME-$time)/3600);
			return "{$hours} ч. назад";
		}		
		else if ($time+604800 > TIME) // меньше недели
		{
			$days = floor((TIME-$time)/86400);
			if ($days == 1)
				return "1 день назад";
			else
				return "{$days} дн. назад";
		}
		else if ($time+2592000 > TIME) // меньше месяца
		{
			$weeks = floor((TIME-$time)/604800);
			return "{$weeks} нед. назад";
		}
		else if ($time+31104000 > TIME) // меньше года
		{
			$month = floor((TIME-$time)/2592000);
			if ($month == 1)
				return "1 месяц назад";
			else
				return "{$month} мес. назад";
		}
		else
		{
			return "более года назад";
		}
		
		return '';
	}
	
	/*function ViewLastReply($how_much = 5)
	{
		global $sql, $gUserid;
		
		
		
		$videos = $sql->SetQuery ("SELECT video.v_id, video.title, video.img, B.utime, B.user_id FROM comments A, comments B, video
									WHERE A.component = 'video' AND A.user_id = '{$gUserid}' 
									AND B.component = 'video' AND B.param = A.param AND B.utime > A.utime
									AND video.v_id = A.param
									GROUP BY A.param
									ORDER BY A.param DESC, B.utime DESC
									LIMIT {$how_much}", 'LoadAllRows');
								  
		for($i = 0; $i < count($videos); $i++)
		{
			if(count($videos[$i]))
				$videos[$i]['utime'] = $this->RelativeTime($videos[$i]['utime']);
		}
			
		$this->tpl->assign("videos", $videos);
		$this->tpl->assign("userid", $gUserid);
		
		return $this->tpl->draw( "comments_lastvideo", true ); 		
	}*/
	
	function ViewLastReply($how_much = 5)
	{
		global $sql, $gUserid;
		
		$q = "
			SELECT MAX( z.c1utime ) AS may_last, MAX( z.c2utime ) AS last, v.v_id, v.title, v.img
			FROM (
				SELECT c1.utime AS c1utime, c2.utime AS c2utime, c1.param AS param
				FROM `comments` AS c1
				LEFT JOIN `comments` AS c2 ON ( c1.param = c2.param )
				WHERE c1.user_id = {$gUserid}
				AND c2.user_id <> {$gUserid}
				AND c1.component = 'video'
				AND c2.component = 'video'
			) AS z
			LEFT JOIN `video` AS v ON ( v.v_id = z.param )
			GROUP BY z.param
			HAVING may_last < last
			ORDER BY last DESC
			LIMIT 0 , {$how_much}
		";		
		
		$sql->SetQuery($q);
		$videos = $sql->LoadAllRows();

		if(gettype($videos) == 'array')
		{
			foreach ($videos as &$v)
			{
				$v['time'] = $this->RelativeTime($v['last']);
			}
		}
		
		$this->tpl->assign("videos", $videos);
		
		return $this->tpl->draw( "comments_lastvideo", true ); 		
	}
}

?>
