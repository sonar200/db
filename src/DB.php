<?php


namespace Sonar200\DB;

/**
 * Class DB
 *
 * @package Core\Base
 *
 * @todo    Добавить методы:
 *       insert - добавление записи
 *       update - обновление записи
 *       delete - удаление записи
 *
 * @method static DB getInstance()
 */
class DB extends ADB
{

    /**
     * Выполнение запроса для получения записей
     *
     * @param string      $table  Таблица из которой требуется получить записи
     * @param array       $select Массив полей для выборки из бд
     * @param array       $where  Параметры выборки
     * @param string|null $class  Класс для получения маппинга
     * @param bool        $single Параметры выборки
     *
     * @return array|false
     * @todo проработать возможность сборки сложных условий с join
     *
     * @todo $where Доработать сборку условий выборки по параметрам
     *              Предусмотреть сборку условий для блоков AND, OR, BETWEEN, IN и т.д.
     *
     */
    public function select(string $table, array $select = [], array $where = [], string $class = null, bool $single = false)
    {
        $selectString = $this->getSelectString($table, $select);
        $wherePrepare = $this->getWhereString($table, $where);

        $query = "SELECT {$selectString} FROM {$table} {$wherePrepare['string']}";
        if ($single) {
            $query .= " LIMIT 1;";
        }

        $result = $this->query($query, $wherePrepare['params'], true, $class);

        return $single && !empty($result) ? $result[0] : $result;
    }

    public function insert(string $table, array $data)
    {
        $insertData = $this->generateInsertData($data);
        $query = "INSERT INTO {$table} ({$insertData['keys']}) VALUES ({$insertData['values']})";

        return $this->query($query, $insertData['params'], false);
    }

    public function update(string $table, array $update, array $where)
    {
        $setSql = [];
        $whereSql = [];
        foreach ($update as $key => $value) {
            $setSql[] = "`$key` = :$key";
        }
        foreach ($where as $key => $value) {
            $whereSql[] = "`$key` = :$key";
        }
        $setString = implode(', ', $setSql);
        $whereString = implode(' AND ', $whereSql);
        $sql = sprintf("UPDATE $table SET %s WHERE %s", $setString, $whereString);
        $this->query($sql);
        //        try {
        //            foreach ($update as $key => $value) {
        //                $this->bind(":$key", $value);
        //            }
        //            foreach ($where as $key => $value) {
        //                $this->bind(":$key", $value);
        //            }
        //
        //            return $this->execute();
        //
        //        } catch (Exception $e) {
        //            $this->error = $e->getMessage();
        //            $this->errorCode = $e->getCode();
        //            return false;
        //        }
    }

    public function delete(string $table, array $where)
    {

    }

    /**
     * Получение строки получаемых полей
     *
     * @param string $table
     * @param array  $select
     *
     * @return string
     */
    protected function getSelectString(string $table, array $select)
    {
        $selectString = '*';
        if (!empty($select)) {
            $selectArray = [];
            foreach ($select as $item) {
                array_push($selectArray, $table . ".`{$item}`");
            }

            $selectString = implode(', ', $selectArray);
        }

        return $selectString;
    }

    /**
     * @param string $table
     * @param array  $where
     *
     * @return array
     */
    protected function getWhereString(string $table, array $where): array
    {
        $whereOut = [
            'string' => '',
            'params' => []
        ];

        if (!empty($where)) {
            $whereOut['string'] = 'WHERE ';

            $out = $this->generateWhereBlock($table, $where);

            $whereOut['string'] .= $out['string'];
            $whereOut['params'] = array_merge($whereOut['params'], $out['params']);
        }
        return $whereOut;
    }

    /**
     * @param string $table
     * @param array  $where
     * @param int    $i
     *
     * @return array
     */
    protected function generateWhereBlock(string $table, array $where, int $i = 0): array
    {
        $out = [
            'string' => '',
            'params' => []
        ];

        $whereString = '';
        foreach ($where as $param => $value) {
            $whereStringItem = ($i == 0 ? '' : ' AND ');
            $i++;

            if (is_string($param)) {

                if (is_string($value) || is_numeric($value)) {
                    switch ($value) {
                        case 'not null':
                            $whereStringItem .= "{$table}.`{$param}` IS NOT NULL";
                        break;
                        default:
                            $whereStringItem .= "{$table}.`{$param}`=:{$param}";
                            $out["params"][":{$param}"] = $value;
                        break;
                    }
                } elseif (is_bool($value)) {
                    $whereStringItem .= "{$table}.`{$param}`=:{$param}";
                    $out["params"][":{$param}"] = intval($value);
                } elseif (is_null($value)) {
                    $whereStringItem .= "{$table}.`{$param}` IS NULL";
                }
            }
            $whereString .= $whereStringItem;
        }
        $out['string'] = "{$whereString}";

        return $out;
    }


    protected function generateInsertData(array $data)
    {
        $out = ['keys'   => '',
                'values' => '',
                'params' => []
        ];
        $setSqlKeys = [];
        $setSqlValues = [];

        foreach ($data as $key => $value) {
            $setSqlKeys[] = "`$key`";
            $setSqlValues[$key] = ":$key";
            $out["params"][":{$key}"] = $value;
        }

        $out['keys'] = implode(', ', $setSqlKeys);
        $out['values'] = implode(', ', $setSqlValues);

        return $out;
    }
}