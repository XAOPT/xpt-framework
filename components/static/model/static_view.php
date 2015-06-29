<?php
class StaticView extends ContStatic
{
	function __construct()
	{
		parent::__construct();
	}

	function ViewItem($id = 0)
	{
		global $sql, $langs, $gUserid;

		if (!$id)
			$id = zReq::getVar('id', 'INT', 'GET');

		$sql->SetQuery("SELECT * FROM `static_pages` WHERE id='{$id}'");
		$page = $sql->LoadRow();

		if (defined('LANGUAGE'))
		{
			$sql->SetQuery("SELECT * FROM `".LANGUAGE."_static_pages` WHERE id='{$id}'");
			$fpage = $sql->LoadRow();

			$page['html'] = $fpage['html'];
			$page['title'] = $fpage['title'];
		}

		if( preg_match( '/\{function="([^(]*)\(([^)]*){0,1}\)"\}/', $page['html'], $code ) )
		{

			$function = $code[ 1 ];

			$params = explode(',',$code[2]);
			foreach ($params as &$p)
				$p = preg_replace('/\'/','',$p);

			$page['html'] = preg_replace('/\{function="([^(]*)(\([^)]*\)){0,1}"\}/',$code[1]($params[0],$params[1]),$page['html']);
		}

		ClassPage::SetKeywords($page['keywords']);
		ClassPage::SetDescription($page['description']);
		ClassPage::AddToTitle($page['title']);

		$this->tpl->assign( "page", $page );

		return $this->tpl->draw( "page", true );
	}

	function StaticBlock($option)
	{
		global $sql;

		$filed_name = ((int)$option > 0)?'id':'alias';

		$locale = DEFAULT_LOCALE;

		$query = "
			SELECT *
			FROM `static_pages` AS p
			LEFT JOIN `static_text` AS t ON p.id=t.id
			WHERE {$filed_name}='{$option}' AND published='1' AND t.lang = '{$locale}'
		";
		$page = $sql->SetQuery($query)->LoadRow();

		if (!$page['id'])
			return 'block not found';

		$this->tpl->assign( "page", $page );
		return $this->tpl->draw( "page_block", true );
	}
}

?>