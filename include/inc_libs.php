<?php
require_once(ROOT_PATH."/lib/class.mysqli.php");
require_once(ROOT_PATH."/lib/class.rewrite.php");
require_once(ROOT_PATH."/lib/class.raintpl.php");
require_once(ROOT_PATH."/lib/ext.raintpl.php");
require_once(ROOT_PATH."/lib/class.page.php");
require_once(ROOT_PATH."/lib/class.pagination.php");
require_once(ROOT_PATH."/lib/class.request.php");
require_once(ROOT_PATH."/lib/class.upload.php");
require_once(ROOT_PATH."/lib/func.system.php");
require_once(ROOT_PATH."/lib/func.html.php");
require_once(ROOT_PATH."/lib/func.fields.php");
require_once(ROOT_PATH."/lib/admin/class.translit.php");
require_once(ROOT_PATH."/lib/admin/func.adminmenu.php");
require_once(ROOT_PATH."/lib/class.charts.php");

/* создаём основное подключение к БД */
$sql = new ClassDatabase(MYSQL_HOST_CMS, MYSQL_USER_CMS, MYSQL_PASS_CMS, MYSQL_DB_CMS);


/* задаём начальные значения title и т.п. */
ClassPage::SetTitle('');
ClassPage::SetKeywords('');
ClassPage::SetDescription('');

/* конфигурация шаблонизатора */
raintpl::configure( 'tpl_dir', ROOT_PATH."/templates/" );
raintpl::configure( 'cache_dir', ROOT_PATH."/cache/" );
raintpl::configure( 'path_replace', false );

/* проверяем авторизацию */
require_once(ROOT_PATH."/lib/class.session.php");

?>