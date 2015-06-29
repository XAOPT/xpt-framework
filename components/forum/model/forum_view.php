<?php

class ForumView extends ContForum
{
	var $forum_cats = array();

	function __construct()
	{
		parent::__construct();
	}

	###########################
	# просмотр списка разделов форума. запускает конструктор дерева build_index_tree
	###########################
	function ViewForumIndex()
	{
		global $gUser;

		$this->BuildIndexTree(0,0);

		$this->tpl->assign('gUser', $gUser);

		foreach ($this->forum_cats AS &$f)
		{
			$access = explode(',', $f['access']);

			if(guser::_hasAccess(self::$name, 'view', $f['cat_id']) || $f['limited'] == 0)
				$f['showme'] = 1;
			else
				$f['showme'] = 0;

			$f['last_time'] = date('H:i:s', $f['last_dtime']);
			$f['last_date'] = date('d.m.y', $f['last_dtime']);

			$f['last_date'] = $this->GetDayFromDate($f['last_date']);
		}

		$this->tpl->assign('forum_cats', $this->forum_cats);

		ClassPage::AddToTitle("");
		ClassPage::SetDescription("");
		ClassPage::SetKeywords("");

		return $this->tpl->draw( "forum_index" );
	}


	###########################
	# конструктор дерева разделов форума
	###########################
	function BuildIndexTree($pid, $lvl)
	{
		global $sql;

		$q = "
			SELECT c.*, u.login AS last_username, u.avatar, u.email, t.subj AS last_subj, t.t_id, t.last_dtime AS last_mess
			FROM `forum_cats` AS c
			LEFT JOIN `users` AS u ON (u.userid = c.last_userid)
			LEFT JOIN `forum_topics` AS t ON (t.t_id = c.last_tid)
			WHERE c.parent = '{$pid}' ORDER BY c.pos, c.cat_title";
		$sql->SetQuery($q);
		$local_forum_cats = $sql->LoadAllRows();

		if (!empty($local_forum_cats))
		foreach ($local_forum_cats as $cat)
		{
			$cat['level'] = $lvl; // глубина вложенности

			$this->forum_cats[] = $cat;

			$this->BuildIndexTree($cat['cat_id'], ($lvl+1));
		}
	}

	###########################
	# принимает время (например, создания топика) в формате d.m.Y и преобразует его во "вчера" или "сегодня", если есть необходимость
	###########################
	function GetDayFromDate($realDate = '')
	{
		$today     = date("d.m.y");
		$yesterday = date("d.m.y", time()-86400);

		if ($today == $realDate)
			return '<b>Сегодня</b>';
		else if ($yesterday == $realDate)
			return '<b>Вчера</b>';
		else
			return $realDate;
	}

	###########################
	## просмотр списка топиков в категории
	###########################
	function ViewCat()
	{
		global $sql, $gUser, $gUserid, $rewrite;

		$cat_id = intval($rewrite->params[0]);

		// извлечение информации о категории
		$cati = $sql->SetQuery("SELECT cat_id, cat_title, limited FROM `forum_cats` WHERE cat_id='{$cat_id}'", "LoadRow");

		if (empty($cati))
			error_404();

		$cat_title = $cati['cat_title'];

		if(!guser::_hasAccess(self::$name, 'view', $cati['cat_id']) && $cati['limited'] == 1) {
			return "Вы не имеете доступа к данной странице";
		}

		$this->tpl->assign('cat_title', $cat_title);

		#### пагинация
		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 1);
		$pagination->per_page = 20;

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		$sql->setQuery("SELECT COUNT(*) FROM `forum_topics` WHERE cat_id='{$cat_id}'");
		$pagination->total = $sql->LoadSingle();

		$this->tpl->assign('page', $pagination->current_page);
		####

		## список топиков
		$q = "
		SELECT t.*,u.login AS username, u.email, lu.login AS last_username, lu.avatar
		FROM `forum_topics` AS t
		LEFT JOIN `users` AS u ON (u.userid=t.userid)
		LEFT JOIN `users` AS lu ON (lu.userid=t.last_userid)
		WHERE t.cat_id='{$cat_id}'
		ORDER BY t.snap DESC, t.last_dtime DESC
		LIMIT {$limit_start}, {$pagination->per_page}
		";
		$sql->SetQuery($q);
		$topics = $sql->loadAllRows();

