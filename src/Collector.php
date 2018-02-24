<?php

namespace Mrcrmn\Mysql;

use Exception;
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
        'table' => null,
        'paramCache' => [],
        'selectColumns' => null,
        'isDistinct' => false,
        'joins' => [],
        'wheres' => [],
        'groupBys' => [],
        'orderBys' => [],
        'limit' => null,
        'offset' => null
    ];

    /**
     * Connects to the Database.
     *
     * @param  string  $host
     * @param  string  $username
     * @param  string  $password
     * @param  int $port
     * @param  string  $database
     *
     * @return bool
     */
    public function connect($host = "127.0.0.1", $username = "root", $password = "", $port = 3306, $database = "")
    {
        $this->preparer = new Preparer(...$args);

        return $this;
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
        $placeholder = ":" . $column;
        $this->paramCache[$placeholder] = $value;

        // "WHERE|AND|OR column =|>|<|... some_value"
        $this->wheres[] = trim(sprintf("%s %s %s %s", $boolean, $column, $operator, $placeholder));
    }

    /**
     * Adds a where in clause to the query.
     *
     * @param string $column  The column name.
     * @param array $array   An array of values to search for.
     * @param string $boolean Whether or not this is a AND or OR subquery.
     */
    protected function addWhereIn($column, $array, $boolean)
    {
        if (count($this->wheres) === 0) {
            $boolean = 'WHERE';
        }

        $currentPlaceholders = [];

        for ($i=0; $i < count($array); $i++) {
            $placeholder = ":" . $column . strval($i);
            $currentPlaceholders[] = $placeholder;
            $this->paramCache[$placeholder] = $array[$i];
        }

        $this->wheres[] = trim(sprintf("$boolean %s IN (%s)", $column, implode(",", $currentPlaceholders)));
    }

    protected function addJoin($onTable, $secondColumn, $firstColumn, $type)
    {
        // If the 2nd parameter is empty whe assume that the column name is referencing the table with the _id convention.
        if (! isset($secondColumn)) {
            $secondColumn = $this->table . "_id";
        }

        $this->joins[] = trim(sprintf("%s JOIN %s ON %s.%s = %s.%s", $type, $onTable, $this->table, $firstColumn, $onTable, $secondColumn));
    }

    protected function addGroupBy($column)
    {
        $this->groupBys[] = trim(sprintf("GROUP BY %s", $column));
    }

    protected function addOrderBy($column, $direction)
    {
        $this->orderBys[] = trim(sprintf("%s %s", $column, strtoupper($direction)));
    }

    protected function prepare()
    {
        $this->prepared = $this->preparer->prepare($this->buildQuery());
    }

    protected function execPreparedStatement()
    {
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
    public function getQuery(): string
    {
        $base = $this->buildQuery();
        foreach ($this->paramCache as $column => $value)
        {
            $base = str_replace($column, $value, $base);
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
