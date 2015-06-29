<?php
class StaticView extends AdmcontStatic
{
	function __construct()
	{
		parent::__construct();
    }

    function ViewCatsList()
    {
		global $sql;

		$cats = $sql->SetQuery("SELECT * FROM `static_cats`	ORDER BY cat_title DESC")->LoadAllRows();

		if (!empty($cats)) {
			$cats_ids = array();
			foreach ($cats as &$c)
			{
				$cats_ids[] = $c['cat_id'];
			}
			$cats_ids = '('.implode(",",$cats_ids).')';

			$sql->SetQuery("SELECT COUNT(*) as coun, cat_id FROM `static_pages` WHERE cat_id IN {$cats_ids} GROUP BY cat_id");
			$pages = $sql->LoadByUniq("cat_id");

			foreach ($cats as &$c)
			{
				$c['pages'] = ($pages[$c['cat_id']]['coun'])?$pages[$c['cat_id']]['coun']:0;
			}
		}
		$this->tpl->assign( "cats", $cats );

		return $this->tpl->draw( "cat_list" );
    }

	function ViewCatEdit()
	{
		$cat_id = zReq::getVar('cat_id', 'INT', 'GET');

		if ($cat_id)
		{
			global $sql;

			$cat = $sql->SetQuery("SELECT * FROM `static_cats` WHERE cat_id={$cat_id}")->LoadRow();

			$this->tpl->assign( "cat", $cat );

			global $langs;

			if (isset($langs))
			{
				$data = array();
				foreach ($langs as $k => $v)
				{
					$data[$k] = $sql->SetQuery("SELECT * FROM `{$k}_static_cats` WHERE cat_id={$cat_id}")->LoadRow();
				}
				$this->tpl->assign( "data", $data );
			}
		}

		return $this->tpl->draw( "cat_edit" );
	}

	function ViewPagesList($cat_id = -1)
	{
		global $sql;

		if ($cat_id < 0)
			$cat_id = zReq::getVar('cat_id', 'INT', 'GET', -1);

		$where = ($cat_id >= 0)?"p.cat_id='{$cat_id}'":"1=1";

		$query = "
			SELECT *
			FROM `static_pages` AS p
			LEFT JOIN `static_cats` AS c ON (c.cat_id=p.cat_id)
			LEFT JOIN `static_text` AS t ON (t.id=p.id)
			WHERE {$where}
			GROUP BY p.id
		";

		$pages = $sql->SetQuery($query)->LoadAllRows();

		$this->tpl->assign( "cat_id", $cat_id );
		$this->tpl->assign( "pages", $pages );

		return $this->tpl->draw( "pages_list" );
	}

	function PageEdit()
	{
		global $sql, $available_locale;

		$id = zReq::getVar('id', 'INT', 'GET');

		if ($id)
		{
			$page = $sql->SetQuery("SELECT * FROM `static_pages` WHERE id='{$id}'")->LoadRow();

			$translations = $sql->SetQuery("SELECT * FROM `static_text` WHERE id={$id}")->LoadByUniq('lang');
			$this->tpl->assign( "translations", $translations );

			$this->tpl->assign( "page", $page );
		}

		## выбор категории
		$cats = $sql->SetQuery("SELECT * FROM `static_cats`")->LoadAllRows();

		$cats[] = array('cat_id' => 0, 'cat_title' => 'Корзина');

		if (isset($page['cat_id']))
			$cat_select = generate_select('cat_id', $cats, 'cat_id', 'cat_title', $page['cat_id']);
		else
			$cat_select = generate_select('cat_id', $cats, 'cat_id', 'cat_title', '');

		$this->tpl->assign( "cat_select", $cat_select );
		##

		$this->tpl->assign( "available_locale", $available_locale );

		return $this->tpl->draw( "page_edit" );
	}
}

?>
