<?php
class SearchAction extends ContSearch
{
	function __construct()
	{
		parent::__construct();
	}

	function SearchResult()
	{
		global $sql, $rewrite;

		$component = $rewrite->component;

		$error      = zReq::getVar('error', 'NOHTML_SQL', 'GET', 1);

		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 1);
		$pagination->per_page = 30;

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		$searchtext = zReq::getVar('st', 'NOHTML_SQL', 'REQUEST', '');

		if(mb_strlen($searchtext) < 3 && $error != 'ok')
		{
			header("Location: ".DOMAIN."/".$component."/?st=".stripslashes($searchtext)."&error=ok");
			exit();
		}

		$sql->SetQuery("SELECT * FROM `search_data`");
		$search_data = $sql->LoadAllRows();

		$output = array();

		if (!empty($search_data))
		foreach ($search_data as $sd) ## перебираем таблицы
		{
			## объединяем в один запрос перечень полей для поиска в данной таблице
			$fields = explode(',', $sd['field']);

			$st = str_ireplace(" ","%",$searchtext);
			$where = array();
			foreach ($fields as $f)
			{
				$where[] = "v.{$f} LIKE '%{$st}%'";
			}

			##
			if ($sd['table'] == 'video')
			{
				$sql->SetQuery("SELECT cat_id FROM `cat_tlvl` WHERE cat_name LIKE '%{$st}%'");
				$cats = $sql->LoadSingleArray();

				if (!empty($cats))
				{
					$cats = implode(',',$cats);

					$where[] = "v.tlvl IN ({$cats})";
				}
			}

			$condition = implode(' OR ', $where);

			if ($sd['table'] == 'video')
			{

				$total = $sql->SetQuery("SELECT COUNT(*)
				FROM `video` AS v
				LEFT JOIN `cat_tlvl` AS t ON (v.tlvl = t.cat_id)
				WHERE ({$condition}) AND published='1'", 'LoadSingle');

				$q = "
				SELECT v.*, t.cat_name as cat_name
				FROM `video` AS v
				LEFT JOIN `cat_tlvl` AS t ON (v.tlvl = t.cat_id)
				WHERE ({$condition}) AND published='1' ORDER BY dtime DESC
				LIMIT {$limit_start}, {$pagination->per_page}";
			}
			else
			{
				$total = $sql->SetQuery("SELECT COUNT(*) FROM `{$sd['table']}` AS v WHERE {$condition} LIMIT {$limit_start}, {$pagination->per_page}");
				$q = "SELECT * FROM `{$sd['table']}` AS v WHERE {$condition} LIMIT {$limit_start}, {$pagination->per_page}";
			}

			$sql->SetQuery($q);
			$results = $sql->LoadAllRows();

			if (!empty($results))
			foreach ($results as $r) ## перебираем результаты поиска для данной даблицы
			{
				$temp = array(
					'link_text' => $r['title'],
					'link'      => $sd['link_format'],
					'component' => $sd['ctitle'],
					'cname'     => $sd['cname'],
					'v_id'      => $r['v_id'],
					'alias'      => $r['alias'],
					'title'     => $r['title'],
					'comments'  => $r['comments'],
					'rating'    => $r['rating'],
					'img'       => $r['img'],
					'tlvl'      => $r['tlvl'],
					'cat_name'  => $r['cat_name']
				);

				## генерация предпросмотра
				foreach ($fields as $f)
				{
					if ($r[$f] != $temp['link_text'])
					{
						$r[$f]      = mb_strtolower($r[$f], 'UTF-8');
						$searchtext = mb_strtolower($searchtext, 'UTF-8');

						preg_match("/(.{0,80}){$searchtext}(.{0,80})/ui", strip_tags($r[$f]), $matches);
						if (!empty($matches[0]))
						{
							$temp['preview'] = $matches[0];

							## добавляем по необходимости многоточие в конец и начало строки
							preg_match("/(.{0,81}){$searchtext}(.{0,81})/iu", strip_tags($r[$f]), $checking_matches);
							if ($checking_matches[1] != $matches[1])
								$temp['preview'] = '...'.$temp['preview'];
							if ($checking_matches[2] != $matches[2])
								$temp['preview'] = $temp['preview'].'...';
							####

							$temp['preview'] = preg_replace("/{$searchtext}/", "<span class='search_founded'><b>{$searchtext}</b></span>",$temp['preview']);
						}
					}
				}
				#####

				## формеруем ссылку на материал по шаблону
				$temp['link'] = preg_replace('/\{domain\}/', DOMAIN, $temp['link']);
				$temp['link'] = preg_replace('/\{component\}/', $sd['cname'], $temp['link']);
				preg_match('/\{(.*?)\}/', $temp['link'], $matches);

				$length = count($matches);
				for ($i=1; $i < $length; $i++ )
				{
					$temp['link'] = preg_replace('/\{'.$matches[$i].'\}/', $r[$matches[$i]], $temp['link']);
				}
				#####

				if (!isset($r['published']) || $r['published'] == 1 || $r['published']=='Y')
					$output[] = $temp;
			}
		}

		$pagination->total = $total;
		if ($pagination->total > $pagination->per_page)
		{
			$pagination->url_start = DOMAIN."/".self::$name."/?st=".stripslashes($searchtext)."&p=";

			$this->tpl->assign( "pagination", $pagination->draw() );
		}
		else
			$this->tpl->assign( "pagination", '' );

		$this->tpl->assign( "searchtext", stripslashes($searchtext) );
		$this->tpl->assign( "results", $output );
		$this->tpl->assign( "error", $error);

		return $this->tpl->draw( "search_result" );
	}

}

?>