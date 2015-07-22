<?php

$debug_info = '';

class ClassDatabase
{
    public $db_connect_id;
    public $query;
    public $query_result;
    public $row    = array();
    public $rowset = array();
    public $debug_mode = true;
    public $debug_total_time = 0;

    /**
     * Подключается к БД по переданным параметрам. В случае, если они не указаны - берутся значения из конфига сайта.
     * @param string $host - хост
     * @param string $user - пользователь
     * @param string $pass - пароль
     * @param string $dbname - имя БД
     */
    function __construct($host = 'localhost', $user = false, $pass = false, $dbname = false)
    {
        $this->db_connect_id = new mysqli($host, $user, $pass, $dbname);

        if( !$this->db_connect_id->connect_errno )
        {
            $this->db_connect_id->query("SET NAMES UTF8;");
        }
    }


    /**
     * Закрыть соединение с базой данных
     */
    function SqlClose()
    {
        if( $this->db_connect_id ) {
            return mysqli_close($this->db_connect_id);
        }
        else {
            return false;
        }
    }


    /**
     * Выполняет запрос к серверу БД
     * @param string $query - строка запроса
     * @param string $result - имя метода текущего класса. Если указан, то текущая функция вернёт данные в соответствии с указанным методом. Пример: $rows = $mysql->SetQuery("SELECT * FROM `test`", 'LoadAllRows')
     * @return mixed. В случае, если указан $result - данные. Если $result не указан - вернётся ID запроса
     */
    function SetQuery($query = "", $result = false)
    {
        unset($this->query_result);

        if (defined("DB_PREFIX"))
            $query = preg_replace('/\#\#/', DB_PREFIX, $query);
        else
            $query = preg_replace('/\#\#/', '', $query);

        $this->query = $query;

        if ($this->debug_mode)
        {
            global $debug_info;

            $msc = microtime(true);

            $this->query_result = $this->db_connect_id->query($query);

            $msc = microtime(true)-$msc;
            $debug_info .= "$query ($msc) <br />";
            $this->debug_total_time += $msc;
        }
        else
        {
            $this->query_result = $this->db_connect_id->query($query);
        }

        if (!$this->query_result)
        {
            echo "Ошибка базы данных. MySQL пишет:", $this->db_connect_id->error;
            exit;
        }

        if($result)
            return $this->$result();
        else
            return $this;
    }

    /**
     * Обрабатывает запрос и возвращает одно значение.
     * @param string $query_id - ID запроса
     */
    function LoadSingle($query_id = 0)
    {
        if( !$query_id ) {
            $query_id = $this->query_result;
        }

        if( $query_id ) {
            $temp = mysqli_fetch_array($query_id, MYSQL_NUM);
            return $temp[0];
        }
        else {
            return false;
        }
    }

    /**
     * Обрабатывает запрос и возвращает одномерный нумерованный массив
     * @param string $query_id - ID запроса
     */
    function LoadSingleArray($query_id = 0)
    {
        if( !$query_id ) {
            $query_id = $this->query_result;
        }

        if( $query_id )
        {
            if (isset($this->rowset)) unset($this->rowset);

            while($this->rowset = mysqli_fetch_array($query_id, MYSQL_NUM)) {
                $result[] = $this->rowset[0];
            }

            return isset($result) ? $result : false ;
            unset($result);
        }
        else {
            return false;
        }
    }

    /**
     * Обрабатывает __один__ ряд запроса и возвращает ассоциативный массив
     * @param string $query_id - ID запроса
     */
    function LoadRow($query_id = 0)
    {
        if( !$query_id ) {
            $query_id = $this->query_result;
        }

        if( $query_id ) {
            return mysqli_fetch_array($query_id, MYSQL_ASSOC);
        }
        else {
            return false;
        }
    }

    /**
     * Обрабатывает все ряды запроса и возвращает двумерный нумерованный ассоциативный массив
     * @param string $query_id - ID запроса
     */
    function LoadAllRows($query_id = 0)
    {
        if( !$query_id ) {
            $query_id = $this->query_result;
        }

        if( $query_id )
        {
            if (isset($this->rowset)) unset($this->rowset);

            while($this->rowset = mysqli_fetch_array($query_id, MYSQL_ASSOC)) {
                $result[] = $this->rowset;
            }

            return isset($result) ? $result : array() ;
            unset($result);
        }
        else {
            return false;
        }
    }

