<?php
class NewsView extends AdmcontNews
{
	function __construct()
	{
		parent::__construct();
	}

	function ViewNewsList()
	{
		global $sql, $gUser, $available_locale;

		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 1);
		$pagination->per_page     = self::$config['per_page'];

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		/*
		// здесь должны быть запросы на случай, если разрешено прикрепление нескольких изображений с разграничением прав. ссейчас тут мусор
		if (self::$config['multi_files'])
			$query = "
			SELECT n.*, COUNT(i.id) as ph_num
			FROM news AS n
			LEFT JOIN `news_images` AS i ON (i.id=n.id)
			GROUP BY n.id
			ORDER BY n.dtime DESC
			LIMIT {$limit_start}, {$pagination->per_page}";
		*/

		$where = array("1=1");

		if (!guser::_hasAccess(self::$name, 'edit_all') && guser::_hasAccess(self::$name, 'edit_my'))
		{
			$where[] = "n.userid={$gUser['userid']}";
		}

		if (self::$config['enable_cats'])
		{
			$cats = $sql->SetQuery("SELECT * FROM `news_cats`")->LoadByUniq('cat_id');

			$this->tpl->assign( "cats", $cats );

			$cat_id = zReq::getVar('cat_id', 'INT', 'GET', 0);

			if ($cat_id)
				$where[] = "n.cat_id={$cat_id}";
		}

		$locale = (defined(DEFAULT_LOCALE))?DEFAULT_LOCALE:$available_locale[0];

		$query = "
		SELECT n.*, t.*, u.login
		FROM `news` AS n
		LEFT JOIN `users` AS u ON (u.userid=n.userid)
		LEFT JOIN `news_text` AS t ON n.id=t.id
		WHERE ".implode(' AND ', $where)."
		GROUP BY n.id
		ORDER BY n.dtime DESC
		LIMIT {$limit_start}, {$pagination->per_page}
		";

		$news = $sql->SetQuery($query)->LoadAllRows();

		if (self::$config['enable_cats'])
		{
			$cats = $sql->SetQuery("SELECT * FROM `news_cats`")->LoadByUniq('cat_id');

			$this->tpl->assign( "cats", $cats );
		}

		## странички
		$query = "SELECT COUNT(*) FROM `news` AS v";

		if (guser::_hasAccess(self::$name, 'edit_my') && !guser::_hasAccess(self::$name, 'edit_all')) {$query .= " WHERE userid='{$gUser['userid']}'";}

		$pagination->total = $sql->SetQuery($query)->LoadSingle();
		$pagination->url_start = ADOMAIN."/".self::$name."/?p=";

		$this->tpl->assign( "pagination", $pagination->draw() );

		$this->tpl->assign( "news", $news );
		$this->tpl->assign( "gUser", $gUser );
		$this->tpl->assign( "config", self::$config );

		return $this->tpl->draw( "news_list" );
	}

	function ViewEditNews()
	{
		global $sql, $gUser, $available_locale;

		$id = zReq::GetVar('id', 'INT', 'GET', 0);

		if ($id)
		{
			$news = $sql->SetQuery("SELECT * FROM `news` WHERE id={$id}")->LoadRow();

			if ($news['published'] == 1 && !guser::_hasAccess(self::$name, 'edit_all'))
				return get_warning_html('Ошибка доступа', 'error', ADOMIN.'/'.self::$name.'/');

			$news['date'] = date('d.m.Y', strtotime($news['dtime']));
			$news['time'] = date('H:i', strtotime($news['dtime']));

			$translations = $sql->SetQuery("SELECT * FROM `news_text` WHERE id={$id}")->LoadByUniq('lang');
			$this->tpl->assign( "translations", $translations );

			if (self::$config['multi_files']) ## выдёргиваем прикреплённые фото, если позволяет конфиг
			{
				$query = "SELECT * FROM `news_images` WHERE id={$id}";

				$pics= $sql->SetQuery($query)->LoadAllRows();

				$this->tpl->assign( "pics", $pics );
			}
		}
		else
		{
			$news['date'] = date('d.m.Y', time());
			$news['time'] = date('H:i', time());
		}

		if (self::$config['enable_cats'])  ## если включено разделение новостей на категории
		{
			$cats = $sql->SetQuery("SELECT * FROM `news_cats`")->LoadAllRows();

			array_unshift($cats, array('cat_title' => $GLOBALS['model']['choose_cat'], 'cat_id' => 0));
			$cat_select = generate_select('cat_id', $cats, 'cat_id', 'cat_title', (isset($news['cat_id']))?$news['cat_id']:0);
			$this->tpl->assign( "cat_select", $cat_select );
		}

		$this->tpl->assign( "gUser", $gUser );
		$this->tpl->assign( "news", $news );
		$this->tpl->assign( "config", self::$config );
		$this->tpl->assign( "available_locale", $available_locale );

		return $this->tpl->draw( "edit_news" );
	}


	function ViewCatsList()
	{
		global $sql;

		$cats = $sql->SetQuery("SELECT * FROM `news_cats` ORDER BY cat_title")->LoadAllRows();

		$this->tpl->assign( "cats", $cats );

		return $this->tpl->draw( "cat_list" );
	}

	function ViewCatEdit()
	{
		global $sql;

		$cat_id = zReq::getVar('cat_id', 'INT', 'GET');

		if ($cat_id)
		{
			$cat = $sql->SetQuery("SELECT * FROM `news_cats` WHERE cat_id='{$cat_id}'")->LoadRow();

			$this->tpl->assign( "cat", $cat );
		}

		return $this->tpl->draw( "cat_edit" );
	}
}

?>
