<?php
$priority = 1;

global $sql;

$sql->SetQuery("SELECT * FROM `static_pages` WHERE published=1 AND cat_id>0");
$pages = $sql->LoadAllRows();

foreach($pages as $p)
{
	echo "<url>
		<loc>".DOMAIN."/{$p['alias']}.html</loc>
		<priority>{$priority}</priority>
	</url>
	";
}
?>