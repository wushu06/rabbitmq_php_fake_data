<?php
namespace RabbitMQ;


/**
 * Class Database
 * @package RabbitMQ
 */
class Database
{
    /**
     * @var \mysqli
     */
    protected $connection;
    /**
     * @var
     */
    protected $query;
    /**
     * @var bool
     */
    protected $show_errors = true;
    /**
     * @var bool
     */
    protected $query_closed = true;
    /**
     * @var int
     */
    public $query_count = 0;

    /**
     * Database constructor.
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param string $charset
     */
    public function __construct($dbhost = null, $dbuser = null, $dbpass = null, $dbname = null, $charset = 'utf8')
    {
        $dbhost = $dbhost ?: $_ENV['DB_HOST'];
        $dbuser = $dbuser ?: $_ENV['DB_USERNAME'];
        $dbpass = $dbpass ?: $_ENV['DB_PASSWORD'];
        $dbname = $dbname ?: $_ENV['DB_NAME'];

        $this->connection = new \mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if ($this->connection->connect_error) {
            $this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
        }
        $this->create();
        $this->connection->set_charset($charset);

    }

    /**
     * @return mixed
     */
    private function create()
    {
        $table = $_ENV['DB_TABLE'];
        $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS $table (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                  `email` varchar(255) NOT NULL DEFAULT 'default' COMMENT 'Email',
                  `is_subscribed` int(11) NOT NULL DEFAULT '0' COMMENT 'Subscribed',
                  `name` varchar(255) NOT NULL DEFAULT 'general' COMMENT 'Name',
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated At',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Data';
EOSQL;
        return $this->query($sql);
    }
    /**
     * @param $query
     * @return $this
     */
    public function query($query)
    {
        if (!$this->query_closed) {
            $this->query->close();
        }
        if ($this->query = $this->connection->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_slice($x, 1);
                $types = '';
                $args_ref = [];
                foreach ($args as $k => &$arg) {
                    if (is_array($args[$k])) {
                        foreach ($args[$k] as $j => &$a) {
                            $types .= $this->_gettype($args[$k][$j]);
                            $args_ref[] = &$a;
                        }
                    } else {
                        $types .= $this->_gettype($args[$k]);
                        $args_ref[] = &$arg;
                    }
                }
                array_unshift($args_ref, $types);
                call_user_func_array([$this->query, 'bind_param'], $args_ref);
            }
            $this->query->execute();
            if ($this->query->errno) {
                $this->error('Unable to process MySQL query (check your params) - ' . $this->query->error);
            }
            $this->query_closed = false;
            $this->query_count++;
        } else {
            $this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->connection->error);
        }
        return $this;
    }

    /**
     * @param null $callback
     * @return array
     */
    public function fetchAll($callback = null)
    {
        $params = [];
        $row = [];
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array([$this->query, 'bind_result'], $params);
        $result = [];
        while ($this->query->fetch()) {
            $r = [];
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback != null && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value == 'break') {
                    break;
                }
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    /**
     * @return array
     */
    public function fetchArray()
    {
        $params = [];
        $row = [];
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array([$this->query, 'bind_result'], $params);
        $result = [];
        while ($this->query->fetch()) {
            foreach ($row as $key => $val) {
                $result[$key] = $val;
            }
        }
        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return $this->connection->close();
    }

    /**
     * @return mixed
     */
    public function numRows()
    {
        $this->query->store_result();
        return $this->query->num_rows;
    }

    /**
     * @return mixed
     */
    public function affectedRows()
    {
        return $this->query->affected_rows;
    }

    /**
     * @return mixed
     */
    public function lastInsertID()
    {
        return $this->connection->insert_id;
    }

    /**
     * @param $error
     */
    public function error($error)
    {
        if ($this->show_errors) {
            exit($error);
        }
    }

    /**
     * @param $var
     * @return string
     */
    private function _gettype($var)
    {
        if (is_string($var)) {
            return 's';
        }
        if (is_float($var)) {
            return 'd';
        }
        if (is_int($var)) {
            return 'i';
        }
        return 'b';
    }
}