		foreach ($topics as &$t)
		{
			$t['last_time'] = date('H:i:s', $t['last_dtime']);
			$t['last_date'] = date('d.m.y', $t['last_dtime']);

			$pages = ceil($t['messages']/parent::$post_per_page);

			if($pages > 1)
			{
				$t['nav'] = '';

				for($i = 1; $i <= $pages; $i++)
				{
					if($pages > 4 && $i > 4 && $i < $pages)
					{
						if(!$s)
						{
							$s = " ... ";
							$t['nav'] .= $s;
						}
					}
					else
					{
						$s = '';

						if($i != $pages)
							$r = " ";
						else
							$r = '';


						$t['nav'] .= '<a href="'.DOMAIN.'/forum/view/'.$t['t_id'].'/?p='.$i.'">'.$i.'</a>'.$r;
					}
				}

				$t['nav'] .= "";
			}

			$t['last_date'] = $this->GetDayFromDate($t['last_date']);
		}

		$this->tpl->assign('topics',$topics);

		$pagination->url_start = DOMAIN."/".self::$name."/cat/{$cat_id}/?p=";

		$this->tpl->assign( "pagination", $pagination->draw() );

		## проверяем, может ли писать пользователь
		$this->tpl->assign('gUser', $gUser);
		$this->tpl->assign('gUserid', $gUserid);
		$this->tpl->assign('cat_id', $cat_id);

		ClassPage::AddToTitle("Раздел форума {$cat_title}");
		ClassPage::SetDescription("{$cat_title}");
		ClassPage::SetKeywords("{$cat_title}");

