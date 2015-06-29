<?php

class zReq
{
    static function getMethod()
    {
        $method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        return $method;
    }

    /**
    *  ѕолучает данные из запроса. ѕроводит первичную обработку.
    *  @param string $name - им€ части запроса
    *  @param string $type - тип данных. ќпредел€ет, как будут обработаны данные запроса
    *  @param string $source - REQUEST_METHOD
    *  @param mixed $def_value - вернЄтс€, если искома€ часть запроса отсутствует
    *  @return mixed
    */
    static function getVar($name, $type, $source='default', $def_value = '')
    {
        global $sql;

        switch ($source)
        {
            case 'GET' :
                $input = &$_GET;
                break;
            case 'POST' :
                $input = &$_POST;
                break;
            case 'FILES' :
                $input = &$_FILES;
                break;
            case 'COOKIE' :
                $input = &$_COOKIE;
                break;
            case 'SESSION' :
                $input = &$_SESSION;
                break;
            default:
                $input = &$_REQUEST;
                $hash = 'REQUEST';
                break;
        }

        if (isset($input[$name]))
            $source = $input[$name];
        else
            return $def_value;

        switch (strtoupper($type))
        {
            case 'INT' :
            case 'INTEGER' :
                preg_match('/-?[0-9]+/', (string) $source, $matches);
                $result = @ (int) $matches[0];
                break;

            case 'FLOAT' :
            case 'DOUBLE' :
                $source = preg_replace('/\,/', '.',  $source);
                preg_match('/-?[0-9]+([.,][0-9]+)?/', (string) $source, $matches);
                $result = @ (float) $matches[0];
                break;

            case 'BOOL' :
            case 'BOOLEAN' :
                $result = (bool) $source;
                break;

            case 'STRING' :
                $result = (string) $source;
                break;

            case 'ARRAY' :
                $result = (array) $source;
                break;

            case 'SQL_ARRAY' :
                $result = (array) $source;
                foreach ($result as &$r)
                {
                    $r =  mysqli_real_escape_string( $sql->db_connect_id, $r );
                }
                break;

            case 'PATH' :
                $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
                preg_match($pattern, (string) $source, $matches);
                $result = (string) $matches[0];
                break;

            case "URL" :
                $pattern = '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
                if (preg_match($pattern, (string) $source))
                    $result = mysqli_real_escape_string( $sql->db_connect_id, (string) $source );
                else
                    $result = mysqli_real_escape_string ( $sql->db_connect_id, 'bad_url:'.(string) $source );

                break;

            case 'NOHTML_SQL':
                $result = mysqli_real_escape_string( $sql->db_connect_id,
                              preg_replace('/\\r\\n/', '<br>', strip_tags( $source ) )
                          );
                break;

            case 'HTML':
                $result = mysqli_real_escape_string( $sql->db_connect_id, $source );
                break;

            case 'SQL' :
            default:
                $result = mysqli_real_escape_string ( $sql->db_connect_id, $source );
                break;
        }

        if ($result === '')
            $result = $def_value;

        return $result;
    }

    static function isPost()
    {
        if ($_POST)
            return true;
    }
}

?>