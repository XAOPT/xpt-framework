<?php

class ContForum
{
	var $tpl;
	static $name = 'forum';

	public static $post_per_page = 20;

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);

		require_once(ROOT_PATH."/lib/class.bb.php");

		$this->smiles = array();
		$this->smiles['smile'] = ":smile:";
		$this->smiles['yes'] = ":yes:";
		$this->smiles['yazik'] = ":yazik:";
		//$this->smiles['ohmy'] = ":ohmy:";
		$this->smiles['newcry'] = ":cry:";
		$this->smiles['sosew'] = ":sosew:";
		$this->smiles['palevo'] = ":palevo:";
		$this->smiles['prayy'] = ":pray:";
		$this->smiles['sad'] = ":sad:";
		$this->smiles['zloy'] = ":zloy:";
		$this->smiles['lol'] = ":lol:";
		$this->smiles['fie'] = ":fie:";
		$this->smiles['avtorklif'] = ":facepalm:";
		$this->smiles['metal'] = ":metal:";
		$this->smiles['rickroll'] = ":rickroll:";
		$this->smiles['dunno'] = ":dunno:";
		$this->smiles['vau'] = ":vau:";
		$this->smiles['nate'] = ":nate:";
		$this->smiles['avtoradolf'] = ":avtoradolf:";
		//$this->smiles['dance'] = ":dance:";
		$this->smiles['buba'] = ":buba:";
		//$this->smiles['huyase'] = ":huyase:";
		//$this->smiles['ispug'] = ":ispug:";

		$this->smiles['subj'] = ":subj:";
		$this->smiles['shaytan'] = ":hatol:";
		$this->smiles['shok'] = ":O_O:";
		$this->smiles['spy'] = ":spy:";
		$this->smiles['veblo_1'] = ":veblo_1:";
		$this->smiles['xdnew'] = ":xd:";
		$this->smiles['metalhead'] = ":metalhead:";
		$this->smiles['spydance'] = ":spydance:";
		$this->smiles['palevojein'] = ":palevojein:";
		$this->smiles['geys'] = ":geys:";
		$this->smiles['geypalevonew'] = ":geypalevo:";
		$this->smiles['parovozdjan'] = ":parovozdjan:";
		$this->smiles['ohpalevo'] = ":ohpalevo:";
		$this->smiles['opasnoste'] = ":opasnoste:";
		$this->smiles['vihui'] = ":vihui:";
		$this->smiles['mameprivet'] = ":mameprivet:";
		//$this->smiles['pidorasy'] = ":pidorasy:";
		$this->smiles['omg'] = ":._.:";


		$this->smiles['po_weke'] = ":po_weke:";
		$this->smiles['fffuuu'] = ":fffuuu:";
		/* $this->smiles['trollface'] = ":trollface:"; */
		$this->smiles['trollface2'] = ":trollface2:";
		$this->smiles['petro'] = ":petro:";
		$this->smiles['nono'] = ":nono:";
		$this->smiles['-_-'] = ":-_-:";
		$this->smiles['ginsgnil'] = ":ginsgnil:";
		$this->smiles['spasibo_podrochil'] = ":fapfap:";
		$this->smiles['hmm'] = ":hmm:";

		$this->smiles['boss'] = ":boss:";
		$this->smiles['clown'] = ":clown:";
		$this->smiles['wizard'] = ":mage:";
		$this->smiles['lostneprowaet'] = ":lostneprowaet:";
		$this->smiles['perec'] = ":perec:";
		$this->smiles['bayan'] = ":bayan:";
		$this->smiles['1drhouse'] = ":drhouse:";
		$this->smiles['facepalm'] = ":facepalm2:";
		$this->smiles['fuckyea'] = ":fuckyea:";
		$this->smiles['russian'] = ":russian:";
		$this->smiles['yep'] = ":yep:";
		$this->smiles['1alkawi'] = ":alkawi:";
		$this->smiles['blush'] = ":blush:";
		$this->smiles['nukanuka'] = ":nukanuka:";
		$this->smiles['stinker'] = ":stinker:";

		//$this->smiles['snobuedance'] = ":snobuedance:";
	}

	function showModule($module_name)
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		switch ($action) {
			case 'alone_block':
				return $model->AloneBlock();
				break;
			case 'last_topics':
				return $model->LastTopics();
				break;
		}
	}

	function Action($action = '')
	{
		global $gUserid;

		$arAction = array('reply', 'etopic', 'vote', 'poll_open','poll_delete','poll_close', 'create_topic', 'delete_topic', 'publish', 'snap', 'update_mess', 'delmess','add_warning','add_bans','remove_warning','remove_ban','find_page');

		if (in_array($action, $arAction))
		{
			$prefix = 'action';
		}
			else
			$prefix = 'view';

		if (in_array($action, array('delete_topic', 'publish', 'snap', 'add_warning','add_bans')))
		{
			if (!guser::_hasAccess(self::$name, 'moder'))
				return "Ошибка доступа. Отказать";
		}

		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);

		$model = new $model_name;

		switch ( $action )
		{
			case 'find_page':
				return $model->FindPage();
			case 'remove_ban':
				return $model->RemoveBan();
			case 'add_bans':
				return $model->AddBan();
			case 'remove_warning':
				return $model->RemoveWarning();
			case 'add_warning':
				return $model->AddWarning();
			case 'create_topic':
				return $model->CreateTopic();
			case 'delete_topic':
				return $model->DeleteTopic();
			case 'publish':
				return $model->PublishTopic();
			case 'snap':
				return $model->SnapTopic();
			case 'reply':
				return $model->AddReply();
			case 'update_mess':
				return $model->UpdateMess();
			case 'etopic':
				return $model->EditTopic();
			case 'vote':
				return $model->AddVote();
			case 'poll_open':
				return $model->ChangePollState('open');
			case 'poll_close':
				return $model->ChangePollState('close');
			case 'poll_delete':
				return $model->DeletePoll();
			case 'delmess':
				return $model->DeleteMess();
			## view
			case 'editmess':
				return $model->ViewEditmess();
			case 'view':
				return $model->ViewTopic();
			case 'usermess':
				return $model->UserMess();
			case 'cat':
				return $model->ViewCat();
			case 'search':
				return $model->SearchTopic();
			case 'edit_topic':
				return $model->EditTopic();
			case 'newtopic':
				return $model->NewTopic();
			default:
				return $model->ViewForumIndex();
		}
	}
}

?>
