<?php
function admin_menu($components = array()) {
    global $rewrite, $gLocale;

    $html = '<ul class="navigation">';

    foreach ( $components as $component_name ) {
        if (guser::_hasAccess ( $component_name )) {

            $menu = ROOT_PATH. '/components/' . $component_name . '/admin/'.$component_name.'_menu.json';
            $lang = ROOT_PATH . '/components/' . $component_name . '/admin/lang/' . $gLocale . '.txt';

            $local = array();

            if (file_exists($lang)) {
                $local = include $lang;
            }

            if (file_exists($menu))
            {
                $items = json_decode(file_get_contents($menu), true);

                $html .= '<li class="mm-dropdown">';

                $title = isset($local[$items['title']]) ? $local[$items['title']] : $items['title'];

                $html .= "
                    <a href='#'><i class='menu-icon fa {$items['icon']}'></i><span class='mm-text mmc-dropdown-delay fadeIn'>{$title}</span></a>
                    <ul class='mmc-dropdown-delay fadeInLeft'>
                ";

                foreach($items['actions'] as $url) {

                    $access = $url['access'];
                    $url_title = isset($local[$url['title']]) ? $local[$url['title']] : $url['title'];


                    if (empty($access) || guser::_hasAccess($component_name, $access))
                    {
                        $html .= "
                        <li>
                            <a href='/admin/".$component_name.$url['href']."'><span class='mm-text'>{$url_title}</span></a>
                        </li>
                        ";
                    }
                }

                $html .= "</ul>";
            }
        }
    }

    $html .= "</ul>";

    return $html;
}

?>