		return $this->tpl->draw( "view_cat" );
	}

	function ViewTopic()
	{
		global $sql, $rewrite, $gUserid, $gUser, $access_names;

		$t_id = (int)$rewrite->params[0];

		if (!$t_id) return 'Ошибка';


		// получаем информацию о самом топике. категория, автор, текст вопроса и т.п.
		$q = "SELECT t.*, c.cat_id, c.cat_title FROM `forum_topics` AS t
			  LEFT JOIN `forum_cats` AS c ON (c.cat_id = t.cat_id)
			  WHERE t_id ='{$t_id}'";

		$topic = $sql->setQuery($q, 'LoadRow');

		if (empty($topic))
			error_404();


		$cat_info = $sql->SetQuery("SELECT cat_id, limited FROM `forum_cats` WHERE cat_id='{$topic['cat_id']}'", "LoadRow");

		if(!guser::_hasAccess(self::$name, 'view', $cat_info['cat_id']) && $cat_info['limited'] == 1) {
			return "Вы не имеете доступа к данной странице";
		}

		$this->tpl->assign('topic', $topic);


		//ОПРОС
		if($topic['poll'])
		{
			$poll_answers = $sql->SetQuery("SELECT * FROM `forum_poll_answers` WHERE t_id='{$t_id}' ORDER BY a_id ASC", "LoadAllRows");

			$poll_count   = $sql->SetQuery("SELECT COUNT(*) FROM `forum_poll_votes` WHERE t_id='{$t_id}'", "LoadSingle");

			$poll_count_res   = $sql->SetQuery("SELECT * FROM `forum_poll_votes` WHERE t_id='{$t_id}' GROUP BY t_id, userid", "LoadRow");
			$poll_count_users = $sql->NumRows();

			$user_votes_res   = $sql->SetQuery("SELECT a_id FROM `forum_poll_votes` WHERE t_id='{$t_id}' AND userid='{$gUserid}'", "LoadAllRows");
			$user_count       = $sql->NumRows();

			$user_votes = array();

			if (!empty($user_votes_res))
			foreach($user_votes_res as $uv)
			{
				$user_votes[] = $uv['a_id'];
			}

			$show = true;
			if($topic['poll_show'] == 2)
			{
				if(!$user_count)
					$show = false;
			}
			else if ($topic['poll_show'] == 3)
			{
				if($topic['poll_state'] != 'close')
					$show = false;
			}

			$this->tpl->assign('poll_show', $show);
			$this->tpl->assign('user_votes', $user_votes);
			$this->tpl->assign('poll_answers', $poll_answers);
			$this->tpl->assign('poll_count', $poll_count);
			$this->tpl->assign('poll_count_users', $poll_count_users);
			$this->tpl->assign('user_count', $user_count);
		}


		## PAGINATION

		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 0);
		$pagination->per_page = self::$post_per_page;

		$query = "SELECT COUNT(*) FROM `forum_mess` WHERE t_id ='{$t_id}'";
		$pagination->total = $sql->SetQuery($query, 'LoadSingle');

		if (!$pagination->current_page)
			$pagination->current_page = ceil($pagination->total/self::$post_per_page);

		$this->tpl->assign('page', $pagination->current_page);

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		// получаем информацию обо всех ответах
		$q = "
		SELECT *
		FROM `forum_mess`
		WHERE t_id ='{$t_id}'
		ORDER BY dtime
		LIMIT {$limit_start},{$pagination->per_page}";

		$sql->SetQuery($q);
		$messages = $sql->LoadAllRows();


		$bb = new ClassBB($this->smiles);


		$uids = array();
		if (!empty($messages))
		{
			foreach ($messages as $m)
			{
				$uids[] = $m['userid'];
			}
			$uids = implode(',', $uids);

			$q = "
			SELECT u.karma, u.banned, u.login, u.email, u.userid, u.avatar, u.usergroupid, d.utime, d.text, u.groups, us.forum_mess, us.warnings
			FROM `users` AS u
			LEFT JOIN `users_stat` AS us ON (us.userid = u.userid)
			LEFT JOIN `forum_warnings` AS d ON(d.userid=u.userid)
			WHERE u.userid IN ({$uids})
			";

			$sql->SetQuery($q);
			$users = $sql->LoadByUniq('userid');

			foreach ($users as &$u) {
				$u['subs'] = $bb->show($u['subs']);
			}

			$this->tpl->assign('users', $users);
		}


		foreach ($messages as &$m)
		{
			$t = $this->GetDayFromDate(date('d.m.y', $m['dtime']));

			$m['ftime'] = $t." в ".date('H:i', $m['dtime']);
			$m['utime'] = $m['dtime'];
			$m['dtime'] = date('d.m.y H:i', $m['dtime']);

			$m['qmess']   = htmlspecialchars($m['message'],  ENT_QUOTES);
			$m['qmess']   = preg_replace('/\[dvred\](.*?)\[\/dvred\]/',"$1",$m['message']);
			$m['message'] = $bb->show($m['message']);
			$m['editor']  = json_decode($m['editor']);
		}

		$this->tpl->assign('messages', $messages);
		$this->tpl->assign('smiles', $this->smiles);


		$pagination->url_start =DOMAIN."/forum/view/{$t_id}/?p=";
		$this->tpl->assign( "pagination", $pagination->draw() );

		$this->tpl->assign('gUser', $gUser);
		$this->tpl->assign('gUserid', $gUserid);
		$this->tpl->assign( "access_names", $access_names );

		ClassPage::AddToTitle($topic['subj']);
		ClassPage::SetDescription("Топик {$topic['subj']}");
		ClassPage::SetKeywords("{$topic['subj']}");

		return $this->tpl->draw( "topic_view" );
	}


	###########################
	## страница создания топика
	## $is_poll - флаг опроса
	###########################
	function NewTopic($is_poll = 0)
	{
		global $rewrite, $sql;

		$cat_id = $rewrite->params[0];

		$count = $sql->SetQuery("SELECT COUNT(*) FROM `forum_cats` WHERE cat_id='{$cat_id}'", 'LoadSingle');

		if (!$count) error_404();

		$this->tpl->assign('cat_id', $cat_id);
		$this->tpl->assign('is_poll', $is_poll);

		$this->tpl->assign('smiles', $this->smiles);

		return $this->tpl->draw( "new_topic" );
	}


	###########################
	## страница редактирования сообщения
	###########################
	function ViewEditmess()
	{
		global $sql, $rewrite, $gUser;

		$m_id = $rewrite->params[0];

		if ($m_id)
		{
			$q = "
			SELECT m.*, t.cat_id, t.published
			FROM `forum_mess` AS m
			LEFT JOIN `forum_topics` AS t ON (t.t_id=m.t_id)
			WHERE m.m_id={$m_id}";

			$message = $sql->SetQuery($q, 'LoadRow');

			// редактировать могут модераторы, либо владелец поста при условии, что топик открыт
			if ( ($message['userid'] != $gUser['userid'] || !$message['published']) && !guser::_hasAccess(self::$name, 'moder', $message['cat_id'])  ) {
				return 'Вы не можете редактировать это сообщение';
			}

			$this->tpl->assign('smiles', $this->smiles);
			$this->tpl->assign('message', $message);
		}

		return $this->tpl->draw( "edit_message" );
	}


	/*function alone_block($options = array())
	{
		global $db, $human;
		$this->template = 'alone_block.htm';

		$q = "SELECT t.*, COUNT(m.text) AS messages, c.cat_id FROM `xen_forum_cats` AS c
			  LEFT JOIN `xen_forum_topics` as t ON (c.cat_id=t.cat_id)
			  LEFT JOIN `xen_forum_mess`  as m ON (t.topic_id = m.topic_id)
			  WHERE c.options REGEXP '\"{$options[0]}\"\:{$options[1]}'
			  GROUP BY t.topic_id
			  ORDER BY m.date DESC LIMIT 0,5";

		$db->setQuery($q);
		$topics = $db->loadAllRows();

		foreach($topics as & $topic)
		{
			$q = "SELECT user_id, commentor, date FROM `xen_forum_mess` WHERE topic_id = '{$topic['topic_id']}' ORDER BY date DESC LIMIT 0,1";
			$db->setQuery($q);
			$lastone = $db->loadAssoc();

			if ($lastone['user_id'])
			{
				$topic['last_user'] = $human->getNameById($lastone['user_id']);
				$topic['last_date'] = $lastone['date'];
			}
			else
			{
				$topic['last_user'] = $lastone['commentor'];
			}
			$topic['login'] = $human->getNameById($topic['user_id']);
		}

		$this->tpl->assign('topics',$topics);

		$this->tpl->assign('alone_header','Форум школы');

		//require_once(ROOT_PATH."/libraries/bbcode/insert_bb.php");
		//$this->tpl->assign('bb_form', $this->bb_code);

		return $this->pull_output();
	}

	function last_topics()
	{
		global $db, $human;
		$this->template = 'view_last_topics.htm';

		// извлечение информации о категории
		$q = "SELECT cat_title FROM `xen_forum_cats` AS c WHERE c.cat_id={$cat_id}";
		$db->setQuery($q);
		$this->tpl->assign('cat', $db->loadAssoc());

		$q = "SELECT t.*, COUNT(m.text) AS messages, u.login AS username, u.user_id, c.options
			  FROM `xen_forum_topics` as t
			  LEFT JOIN `xen_forum_mess` as m ON (t.topic_id = m.topic_id)
			  LEFT JOIN `xen_forum_cats` as c ON (c.cat_id = t.cat_id)
			  LEFT JOIN `xen_users` as u ON (u.user_id = t.user_id)
			  GROUP BY t.topic_id
			  HAVING c.options = ''
			  ORDER BY m.date DESC
			  LIMIT 0,5";

		$db->setQuery($q);
		$topics = $db->loadAllRows();

		if ($topics[0]['username'])
		{
			foreach($topics as & $topic)
			{
				$db->setQuery("SELECT user_id, commentor, DATE_FORMAT(date,'%d.%m.%Y') AS last_date, DATE_FORMAT(date,'%H:%i') AS last_time FROM `xen_forum_mess` WHERE topic_id = '{$topic['topic_id']}' ORDER BY date DESC LIMIT 0,1");
				$lastone = $db->loadAssoc();

				if ($lastone['user_id'])
				{
					$topic['last_user'] = $human->getNameById($lastone['user_id']);
				}
				else
				{
					$topic['last_user'] = $lastone['commentor'];
				}

				$today = date("d.m.Y");
				if ($today == $lastone['last_date'])
					$topic['last_date'] = 'Сегодня';
				else
					$topic['last_date'] = $lastone['last_date'];

				$topic['last_time'] = $lastone['last_time'];

				if (!$topic['messages']) {$topic['messages'] = 1;}

				$topic['num_pages'] = ceil($topic['messages']/self::$post_per_page); // количество страниц топика
			}

			$this->tpl->assign('topics',$topics);
		}

		return $this->pull_output();
	}*/

	function EditTopic()
	{
		global $sql, $rewrite, $gUser, $gUserid;

		$date = '';
		$time = '';

		if (!$t_id)
			$t_id = (int)$rewrite->params[0];

		$topic = $sql->SetQuery("SELECT * FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadRow');

		if(!guser::_hasAccess(self::$name, 'moder', $topic['cat_id']) && $gUserid != $topic['userid'] || empty($topic))
			return "Вы не имеете доступа к данной операции";

		$answers = $sql->SetQuery("SELECT * FROM `forum_poll_answers` WHERE t_id='{$t_id}' ORDER BY a_id ASC", 'LoadAllRows');
		$check   = $sql->setQuery("SELECT COUNT(*) FROM `forum_poll_votes`  WHERE `t_id`='{$t_id}'", 'LoadSingle');

		if($topic['poll_date'])
		{
			$date = date("d.m.Y", $topic['poll_date']);
			$time = date("H:i", $topic['poll_date']);
		}

		$show = true;

		if(guser::_hasAccess(self::$name, 'move'))
		{
			$cat_select = $this->GetParentsSelect($topic['cat_id']);
			$this->tpl->assign('cat_select', $cat_select);
		}

		$this->tpl->assign('answers', $answers);
		$this->tpl->assign('topic',$topic);
		$this->tpl->assign('date',$date);
		$this->tpl->assign('check',$check);
		$this->tpl->assign('time',$time);
		$this->tpl->assign('t_id',$t_id);
		$this->tpl->assign('gUser',$gUser);

		return $this->tpl->draw( "edit_topic" );
	}
	###########################
	## все сообщения юзера
	###########################
	function UserMess()
	{
		global $sql, $gUserid;
		if (!$gUserid)
			return "<div class='warning'>Вы не вошли на сайт под своим именем. Пожалуйста авторизуйтесь или пройдите форму регистрации.</div>";
		$userid = $gUserid;
		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 0);
		$pagination->per_page = 20;
		$pagination->total = $total =  $sql->setQuery("SELECT COUNT(*) FROM `forum_mess` WHERE `userid` = {$gUserid}",'LoadSingle');
		if (!$pagination->current_page)
		$pagination->current_page = ceil($pagination->total/20);

		$this->tpl->assign('page', $pagination->current_page);

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;
		$mess = $sql->SetQuery("SELECT m_id, t_id, message, dtime FROM `forum_mess` WHERE `userid` = {$gUserid} LIMIT {$limit_start},20",'LoadAllRows');
		$bb = new ClassBB($this->smiles);
		foreach ($mess as &$m) {
			$t = $this->GetDayFromDate(date('d.m.y', $m['dtime']));
			$m['ftime'] = $t." в ".date('H:i', $m['dtime']);
			$m['dtime'] = date('d.m.y H:i', $m['dtime']);
			$m['message'] = $bb->show($m['message']);
		}
		$pagination->url_start =DOMAIN."/forum/usermess/?p=";
		$this->tpl->assign( "pagination", $pagination->draw() );
		$this->tpl->assign('messages', $mess);
		$this->tpl->assign('total', $total);
		$this->tpl->assign('smiles', $this->smiles);
		$this->tpl->assign('gUserid', $gUserid);
		ClassPage::AddToTitle("Все сообщения на форуме");
		ClassPage::SetDescription("Все сообщения пользователя на форуме за всё время");
		ClassPage::SetKeywords("сообщения");

			return $this->tpl->draw( "user_mess" );
	}
	###########################
	## поиск по форуму
	###########################
	function SearchTopic()
	{
		global $sql, $rewrite, $gUserid, $gUser, $access_names;
		$q_search = zReq::getVar( 'q', 'SQL', 'GET');
		if(strlen($q_search)<3 || strlen($q_search)>120 )
			return "Ошибка, длина запроса некорректна";

				## список топиков
		/*$q = "
		SELECT t.*,u.login AS username, lu.login AS last_username, lu.avatar
		FROM `forum_topics` AS t
		LEFT JOIN `users` AS u ON (u.userid=t.userid)
		LEFT JOIN `users` AS lu ON (lu.userid=t.last_userid)
		WHERE subj LIKE '%".$q_search."%' AND
		ORDER BY t.snap DESC, t.last_dtime DESC
		LIMIT 20
		";*/

		// получаем информацию обо всех ответах
		$q = "
		SELECT *
		FROM `forum_mess`
		WHERE message LIKE '%".$q_search."%'
		ORDER BY dtime DESC
		LIMIT 50";

		$sql->SetQuery($q);
		$messages = $sql->LoadAllRows();


		$bb = new ClassBB($this->smiles);


		$uids = array();
		if (!empty($messages))
		{
			foreach ($messages as $m)
			{
				$uids[] = $m['userid'];
			}
			$uids = implode(',', $uids);

			$q = "
			SELECT u.karma,u.banned, u.login, u.userid, u.avatar, u.email, u.usergroupid, d.utime, d.text, u.groups, us.forum_mess, i.warnings, i.subs
			FROM `users` AS u
			LEFT JOIN `users_stat` AS us ON (us.userid = u.userid)
			LEFT JOIN `forum_uinfo` AS i ON (u.userid = i.userid)
			LEFT JOIN `dv_forum_warnings` AS d ON(d.userid=u.userid)
			WHERE u.userid IN ({$uids})
			";

			$sql->SetQuery($q);
			$users = $sql->LoadByUniq('userid');

			foreach ($users as &$u) {
				$u['subs'] = $bb->show($u['subs']);
			}

			$this->tpl->assign('users', $users);
		}


		foreach ($messages as &$m)
		{
			$t = $this->GetDayFromDate(date('d.m.y', $m['dtime']));

			$m['ftime'] = $t." в ".date('H:i', $m['dtime']);
			$m['utime'] = $m['dtime'];
			$m['dtime'] = date('d.m.y H:i', $m['dtime']);
			$m['qmess']   = htmlspecialchars($m['message'],  ENT_QUOTES);
			$m['qmess']   = preg_replace('/\[dvred\](.*?)\[\/dvred\]/',"$1",$m['message']);
			$m['message'] = $bb->show($m['message']);
			$m['editor']  = json_decode($m['editor']);
		}

		$this->tpl->assign('messages', $messages);
		$this->tpl->assign('smiles', $this->smiles);
		$this->tpl->assign('gUser', $gUser);
		$this->tpl->assign('gUserid', $gUserid);
		//$sql->SetQuery($q);
		//$topics = $sql->loadAllRows();
		//$this->tpl->assign('topics',$topics);
		ClassPage::AddToTitle("Поиск по форуму топиков со словами - {$q_search}");
		ClassPage::SetDescription("Поиск по форуму");
		ClassPage::SetKeywords("{$q_search}");

				return $this->tpl->draw( "search" );
	}

	##### ВЫНЕСТИ ОТСЮДА В ЛИБУ
	function GetParentsSelect( $selected = '')
	{
		$this->BuildCatsTree(0,0);
		$cats = $this->forum_cats;

		foreach ($cats as &$c)
		{
			$c['cat_title'] = str_repeat('&mdash;', $c['level']).' '.$c['cat_title'];
		}

		array_unshift($cats, array('cat_title' => '= Выберите родительскую категорию =', 'cat_id' => 0));

		if (!empty($selected))
			$cat_select = generate_select('cat_id', $cats, 'cat_id', 'cat_title', $selected);
		else
			$cat_select = generate_select('cat_id', $cats, 'cat_id', 'cat_title', '');

		return $cat_select;
	}

	function BuildCatsTree($pid, $lvl)
	{
		global $sql;

		$local_forum_cats = $sql->SetQuery("SELECT * FROM `forum_cats` WHERE parent = '{$pid}' ORDER BY cat_title", 'LoadAllRows');

		if (!empty($local_forum_cats))
		foreach ($local_forum_cats as $cat)
		{
			$cat['level'] = $lvl; // глубина вложенности

			if (guser::_hasAccess(self::$name, 'moder', $cat['cat_id']))
				$this->forum_cats[] = $cat;

			$this->BuildCatsTree($cat['cat_id'], ($lvl+1));
		}
	}
	##### END OF ВЫНЕСТИ ОТСЮДА В ЛИБУ
}

?>
