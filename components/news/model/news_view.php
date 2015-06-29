<?php
class NewsView extends ContNews
{
	function __construct()
	{
		parent::__construct();
	}

	function NewsBlock($options)
	{
		global $sql, $gLocale;

		$limit = 3;

		if (isset($options['count']))
			$limit = $options['count'];

		if (isset($options['cat_id']))
			$q = "SELECT * FROM `news` WHERE published='1' AND cat_id='{$options['cat_id']}' ORDER BY dtime DESC LIMIT 0, $limit";
		else
			$q = "
				SELECT *
				FROM `news` AS n
				LEFT JOIN `news_text` AS t ON (t.id=n.id)
				WHERE published='1' AND t.lang = '{$gLocale}'
				ORDER BY dtime DESC LIMIT 0, $limit";

		$news = $sql->SetQuery($q)->LoadAllRows();

		if (!$news)
			return $this->tpl->draw( "news_block" );

		$month = array(0,'янв','фев','март','апр','мая','июня','июля','авг','сен','окт','ноя','дек');

		foreach ($news as &$n)
		{
			$temp = strtotime($n['dtime']);
			$n['word_month'] = $month[date('n', $temp)];

			$n['time'] = date('H:m', strtotime($n['dtime']));
		}
		$this->tpl->assign( "news", $news );

		return $this->tpl->draw( "news_block" );
	}

	function ViewNewsList()
	{
		global $sql, $gLocale;

		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 1);
		$pagination->per_page     = self::$config['per_page'];

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		$locale = DEFAULT_LOCALE;

		$q = "
		SELECT n.*, t.*, u.email
		FROM `news` AS n
		LEFT JOIN `news_text` AS t ON t.id=n.id
		LEFT JOIN `users` AS u ON (u.userid=n.userid)
		WHERE n.published='1' AND lang='{$gLocale}'
		ORDER BY n.dtime DESC
		LIMIT {$limit_start}, {$pagination->per_page}";

		$news = $sql->SetQuery($q)->LoadAllRows();

		if (!$news)
			return $this->tpl->draw( "news_list" );

		foreach ($news as &$n)
		{
			$n['date'] = date('d.m.Y', strtotime($n['dtime']));
			$n['time'] = date('H:m', strtotime($n['dtime']));
		}

		$this->tpl->assign( "news", $news );

		## странички
		$query = "
		SELECT COUNT(*)
		FROM `news` AS v
		WHERE published='1'
		";
		$sql->SetQuery($query);


		$pagination->total = $sql->LoadSingle();
		$pagination->url_start = DOMAIN."/".self::$name."/?p=";

		$this->tpl->assign( "pagination", "1221" );
		return $this->tpl->draw( "news_list" );
	}

	function FullView($alias = false)
	{
		global $sql, $gLocale;

		if (!$alias)
			$alias = zReq::getVar('id', 'INT', 'GET');

		$q = "
		SELECT n.*, t.*, u.login, u.avatar
		FROM `news` AS n
		LEFT JOIN `news_text` AS t ON (t.id=n.id)
		LEFT JOIN `users` AS u ON (u.userid=n.userid)
		WHERE n.alias='{$alias}' AND lang='{$gLocale}'
		";

		$news = $sql->SetQuery($q)->LoadRow();

		if (empty($news) && (int)$alias > 0)
		{
			$q = "
			SELECT alias
			FROM `news` AS n
			LEFT JOIN `users` AS u ON (u.userid=n.userid)
			WHERE n.id='{$alias}'
			";

			$test = $sql->SetQuery($q, 'LoadSingle');
			if ($test)
				error_301(DOMAIN.'/news/'.$test.'/');
		}

		if ($news['published'] == 0)
		{
			if (!guser::_hasAccess(self::$name) )
			{
				error_404();
			}
		}

		$this->tpl->assign( "news", $news );

		//$sql->SetQuery("SELECT * FROM `news_images` WHERE id='{$id}'");
		//$photos = $sql->LoadAllRows();
		//$this->tpl->assign( "photos", $photos );

		$q = "
			SELECT *
			FROM `news` AS n
			LEFT JOIN `news_text` AS t ON (t.id=n.id)
			WHERE published=1 AND lang='{$gLocale}'
			ORDER BY dtime DESC
			LIMIT 0, 3
		";

		$dop_news = $sql->SetQuery($q)->LoadAllRows();

		$month = array(0,'янв','фев','март','апр','мая','июня','июля','авг','сен','окт','ноя','дек');

		foreach ($dop_news as &$d)
		{
			$d['word_month'] = $month[date('n', strtotime($d['dtime']))];
		}
		$this->tpl->assign( "dop_news", $dop_news );

		$sql->SetQuery("UPDATE `news` SET views=views+1 WHERE alias='{$alias}'");

		if (self::$config['coms_enabled'])
		{
			$this->tpl->assign( "comments", show_module('comments', 'comments_block', array('news', $news['id'], DOMAIN."/".self::$name."/{$news['alias']}/")) );
		}

		ClassPage::SetTitle($news['title']);
		ClassPage::SetDescription("");
		ClassPage::SetKeywords("");

		return $this->tpl->draw( "fullview" );
	}
}

?>
