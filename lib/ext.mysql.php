<?php

class ExtentionDatabase
{
    /**
     * Ссылка на объект-контроллер БД
     *
     * @var ref
     */
    var $sql;

    function __construct(&$sql_object)
    {
        $this->sql = $sql_object;
    }

    /**
     *  Изменяет положение записи в таблице. Например, для управления сортировкой вверх-вниз в админ-панели. Первый (1) элемент самый "верхний".
     *  @param string $direction - направление движения ('up','down')
     *  @param string $table     - имя таблицы в БД
     *  @param string $key_name  - имя поля ключа
     *  @param mixed $key_value - значение ключа. Эту строку мы "двигаем"
     *  @param string $where_condition - дополнительное условие для передвигаемой строки
     *  @param string $order_name - имя поля, содержащего значение порядка. По умолчанию "position"
     *  @return boolean
     */
    function ChangePosition($direction, $table, $key_name, $key_value, $where_condition = '1=1', $order_name='position')
    {
        if (($direction != 'up' && $direction != 'down') || !$table || !$key_name || !$key_value)
            return false;

        $current_position = $this->sql->SetQuery("SELECT `{$order_name}` FROM `{$table}` WHERE {$key_name}='{$key_value}' AND {$where_condition}", 'LoadSingle');

        if (!(int)$current_position)
            return false;
        if ($direction == 'up' && $current_position == 1) ## выше некуда
            return false;

        if ($direction == 'down')
        {
            $max_position = $this->sql->SetQuery("SELECT MAX({$order_name}) FROM `{$table}` WHERE {$where_condition}", 'LoadSingle');

            if ($current_position == $max_position) ## ниже некуда
                return false;
        }

        if ($direction == 'up')
        {
            $this->sql->SetQuery("UPDATE `{$table}` SET `{$order_name}`=`{$order_name}`+1 WHERE `{$order_name}`={$current_position}-1 AND {$where_condition}");
            $this->sql->SetQuery("UPDATE `{$table}` SET `{$order_name}`=`{$order_name}`-1 WHERE `{$key_name}`='{$key_value}' AND {$where_condition}");
        }
        else
        {
            $this->sql->SetQuery("UPDATE `{$table}` SET `{$order_name}`=`{$order_name}`-1 WHERE `{$order_name}`={$current_position}+1 AND {$where_condition}");
            $this->sql->SetQuery("UPDATE `{$table}` SET `{$order_name}`=`{$order_name}`+1 WHERE `{$key_name}`='{$key_value}' AND {$where_condition}");
        }

        return true;
    }

    /**
    *  Пересчитывает поле сортировки в таблице и "заполняет дыры" после, например, удаления записи.
    *  @param string $table - имя таблицы в БД. Обязательный параметр
    *  @param string $where_condition - дополнительное условие для передвигаемой строки
    *  @param string $order_name - имя поля, содержащего значение порядка. По умолчанию "position"
    *  @return Возвращает false в случае ошибки и количество найденных "дыр" в сортировке в случае успеха
    */
    function ReorderTable($table, $where_condition = '1=1', $order_name='position')
    {
        if (!$table)
            return false;

        $max_position = $this->sql->SetQuery("SELECT MAX({$order_name}) FROM `{$table}` WHERE {$where_condition}", 'LoadSingle');

        $positions = $this->sql->SetQuery("SELECT `{$order_name}` FROM `{$table}` WHERE {$where_condition} ORDER BY `{$order_name}`", 'LoadSingleArray');

        $holes = 0;
        for ($i=1; $i<=$max_position; $i++)
        {
            if (!in_array($i, $positions))
            {
                $max_position--;
                $holes++;

                $this->sql->SetQuery("UPDATE `{$table}` SET `{$order_name}`={$order_name}-1 WHERE `{$order_name}`>{$i} AND {$where_condition}");

                foreach ($positions as &$p) $p = ($p>$i)?$p-1:$p;
            }
        }

        return $holes;
    }

    /**
    * Возвращает отсортированное дерево записей
    * @return array
    */
    function BuildSqlTree($parent_id, $lvl)
    {
        return '';
    }

    /**
     * Получает конфиг из БД. Таблица должна содержать по меньшей мере два поля: key и value
     * @param string $table - имя таблицы
     * @param string $condition  - хеш условий для выборки
     * @return string
     */
    static function GetStdConfig($table = '', $condition = array())
    {
        global $sql;

        $config = array();

        if ($table)
        {
            $query = "SELECT `key`, `value` FROM `{$table}` WHERE 1=1";
            foreach ($condition as $k => &$v)
            {
                $query .= " AND `{$k}`='{$v}'";
            }
            $sql->SetQuery($query);
            $options = $sql->LoadAllRows();

            foreach ($options as &$option)
            {
                $config[$option['key']] = $option['value'];
            }
        }

        return $config;
    }

    /**
     * Сохраняет конфиг в БД
     * @param string $table - имя таблицы
     * @param string $condition  - хеш условий для выборки
     * @return string
     */
    static function SaveStdConfig($table = '', $var_keys = array(), $condition = array())
    {
        global $sql;

        if ($table)
        {
            $condition = "";
            foreach ($condition as $k => &$v)
            {
                $condition .= " AND `{$k}`='{$v}'";
            }

            foreach ($var_keys as $k=>&$v)
            {
                $val = zReq::getVar($k, $v);

                $query = "SELECT count(*) FROM `{$table}` WHERE `key`='{$k}' {$condition}";
                $num_rows = $sql->SetQuery($query, 'LoadSingle');

                if  ($num_rows)
                {
                    $query = "UPDATE `{$table}` SET `value`='{$val}' WHERE `key`='{$k}' {$condition};";
                    $sql->SetQuery($query);
                }
                else
                {
                    $query = "INSERT INTO `{$table}` (`key`, `value`) VALUES ('{$k}', '{$val}')";
                    $sql->SetQuery($query);
                }
            }
        }
    }
}

?>