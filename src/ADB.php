<?php

namespace Sonar200\DB;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Class ADB
 *
 * @package Sonar200\DB
 */
abstract class ADB
{
    /** @var mixed */
    protected static $instances = null;

    /** @var string */
    protected $host;

    /** @var string */
    protected $db;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var PDO|null */
    protected $pdo = null;

    /** @var PDOStatement */
    protected $stmt;

    /** @var array|null */
    protected $error;

    /** @var int|null */
    protected $errorCode;

    /** @var string|null */
    protected $connectError;

    /** @var string|null */
    protected $connectCode;

    public function setConfig(string $host, string $db, string $username, string $password)
    {
        $this->host = $host;
        $this->db = $db;
        $this->username = $username;
        $this->password = $password;

        if (is_null($this->pdo)) {

            try {
                $this->pdo = @new PDO("mysql:host={$this->host};dbname=" . $this->db, $this->username, $this->password);
                if (!is_null($this->pdo)) {
                    $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                    $this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
                    $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
                }
            } catch (PDOException $exception) {
                $this->connectCode = $exception->getCode();
                $this->connectError = $exception->getMessage();
            }

        }
    }

    /**
     * Выполнение произвольного запроса к бд
     *
     * @param string       $query
     * @param array        $bind
     * @param boolean|null $fetch
     * @param string|null  $class
     *
     * @return array|false
     */
    public function query(string $query, array $bind = [], bool $fetch = true, string $class = null)
    {
        if (!$this->pdo) {
            return false;
        }

        if ($this->stmt = $this->pdo->prepare($query)) {
            $this->setFetchMode($class);

            if ($this->execute($bind) && $this->stmt->rowCount() > 0) {
                return $fetch ? $this->stmt->fetchAll() : true;
            }
        }

        $this->setError();

        return false;
    }

    protected function execute(array $bind = []): bool
    {
        return empty($bind) ? $this->stmt->execute() : $this->stmt->execute($bind);
    }

    protected function setFetchMode(?string $class)
    {
        if (is_null($class) || !class_exists($class)) {
            $this->stmt->setFetchMode(PDO::FETCH_ASSOC);
        } else {
            $this->stmt->setFetchMode(PDO::FETCH_CLASS, $class);
        }
    }

    /**
     * Получение текста ошибки
     *
     * @return array|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Установка кода и текста ошибки в переменные
     */
    protected function setError()
    {
        $this->errorCode = intval($this->pdo->errorCode());
        $this->error = $this->pdo->errorInfo();
    }

    /**
     * Установка кода и текста ошибок соединения в переменные
     *
     * @param int    $code
     * @param string $error
     */
    protected function setConnectionError(int $code = 0, string $error = '')
    {
        $this->connectCode = $code;
        $this->connectError = $error;
    }

    /**
     * Получение кода ошибки
     *
     * @return int|null
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }


    /**
     * @return int
     */
    public function lastInsertId(): int
    {
        if ($this->pdo) {
            return intval($this->pdo->lastInsertId());
        } else {
            return 0;
        }
    }



    public static function getInstance()
    {
        $class = get_called_class();

        if (empty(self::$instances)) {
            self::$instances = new $class;
        }
        return self::$instances;
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }
}