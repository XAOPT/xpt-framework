<?php

class ForumAction extends ContForum
{
	function __construct()
	{
		parent::__construct();

		//require_once(ROOT_PATH."/lib/class.userstat.php");
	}

	///////
	// создаём топик
	///////
	function CreateTopic()
	{
		global $sql, $gUserid, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";
		$cat_id = zReq::getVar( 'cat_id', 'INT', 'POST', 0 );

		$cat = $sql->SetQuery("SELECT * FROM `forum_cats` WHERE cat_id='{$cat_id}'", 'LoadRow');

		// проверяем, есть ли доступ к категории
		if(!guser::_hasAccess(self::$name, 'view', $cat_id) && $cat['limited'] == 1 )
			return "Вы не имеете доступа к данной операции";

		// это мы засунем в forum_topics
		$data = array(
			'cat_id'      => $cat_id,
			'subj'        => zReq::getVar( 'subj', 'NOHTML_SQL', 'POST', '' ),
			'poll'        => zReq::getVar( 'poll', 'SQL', 'POST', ''),
			'poll_many'   => (int)zReq::getVar( 'poll_many', 'BOOL', 'POST', 0 ),
			'poll_show'   => zReq::getVar( 'poll_show', 'INT', 'POST', 1 ),
			'userid'      => $gUserid,
			'messages'    => 1,
			'last_dtime'  => time(),
			'last_userid' => $gUserid
		);

		// а это засунем в forum_mess
		$data2 = array(
			'dtime'   => time(),
			'message' => zReq::getVar( 'content', 'SAFE_NOHTML_NOBR', 'POST', '' ),
			'userid'  => $gUserid
		);


		if (!$data['subj'])     return '<h1>Ошибка!</h1><div class="warning">Не указана тема!</div>';
		if (!$data['cat_id'])   return '<h1>Ошибка!</h1><div class="warning">Не указана ветка!</div>';
		if (!$data2['message']) return '<h1>Ошибка!</h1><div class="warning">Сообщение пустое!</div>';
		if (!$gUserid)          return '<h1>Ошибка!</h1><div class="warning">Для создания топика необходимо авторизоваться на сайте</div>';
		if (empty($cat))        return '<h1>Ошибка!</h1><div class="warning">Ветка не найдена</div>';

		$answers = zReq::getVar( 'answer',  'ARRAY', 'POST');

		if($data['poll'] && count($answers) < 2)
			return "Нужно ввести хотя бы два варианта ответа";

		//BBCODE
		$bb = new ClassBB();
		$data2['message'] = $bb->save($data2['message']);

		if($data2['message'] === null)
			return 'Нарушена конструкция тэгов';

		$sql->InsertArray('forum_topics', $data);

		$data2['t_id'] = $sql->InsertId();

		// если указана тема голосования - cохраняем варианты ответов
		if($data['poll'])
		{
			$i = 0;

			// не более десяти вариантов ответа допустимо
			while ($i < 10 && isset($answers[$i]) && !empty($answers[$i])) {

				$answers[$i] = mysql_real_escape_string($answers[$i]);
				$sql->SetQuery("INSERT INTO `forum_poll_answers` SET `t_id`='{$data2['t_id']}', `answer` ='{$answers[$i]}'");

				$i++;
			}
		}

		$sql->InsertArray('forum_mess', $data2);

		$sql->SetQuery("UPDATE `forum_cats` SET incat=incat+1, messages=messages+1, last_dtime='{$data['last_dtime']}', last_tid='{$data2['t_id']}', last_userid='{$gUserid}' WHERE cat_id='{$data['cat_id']}'");

		## обновляем карму
		/*$stat = new ClassUserstat();
		$stat->userid = $gUserid;
		$stat->UpdateForumMess(1);*/

		header("Location: ".DOMAIN."/forum/cat/".$data['cat_id']."/");
		exit();
	}




