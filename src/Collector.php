<?php

namespace Mrcrmn\Mysql;

use Exception;
use InvalidArgumentException;
use Mrcrmn\Mysql\QueryBuilders\InsertQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\SelectQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\UpdateQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\DeleteQueryBuilder;

/**
 * The Collector Class which stores the data and methods needed to build a query.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
class Collector
{
    /**
     * The table where the current query is executed.
     *
     * @var string
     */
    public $table;

    /**
     * Executes dangerous queries if set to true.
     *
     * @var bool
     */
    public $withForce = false;

    /**
     * The QueryBuilder Instance.
     *
     * @var \Mrcrmn\Mysql\QueryBuilders\
     */
    protected $builder;

    /**
     * The query result.
     *
     * @var object
     */
    public $result;

    /**
     * The prepared statement, ready to be executed.
     *
     * @var \Mrcrmn\Mysql\Connectors\DatabaseConnection
     */
    public $prepared;

    /**
     * The preparer Instance.
     *
     * @var \Mrcrmn\Mysql\Preparer
     */
    protected $preparer;

    /**
     * The list of valid operators.
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'LIKE', 'LIKE BINARY', 'NOT LIKE', 'ILIKE',
        '&', '|', '^', '<<', '>>',
        'RLIKE', 'REGEXP', 'NOT REGEXP',
        '~', '~*', '!~', '!~*', 'similar to',
        'NOT SIMILAR TO', 'NOT ILIKE', '~~*', '!~~*',
    ];

    /**
     * This array stores the keys and values which are later bound to the prepared statement.
     *
     * @var array
     */
    public $paramCache = [];

    /**
     * The columns to select.
     *
     * @var string
     */
    public $selectColumns;

    /**
     * The update string.
     *
     * @var string
     */
    public $updates = [];

    /**
     * The insert string.
     *
     * @var string
     */
    public $inserts = [];

    /**
     * Bool if the selection is disctinct.
     *
     * @var bool
     */
    public $isDistinct = false;

    /**
     * The joins.
     *
     * @var array
     */
    public $joins = [];

    /**
     * The array which stores the all where clauses.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The array which stores the GROUP BY clauses.
     *
     * @var array
     */
    public $groupBys = [];

    /**
     * The array which stores the all order by clauses.
     *
     * @var array
     */
    public $orderBys = [];

    /**
     * The limit for the select query.
     *
     * @var int
     */
    public $limit;

    /**
     * The offset for the select query.
     *
     * @var int
     */
    public $offset;

    /**
     * List of parameters to reset after an execution.
     *
     * @var array
     */
    protected $shouldFlush = [
        'action' => null,
        'withForce' => false,
        'table' => null,
        'paramCache' => [],
        'selectColumns' => null,
        'updates' => [],
        'inserts' => [],
        'isDistinct' => false,
        'joins' => [],
        'wheres' => [],
        'groupBys' => [],
        'orderBys' => [],
        'limit' => null,
        'offset' => null
    ];

    /**
     * The string to add when a key already exists.
     *
     * @var string
     */
    protected const PARAM_ADDON = 'aaaaa';

    /**
     * Connects to the Database.
     *
     * @param  string  $host
     * @param  string  $username
     * @param  string  $password
     * @param  int     $port
     * @param  string  $database
     *
     * @return bool
     */
    public function connect($host = "127.0.0.1", $username = "root", $password = "", $port = 3306, $database = "")
    {
        $this->preparer = new Preparer(...func_get_args());

        return $this;
    }

    /**
     * Returns true if there is an active connection.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->preparer instanceof Preparer;
    }

    /**
     * Adds a $key => $value pair to the parameter cache.
     *
     * @param string $key       The key to cache.
     * @param int|string $value The value to cache.
     */
    private function addToParamCache($key, $value)
    {
        $key = ':'.$key;
        while (array_key_exists($key, $this->paramCache)) {
            $key = $key.self::PARAM_ADDON;
        }
        $this->paramCache[$key] = $value;

        return $key;
    }

    /**
     * Removes the : and -- from the placeholder key.
     *
     * @param  string $key The parameter key.
     *
     * @return string
     */
    public function getOriginalParamKey($key)
    {
        $key = ltrim($key, ':');
        $key = rtrim($key, self::PARAM_ADDON);

        return $key;
    }

    /**
     * Sets the table.
     *
     * @param string $table
     */
    protected function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Constructs the full insert statement.
     *
     * @param array $array The array which holds all column names and the values to insert.
     */
    protected function addInsertArray($array)
    {
        if (! is_array($array)) {
            throw new InvalidArgumentException('You need to insert an assoc array.');
        }

        if (isset($array['table'])) {
            $this->table = $array['table'];
            unset($array['table']);
        }

        if (empty($this->table)) {
            throw new Exception('No table is set.');
        }

        $placeholders = [];
        $columns = array_keys($array);

        foreach ($array as $key => $value)
        {
            $placeholders[] = $this->addToParamCache($key, $value);
        }

        $columns = implode(",", $columns);
        $values = implode(",", $placeholders);

        $this->inserts = [
            'columns' => $columns,
            'values' => $values
        ];

        if (! $this->isConnected()) {
            return $this->getQuery();
        }

        $this->prepare();

        return $this->run();
    }

    /**
     * Constructs the base update statement.
     *
     * @param array $array The array which holds all column names and the values to update.
     */
    protected function addUpdateArray($array)
    {
        if (! is_array($array)) {
            throw new InvalidArgumentException('You need to insert an assoc array.');
        }

        if (empty($this->table)) {
            throw new Exception('No table is set.');
        }

        $placeholders = [];

        foreach ($array as $key => $value)
        {
            $placeholders[] = $this->addToParamCache($key, $value);
        }

        foreach ($placeholders as $column)
        {
            $this->updates[] = sprintf("%s = %s", $this->getOriginalParamKey($column), $column);
        }

        if (! $this->isConnected()) {
            return $this->getQuery();
        }

        $this->prepare();

        return $this->run();
    }

    /**
     * Builds the column string for the query.
     *
     * @param  string|array $columns
     *
     * @return void
     */
    protected function makeColumns($columns)
    {
        $this->selectColumns = trim(implode(",", $columns));
    }

    /**
     * Makes a where subquery.
     *
     * @param  string $column
     * @param  string $operator
     * @param  string|int $value
     * @param  string $boolean
     *
     * @return void
     */
    protected function addWhere($column, $operator, $value, $boolean)
    {
        // We check if a third parameter is passed to the method. If not, we'll set the default operator to '='.
        if (! isset($value)) {
            $value = $operator;
            $operator = '=';
        } else {
            $operator = strtoupper($operator);
        }

        // Next we check if the operator is valid.
        if (! in_array($operator, $this->operators)) {
            throw new Exception("{$operator} is not a valid Operator.");
        }

        // If this is the first WHERE clause, we don't need to prepend the statement with an 'AND' or 'OR'.
        if (count($this->wheres) === 0) {
            $boolean = 'WHERE';
        }

        // Finally we add the full query to the wheres array.
        $placeholder = $this->addToParamCache($column, $value);

        // "WHERE|AND|OR column =|>|<|... some_value"
        $this->wheres[] = trim(sprintf("%s %s %s %s", $boolean, $column, $operator, $placeholder));
    }

    /**
     * Adds a where in clause to the query.
     *
     * @param string $column  The column name.
     * @param array $array    An array of values to search for.
     * @param string $boolean Whether or not this is a AND or OR subquery.
     */
    protected function addWhereIn($column, $array, $boolean)
    {
        if (count($this->wheres) === 0) {
            $boolean = 'WHERE';
        }

        $placeholders = [];

        foreach ($array as $value) {
            $placeholders[] = $this->addToParamCache($column, $value);
        }

        $this->wheres[] = trim(sprintf("$boolean %s IN (%s)", $column, implode(",", $placeholders)));
    }

    /**
     * Constructs a join subquery.
     *
     * @param string $onTable      The table to join.
     * @param string $secondColumn The column of the second table.
     * @param string $firstColumn  The column of the current primary table.
     * @param string $type         Whether its an inner, right or left join.
     */
    protected function addJoin($onTable, $secondColumn, $firstColumn, $type)
    {
        // If the 2nd parameter is empty whe assume that the column name is referencing the table with the _id convention.
        if (! isset($secondColumn)) {
            $secondColumn = $this->table . "_id";
        }

        $this->joins[] = trim(sprintf("%s JOIN %s ON %s.%s = %s.%s", $type, $onTable, $this->table, $firstColumn, $onTable, $secondColumn));
    }

    /**
     * Constructs a group by subquery
     *
     * @param string $column
     */
    protected function addGroupBy($column)
    {
        $this->groupBys[] = trim(sprintf("GROUP BY %s", $column));
    }

    /**
     * Constructs an order by subquery.
     *
     * @param string $column    The column to toder by.
     * @param string $direction ASC or DESC.
     */
    protected function addOrderBy($column, $direction)
    {
        $this->orderBys[] = trim(sprintf("%s %s", $column, strtoupper($direction)));
    }

    /**
     * Calls the prepare method on the preparer and passes the built query.
     *
     * @return void
     */
    protected function prepare()
    {
        $this->prepared = $this->preparer->prepare($this->buildQuery());
    }

    /**
     * Executes the prepared statement and returns its result.
     *
     * @return $result
     */
    protected function execPreparedStatement()
    {
        if (! $this->isConnected()) {
            return $this->getQuery();
        }

        if (empty($this->paramCache)) {
            $result = $this->prepared->execute();
        }


        $result = $this->prepared->execute($this->paramCache);

        $this->flushAll();

        return $result;
    }

    /**
     * Evaluates the action, instanciates the Query builder and calls its build() method.
     *
     * @return string
     */
    protected function buildQuery()
    {
        switch ($this->action) {
            case 'INSERT':
                $this->builder = new InsertQueryBuilder($this);
                break;
            case 'SELECT':
                $this->builder = new SelectQueryBuilder($this);
                break;
            case 'UPDATE':
                $this->builder = new UpdateQueryBuilder($this);
                break;
            case 'DELETE':
                $this->builder = new DeleteQueryBuilder($this);
                break;
            default:
                throw new Exception('Invalid Action');
                break;
        }

        return $this->builder->build();
    }

    /**
     * Returns the plain Text query.
     *
     * @return string
     */
    public function getQuery()
    {
        $base = $this->buildQuery();
        foreach (array_reverse($this->paramCache) as $placeholder => $value)
        {
            if (is_string($value)) {
                $value = "'".$value."'";
            }

            $base = str_replace($placeholder, $value, $base);
        }
        $this->flushAll();

        return $base;
    }

    /**
     * Flushes all parameters for a new clean query.
     *
     * @return $this
     */
    protected function flushAll()
    {
        foreach($this->shouldFlush as $param => $default)
        {
            $this->{$param} = $default;
        }

        return $this;
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    protected function closeConnection()
    {
        $this->preparer->disconnect();
    }
}