    /**
     * Обрабатывает все ряды запроса и возвращает двумерный ассоциативный массив, ключ к строкам - $uniq_name
     * @param string $uniq_name - имя ключевого столбца таблицы. Значение столбца станет ключом к строке в возвращённом массиве.
     * Правильный пример: $mysql->SetQuery("SELECT name, age FROM `users`"); $rows = $mysql->LoadByUniq('name');
     * НЕправильный пример: $mysql->SetQuery("SELECT age FROM `users`"); $rows = $mysql->LoadByUniq('name');
     */
    function LoadByUniq($uniq_name = false)
    {
        $query_id = $this->query_result;

        if( $uniq_name )
        {
            unset($this->rowset);

            while($this->rowset = mysqli_fetch_array($query_id, MYSQL_ASSOC)) {
                $temp = $this->rowset;
                $result[$temp[$uniq_name]] = $temp;
            }

            return isset($result) ? $result : false ;
            unset($result);
        }
        else {
            return false;
        }
    }

    /**
     * Функция отдает количество возвращаемых БД-сервером рядов (для SELECT-запросов)
     * @param string $query_id - ID запроса
     */
    function NumRows($query_id = 0) {
        if( !$query_id ) {
            $query_id = $this->query_result;
        }

        return ( $query_id ) ? mysqli_num_rows($query_id) : false;
    }

    function AffectedRows() {

        return mysqli_affected_rows($this->db_connect_id);
    }


    /**
     * Возвращает айдишник последней вставленной INSERTs-ом записи
     * @param string $query_id - ID запроса
     */
    function InsertId()
    {
        return mysqli_insert_id($this->db_connect_id);
    }


    /**
     * Вставляет в таблицу $table строку, определённую массивом $array
     * @param string $table - имя таблицы
     * @param string $array - хеш-массив. Ключ элемента - имя столбца. Значение элемента - устанавливаемое значение.
     */
    function InsertArray($table, $array)
    {
        $var = array();
        $val = array();

        foreach ($array AS $k=>$v) {
            $vars[] = "`".$k."`";

            if ($v != 'NOW()')
                $val[]  = "'".$v."'";
            else
                $val[]  = $v;
        }

        $var    = implode(",", $vars);
        $values = implode(",", $val);

        $query = "INSERT INTO ".$table." (".$var.") VALUES (".$values.")";

        if (defined("DB_PREFIX"))
            $query = preg_replace('/\#\#/', DB_PREFIX, $query);

        return $this->SetQuery($query);
    }

    public function insertUpdate($table = '', $data = array(), $keys = array())
    {
        if (!$table)
            return;

        if (empty($keys))
            $query = "INSERT IGNORE INTO `{$table}`";
        else
            $query = "INSERT INTO `{$table}`";

        $fields = array_keys($data);

        $query .= " (`".implode('`,`', $fields)."`) ";

        $query .= " VALUES ";

        $temp = array();
        foreach ($fields as $field)
        {
            $temp[] = $data[$field];
        }

        $query .= "('".implode("','", $temp)."')";

        if (!empty($keys)) {
            $query .= " ON DUPLICATE KEY UPDATE ";

            foreach ($fields as $field)
            {
                if (!in_array($field, $keys))
                    $query .= " {$field} = VALUES({$field}),";
            }

            $query = substr($query, 0, -1);
        }

        $query .= ";";

        return $this->SetQuery($query);
    }


    /**
     * Обновляет строку в таблице $table, определённую условием $where.
     * @param string $table - имя таблицы
     * @param string $array - хеш-массив. Ключ элемента - имя столбца. Значение элемента - устанавливаемое значение.
     * @param string $where - условие выбора строки.
     */
    function UpdateArray ($table, $array, $where) {
        $vars = array();

        foreach ($array AS $k=>$v) {
            if ($v != 'NOW()')
                $vars[]="`".$k."`='".$v."'";
            else
                $vars[]="`".$k."`=".$v."";
        }

        $var = implode(",", $vars);

        $query = "UPDATE ".$table." SET ".$var." WHERE ".$where;
        if (defined("DB_PREFIX"))
            $query = preg_replace('/\#\#/', DB_PREFIX, $query);

        $this->SetQuery($query);
    }

    /* создаёт подключение к удалённой БД */
    public static function _createConnection($connection = array())
    {
        $connection = get_object_vars(json_decode($connection));

        if (!$connection['host'] || !$connection['user'] || !$connection['pwd'] || !$connection['name'])
            return false;

        return new ClassDatabase($connection['host'], $connection['user'], $connection['pwd'], $connection['name']);
    }
}

?>