	function PublishTopic()
	{
		global $sql, $rewrite;

		$t_id = (int)$rewrite->params[0];

		if (!$t_id)	return 'Ошибка публикации';

		$cat_id = $sql->SetQuery("SELECT cat_id FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadSingle');

		if (!$cat_id)	return 'Ошибка публикации';

		$limited = $sql->SetQuery("SELECT limited FROM `forum_cats` WHERE cat_id='{$cat_id}'", 'LoadSingle');

		if (!guser::_hasAccess(self::$name, 'moder', $cat_id) && $limited)
			return 'Ошибка доступа';

		$sql->SetQuery("UPDATE `forum_topics` SET `published`=IF(published=1,0,1) WHERE t_id='{$t_id}'");

		header('Location: '.$_SERVER['HTTP_REFERER']);
		exit();
	}

	function SnapTopic()
	{
		global $sql, $rewrite;

		$t_id = (int)$rewrite->params[0];

		if (!$t_id)	return 'Ошибка снапа';

		$cat_id = $sql->SetQuery("SELECT cat_id FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadSingle');

		if (!$cat_id)	return 'Ошибка публикации';

		$limited = $sql->SetQuery("SELECT limited FROM `forum_cats` WHERE cat_id='{$cat_id}'", 'LoadSingle');

		if (!guser::_hasAccess(self::$name, 'moder', $cat_id) && $limited)
			return 'Ошибка доступа';

		$sql->SetQuery("UPDATE `forum_topics` SET `snap`=IF(snap='1','0','1') WHERE t_id='{$t_id}'");

		header('Location: '.$_SERVER['HTTP_REFERER']);
		exit();
	}

	function DeleteTopic($t_id = 0)
	{
		global $sql, $rewrite, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		if (!$t_id)	$t_id = (int)$rewrite->params[0];

		if (!$t_id)	return 'Ошибка удаления';

		$cat_id = $sql->SetQuery("SELECT cat_id FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadSingle');

		$sql->SetQuery("DELETE FROM `forum_topics` WHERE t_id='{$t_id}'");

		## обновим карму всем.. трололо полное
		$karma_users = $sql->SetQuery("SELECT userid FROM `forum_mess` WHERE t_id='{$t_id}'", 'LoadSingleArray');
		$stat = new ClassUserstat();

		foreach ($karma_users as $k)
		{
			$stat->userid = $k;
			$stat->UpdateForumMess(0);
		}

		$sql->SetQuery("DELETE FROM `forum_mess` WHERE t_id='{$t_id}'");
		$deleted = $sql->AffectedRows();

		$sql->SetQuery("UPDATE `forum_cats` SET incat=incat-1, messages=messages-{$deleted} WHERE cat_id='{$cat_id}'");

		## если удаляемый топик - последний в ветке - надо найти новый последний топик
		$last_tid = $sql->SetQuery("SELECT last_tid FROM `forum_cats` WHERE cat_id='{$cat_id}'", 'LoadSingle');

		$this->DeletePoll($t_id, true);

		if ($t_id == $last_tid)
		{
			$q = "
			SELECT * FROM `forum_topics`
			WHERE cat_id = '{$cat_id}'
			ORDER BY last_dtime DESC
			";

			$sql->SetQuery($q);
			$lm = $sql->LoadRow();

			if (!empty($lm['t_id']))
			{
				$sql->SetQuery("UPDATE `forum_cats` SET last_tid='{$lm['t_id']}', last_userid='{$lm['last_userid']}', last_dtime='{$lm['last_dtime']}' WHERE cat_id='{$cat_id}'");
			}
		}
		#########

		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit();
	}


	function AddReply()
	{
		global $sql, $gUserid, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$data = array(
			't_id'    => zReq::getVar( 't_id', 'INT', 'POST', 0 ),
			'message' => zReq::getVar( 'message', 'SAFE_NOHTML_NOBR', 'POST', '' ),
			'userid'  => $gUserid,
			'dtime'   => TIME
		);

		$cat_info = $sql->SetQuery("SELECT * FROM `forum_cats` AS c LEFT JOIN `forum_topics` AS t ON t.cat_id=c.cat_id WHERE t.t_id='{$data['t_id']}'", 'LoadRow');

		$cat_id = $cat_info['cat_id'];

		//BBCODE
		$bb = new ClassBB();
		$data['message'] = $bb->save($data['message']);

		if($data['message'] === null)
			return 'Нарушена конструкция тэгов';

		if (!$gUserid)          return 'Только зарегистрированные пользователи могут оставлять сообщения';
		if (!$data['t_id'])	return 'Ошибка добавления сообщения';
		if (!$cat_info['published'])	return 'Топик закрыт дарагой. Ай-йа тая ан-ната';
		if (!$data['message'])	return 'Сообщение не может быть пустым';
		if (!$cat_id)           return 'Пф..';

		if(!guser::_hasAccess(self::$name, 'view', $cat_id) && $cat_info['limited'] == 1)
			return "Вы не имеете доступа к данной операции";

		$sql->InsertArray('forum_mess', $data);

		$sql->SetQuery("UPDATE `forum_topics` SET messages=messages+1, last_dtime='{$data['dtime']}', last_userid='{$data['userid']}' WHERE t_id='{$data['t_id']}'");

		$sql->SetQuery("UPDATE `forum_cats` SET messages=messages+1, last_dtime='{$data['dtime']}', last_tid='{$data['t_id']}', last_userid='{$gUserid}' WHERE cat_id='{$cat_id}'");

		## обновляем карму
		$stat = new ClassUserstat();
		$stat->userid = $gUserid;
		$stat->UpdateForumMess(1);

		## поиск страницы
		$sql->setQuery("SELECT COUNT(*) FROM `forum_mess` WHERE t_id ='{$data['t_id']}'");
		$total = $sql->LoadSingle();
		$last_page = ceil($total/self::$post_per_page);

		if ($last_page > 1)
			header("Location: ".DOMAIN."/forum/view/".$data['t_id']."/?p={$last_page}#m".TIME);
		else
			header("Location: ".DOMAIN."/forum/view/".$data['t_id']."/#m".TIME);
		exit();
	}

	function UpdateMess()
	{
		global $sql, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$m_id = zReq::getVar( 'm_id', 'INT', 'POST', 0 );

		if (!$m_id)	return 'Ошибка редактирования сообщения';

		$message_text  = zReq::getVar( 'message', 'SAFE_NOHTML_NOBR', 'POST', '' );

		//BBCODE
		$bb = new ClassBB();
		$message_text = $bb->save($message_text);

		if($message_text === null) return 'Нарушена конструкция тэгов';
		if (!$message_text)	   return 'Сообщение не может быть пустым';

		$message = $sql->SetQuery("SELECT * FROM `forum_mess` WHERE m_id={$m_id}", 'LoadRow');

		$cat_id = $sql->SetQuery("SELECT cat_id FROM forum_topics WHERE t_id='{$message['t_id']}'", 'LoadSingle');

		if(!guser::_hasAccess(self::$name, 'moder', $cat_id) && $message['userid'] != $gUser['userid'])
			return "Вы не имеете доступа к данной операции";

		$editor_new = array($gUser['login'], TIME);

		$editor = json_decode($message['editor']);
		$new_array = array();

		if (!empty($editor))
		foreach ($editor as $key => $value)
		{
			if ($value[0] != $gUser['login']) $new_array[] = $editor[$key];
		}
		$new_array[] = $editor_new;
		$data['editor'] = json_encode($new_array);

		$data['message'] = $message_text;

		$sql->UpdateArray('forum_mess', $data, "m_id='{$m_id}'");

		header("Location: ".DOMAIN."/".self::$name."/view/".$message['t_id']."/");
		exit();
	}

	function DeleteMess()
	{
		global $sql, $gUserid, $rewrite, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$m_id = (int)$rewrite->params[0];

		if (!$m_id)	return 'Ошибка удаления сообщения';

		$message  = $sql->SetQuery("SELECT userid, t_id FROM `forum_mess` WHERE m_id='{$m_id}'", 'LoadRow');
		$first_id = $sql->SetQuery("SELECT m_id FROM `forum_mess` WHERE t_id='{$message['t_id']}' ORDER BY dtime LIMIT 0,1", 'LoadSingle');
		$cat_id   = $sql->SetQuery("SELECT cat_id FROM forum_topics WHERE t_id='{$message['t_id']}'", 'LoadSingle');

		if(!guser::_hasAccess(self::$name, 'moder', $cat_id) && $message['userid'] != $gUserid)
			return "Вы не имеете доступа к данной операции";

		if ($first_id == $m_id)
		{
			$this->DeleteTopic($message['t_id']);
			return;
		}

		## удаляем мессагу и обновляем последнее сообщение в топике
		$sql->SetQuery("DELETE FROM `forum_mess` WHERE m_id='{$m_id}'");

		$last_mess = $sql->SetQuery("SELECT dtime, userid FROM `forum_mess` WHERE t_id='{$message['t_id']}' ORDER BY dtime DESC LIMIT 0,1", 'LoadRow');

		$sql->SetQuery("UPDATE `forum_topics` SET messages=messages-1, last_userid='{$last_mess['userid']}', last_dtime='{$last_mess['dtime']}' WHERE t_id='{$message['t_id']}'");
		###

		##############
		## обновляем последнее собщение в Категории
		$sql->SetQuery("SELECT last_tid FROM `forum_cats` WHERE cat_id='{$cat_id}'");
		$last_tid = $sql->LoadSingle();

		$this->DeletePoll($t_id, true);

		if ($message['t_id'] == $last_tid)
		{
			$q = "
			SELECT * FROM `forum_topics`
			WHERE cat_id = '{$cat_id}'
			ORDER BY last_dtime DESC
			LIMIT 0,1
			";

			$sql->SetQuery($q);
			$lm = $sql->LoadRow();

			if (!empty($lm['t_id']))
			{
				$sql->SetQuery("UPDATE `forum_cats` SET messages=messages-1, last_tid='{$lm['t_id']}', last_userid='{$lm['last_userid']}', last_dtime='{$lm['last_dtime']}' WHERE cat_id='{$cat_id}'");
			}
		}
		else
			$sql->SetQuery("UPDATE `forum_cats` SET messages=messages-1 WHERE cat_id='{$cat_id}'");
		##
		#########

		## обновляем карму
		$stat = new ClassUserstat();
		$stat->userid = $message['userid'];
		$stat->UpdateForumMess(0);

		header("Location: ".DOMAIN."/".self::$name."/view/".$message['t_id']."/");
		exit();
	}

	function EditTopic()
	{
	    global $sql, $gUserid, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$t_id = zReq::getVar( 't_id', 'INT', 'POST', 0 );
		$subj = zReq::getVar( 'subj', 'SQL', 'POST', '' );

		if (!$t_id)	return "Ошибка редактирования";

		$poll = zReq::getVar( 'poll',  'SQL', 'POST', '' );
		$poll_many = 0;
		$poll_show = 1;

		$check = $sql->setQuery("SELECT COUNT(*) FROM `forum_poll_votes`  WHERE `t_id`='{$t_id}'", 'LoadSingle');

		//POLL_ANSWERS
		if($poll)
		{
			//$poll_date   = zReq::getVar( 'poll_date', 'SQL', 'POST', '' );
			//$poll_time   = zReq::getVar( 'poll_time', 'SQL', 'POST', '' );
			$poll_many   = zReq::getVar( 'poll_many', 'BOOL', 'POST', 0 );
			$poll_show   = zReq::getVar( 'poll_show', 'INT', 'POST', 1 );

			/* if($poll_date)
			{
				if (!preg_match("/^\d{2}\:\d{2}$/", $poll_time) && $poll_time) ## верный формат времени - чч:мм
					return "Неверный формат времени";

				if (!preg_match("/^(\d{2})\.(\d{2})\.(\d{4})$/", $poll_date) &&  $poll_date) ## верный формат даты - дд:мм:гггг
					return "Неверный формат даты";

				$poll_date = trim($poll_date." ".$poll_time);
				$poll_date = strtotime($poll_date);
			} */

			if($check) return "Данный опрос уже нельзя редактировать";

			$sql->SetQuery("DELETE FROM `forum_poll_answers` WHERE `t_id`='{$t_id}'");

			$answers = zReq::getVar( 'answer',  'ARRAY', 'POST');

			if(count($answers) < 2) return "Нужно ввести хотя бы два варианта ответа";

			$c = 0;
			foreach($answers as $v)
			{
				if($v)
				{
					$c++;
					$v = mysql_real_escape_string($v);
					$sql->SetQuery("INSERT INTO `forum_poll_answers` SET `t_id`='{$t_id}', `answer` ='{$v}'");
				}
			}
		}
		$pollinfo = $sql->setQuery("SELECT poll, poll_many, poll_show, userid FROM `forum_topics`  WHERE `t_id`='{$t_id}'", 'LoadRow');

		if(!$poll && $check)
		{
			//$poll_date = $pollinfo['poll_date'];
			$poll_many = $pollinfo['poll_many'];
			$poll_show = $pollinfo['poll_show'];

			$poll = $pollinfo['poll'];
		}
		//

		$data = array (
			'subj' => $subj,
			'poll' => $poll,
			//'poll_date' => $poll_date,
			'poll_show' => $poll_show
		);

		$cat = zReq::getVar( 'cat_id', 'INT', 'POST', 0 );

		if ($cat)
		{
			$cat_info = $sql->SetQuery("SELECT * FROM `forum_cats` WHERE cat_id='{$cat}'", 'LoadRow');

			if (!$cat_info) return "Чо за дела братюнь";

			if (guser::_hasAccess(self::$name, 'move') && (guser::_hasAccess(self::$name, 'moder', $cat) || $cat_info['limited'] == 0))
				$data['cat_id'] = $cat;
		}

		if (!isset($data['cat_id']))
			$data['cat_id'] = $sql->SetQuery("SELECT cat_id FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadSingle');

		if(!guser::_hasAccess(self::$name, 'moder', $data['cat_id']) && $gUserid != $pollinfo['userid'])
			return "Вы не имеете доступа к данной операции";

		$sql->UpdateArray('forum_topics', $data, "t_id='{$t_id}'");

		header("Location: ".DOMAIN."/".self::$name."/cat/".$data['cat_id']."/");
		exit();
	}

	function AddVote()
	{
	    global $sql, $gUserid;

		$t_id    = zReq::getVar( 't_id', 'INT', 'POST', 0 );
		$answers = zReq::getVar( 'answers', 'ARRAY', 'POST', 0 );

		if(!$answers)
			return "Нужно выбрать вариант для голосования";

		$poll_state =  $sql->setQuery("SELECT poll_state FROM `forum_topics` WHERE t_id ='{$t_id}'", 'LoadSingle');

		if(!$poll_state)           return "Ошибка голосования";
		if($poll_state == 'close') return "Опрос закрыт";

		$check = $sql->setQuery("SELECT COUNT(*) FROM `forum_poll_votes`
								 WHERE t_id ='{$t_id}'
								 AND userid = '{$gUserid}'", 'LoadSingle');

		if($check) return "Вы уже голосовали в этом опросе";

		foreach($answers as $a_id)
		{
			$sql->SetQuery("INSERT INTO `forum_poll_votes` SET a_id = '{$a_id}', t_id='{$t_id}', userid = '{$gUserid}'");
			$sql->SetQuery("UPDATE `forum_poll_answers` SET `count` = `count` + 1 WHERE a_id = '{$a_id}'");
		}

		header("Location: ".DOMAIN."/".self::$name."/view/".$t_id."/");
		exit();
	}

	function ChangePollState($state = '')
	{
		global $sql, $gUserid, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$t_id = zReq::getVar( 'id', 'INT', 'GET', 0 );

		$topic = $sql->SetQuery("SELECT * FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadRow');

		if(!guser::_hasAccess(self::$name, 'moder', $topic['cat_id']) && $topic['userid'] != $gUserid)
			return "Вы не имеете доступа к данной операции";

		$sql->SetQuery("UPDATE `forum_topics` SET `poll_state` = '{$state}'  WHERE t_id = '{$t_id}'");

		header("Location: ".DOMAIN."/".self::$name."/view/".$t_id."/");
		exit();
	}

	function DeletePoll($t_id, $r = false)
	{
		global $sql, $gUserid, $gUser;

		if($gUser["banned"]==1)
			return "Вы забанены и не можете совершать эти действия! Вместо этого, лучше подумайте над своим поведением!";

		$t_id = ($t_id)? $t_id: zReq::getVar( 'id', 'INT', 'GET', 0 );

		$topic = $sql->SetQuery("SELECT * FROM `forum_topics` WHERE t_id='{$t_id}'", 'LoadRow');

		if(!guser::_hasAccess(self::$name, 'moder', $topic['cat_id']) && $topic['userid'] != $gUserid)
			return "Вы не имеете доступа к данной операции";

		$sql->SetQuery("DELETE FROM `forum_poll_answers` WHERE `t_id`='{$t_id}'");
		$sql->SetQuery("DELETE FROM `forum_poll_votes` WHERE `t_id`='{$t_id}'");
		$sql->SetQuery("UPDATE `forum_topics` SET `poll_state` = '', poll = '', poll_many = '', poll_show = ''  WHERE t_id = '{$t_id}'");

		if(!$r)
		{
			header("Location: ".DOMAIN."/".self::$name."/view/".$t_id."/");
			exit();
		}
	}

	function addWarning()
	{
		global $sql, $gUser;

		$data = array(
			'utime'   => TIME,
			'm_id'    => zReq::GetVar('m_id', 'INT', 'GET', 0),
			'moderid' => $gUser['userid'],
			'text'    => zReq::GetVar('text', 'SQL', 'POST'),
			'page'	  => zReq::GetVar('page', 'INT', 'GET', 1)
		);

		$q = "
		SELECT m.*, t.cat_id
		FROM `forum_mess` AS m
		LEFT JOIN `forum_topics` AS t on (m.t_id=t.t_id)
		WHERE m.m_id='{$data['m_id']}'";

		$message = $sql->SetQuery($q, 'LoadRow');

		if(!guser::_hasAccess(self::$name, 'moder', $message['cat_id']) || empty($message))
			return "Вы не имеете доступа к данной операции";

		$data['userid']  = $message['userid'];
		$data['message'] = $message['message'];

		$sql->InsertArray("##forum_warnings", $data);

		$sql->SetQuery("INSERT INTO `forum_uinfo` SET userid='{$data['userid']}', warnings=1 ON DUPLICATE KEY UPDATE warnings=warnings+1");

		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit();
	}
	function addBan()
	{
		global $sql, $gUser;

		$data =  array(
			'userid' => zReq::GetVar('user_id', 'INT', 'GET', 0),
			'moder'  => $gUser['userid'],
			'date'   => zReq::GetVar('date','SQL','POST'),
			'why'    => zReq::GetVar('why','SQL', 'POST'),
			'spam'   => zReq::GetVar('spamer', 'SQL', 'POST')
			);
		$m_id = zReq::GetVar('m_id','INT','GET',0);
		$q = "
		SELECT m.t_id, t.cat_id
		FROM `forum_mess` AS m
		LEFT JOIN `forum_topics` AS t ON (m.t_id=t.t_id)
		WHERE m.m_id='{$m_id}'";

		$access = $sql->SetQuery($q, 'LoadRow');

		if(!guser::_hasAccess(self::$name, 'moder', $access['cat_id']) || empty($access))
			return "Вы не имеете доступа к данной операции";
		$check = $sql->setQuery("SELECT COUNT(*) FROM `banned` WHERE userid='{$data['userid']}'",'LoadSingle');
		if($check)
			 return "Пользователь уже забанен!";
		if($data["spam"] == 1){
			$data["date"] = "2038-12-1";
		}

		$sql->InsertArray("banned", $data);
		$sql->SetQuery("UPDATE `users` SET banned=1 WHERE userid='{$data['userid']}'");
		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit();
	}
	function RemoveBan()
	{
		global $sql, $gUser;


		$userid = zReq::GetVar('user_id', 'INT', 'GET', 0);
		if( !guser::_hasAccess(self::$name, 'rembans') || !$userid)
			return "Вы не имеете доступа к данной операции";

		$sql->SetQuery("DELETE FROM `banned` WHERE `userid` = '{$userid}'");
		$sql->SetQuery("UPDATE `users` SET banned=0 WHERE userid ='{$userid}'");

		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit();
	}
	function FindPage()
	{
		global $sql;
		$t_id = zReq::GetVar('t_id', 'INT', 'GET', 0);
		$m_id = zReq::GetVar('m_id', 'INT', 'GET', 0);
		$pag = $sql->setQuery("SELECT COUNT(*) FROM `forum_mess` WHERE `t_id` = {$t_id} AND `m_id` <{$m_id}",'LoadSingle');
		$finded   = ceil(($pag+1)/20);
		header("Location: ".DOMAIN."/forum/view/".$t_id."/?p=".$finded."#".$m_id);
		exit();
	}
	function RemoveWarning()
	{
		global $sql, $gUser;

		$userid = zReq::GetVar('userid', 'INT', 'GET', 0);
		$utime   = zReq::GetVar('utime', 'INT', 'GET', 0);
		if( !guser::_hasAccess(self::$name, 'warnings') || !$userid)
			return "Вы не имеете доступа к данной операции";

		$sql->SetQuery("DELETE FROM `dv_forum_warnings` WHERE `userid` = '{$userid}' AND `utime` = {$utime}");
		$sql->SetQuery("UPDATE `forum_uinfo` SET warnings=IF(warnings>0,warnings-1,0) WHERE userid='{$userid}'");

		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit();
	}
}

?>
