<?php
global $sql;

$sql->SetQuery("SELECT * FROM `news` WHERE published='1'");
$news = $sql->LoadAllRows();

foreach($news as $n)
{
	echo "<url>
		<loc>".DOMAIN."/news/{$n['alias']}/</loc>
		<priority>0.6</priority>
	</url>
	";
}
?> 