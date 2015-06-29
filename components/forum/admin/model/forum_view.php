<?php
class ForumView extends AdmcontForum
{
	var $forum_cats = array();

	function __construct()
	{
		parent::__construct();
	}

	function ViewCatsList()
	{
		$this->BuildCatsTree(0,0);

		$this->tpl->assign('forum_cats', $this->forum_cats);

		return $this->tpl->draw( "cat_list" );
	}


	function ViewCatEdit()
	{
		$cat_id = zReq::getVar('id', 'INT', 'GET');

		if ($cat_id)
		{
			global $sql;

			$sql->SetQuery("SELECT * FROM `forum_cats` WHERE cat_id='{$cat_id}'");
			$cat = $sql->LoadRow();

			$this->tpl->assign( "cat", $cat );
		}

		if (!isset($cat['cat_id'])) $cat['cat_id'] = 0;

		$this->tpl->assign('parent_selector', $this->GetParentsSelect(isset($cat['parent'])?$cat['parent']:0, $cat['cat_id']));
		//$this->tpl->assign('access', $access);

		return $this->tpl->draw( "cat_edit" );
	}


	function GetParentsSelect( $parent, $selected = '')
	{
		$this->BuildCatsTree(0,0);
		$cats = $this->forum_cats;

		foreach ($cats as &$c)
		{
			$c['cat_title'] = str_repeat('&mdash;', $c['level']).' '.$c['cat_title'];
		}

		array_unshift($cats, array('cat_title' => '= Выберите родительскую категорию =', 'cat_id' => 0));

		if (!empty($selected))
			$cat_select = generate_select('parent', $cats, 'cat_id', 'cat_title', $parent);
		else
			$cat_select = generate_select('parent', $cats, 'cat_id', 'cat_title', '');

		return $cat_select;
		echo $cat_select;
	}

	function BuildCatsTree($pid, $lvl)
	{
		global $sql;

		$q = "SELECT * FROM `forum_cats` WHERE parent = '{$pid}' ORDER BY cat_title";
		$sql->SetQuery($q);
		$local_forum_cats = $sql->LoadAllRows();

		if (!empty($local_forum_cats))
		foreach ($local_forum_cats as $cat)
		{
			$cat['level'] = $lvl; // глубина вложенности

			$this->forum_cats[] = $cat;

			$this->BuildCatsTree($cat['cat_id'], ($lvl+1));
		}
	}
}

?>