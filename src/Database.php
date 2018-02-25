<?php

namespace Mrcrmn\Mysql;

use Mrcrmn\Mysql\Collector;

/**
* The main database class which hosts the public API.
*
* @package Backbone
* @author Marco Reimann <marcoreimann@outlook.de>
*/
class Database extends Collector
{
    /**
     * INSERT|SELECT|UPDATE|DELETE
     *
     * @var string
     */
    public $action;

    /**
     * List of all available actions.
     *
     * @var array
     */
    public $actions = ['INSERT', 'SELECT', 'UPDATE', 'DELETE'];

    /**
     * Builds the insert statement. Accepts an assoc array [$key => $value].
     *
     * @param  array $array
     *
     * @return $this
     */
    public function insert($array)
    {
        $this->action = 'INSERT';

        return $this->addInsertArray($array);
    }

    /**
     * The start of the select statement.
     *
     * @param  array|string $columns
     *
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->action = 'SELECT';

        $columns = is_array($columns) ? $columns : func_get_args();
        $this->makeColumns($columns);

        return $this;
    }

    /**
     * The beginning of the update statement.
     *
     * @param array $array The columns and new values.
     * @param bool $force Needs to be true to update dangerous queries.
     *
     * @return $this
     */
    public function update($array, $force = false)
    {
        $this->action = 'UPDATE';
        $this->withForce = $force;

        return $this->addUpdateArray($array);
    }

    /**
     * The beginning of the update statement.
     *
     * @param bool $force Needs to be set to true, if you want to execute a query without any where clauses.
     *
     * @return $result
     */
    public function delete($force = false)
    {
        $this->action = 'DELETE';
        $this->withForce = $force;

        return $this->run();
    }

    /**
     * Setter for the table.
     * @param  string $table
     * @return $this
     */
    public function into($table)
    {
        $this->setTable($table);

        return $this;
    }

    /**
     * Setter for the table.
     * @param  string $table
     * @return $this
     */
    public function table($table)
    {
        $this->setTable($table);

        return $this;
    }

    /**
     * Setter for the table.
     * @param  string $table
     *
     * @return $this
     */
    public function from($table)
    {
        $this->setTable($table);

        return $this;
    }

    /**
     * Sets the distinct value to true.
     *
     * @return $this
     */
    public function distinct()
    {
        $this->isDistinct = true;

        return $this;
    }

    /**
     * Adds a where clause.
     *
     * @param  string       $column
     * @param  string       $operator
     * @param  string|int   $value
     * @param  string       $boolean
     *
     * @return $this
     */
    public function where($column, $operator, $value = null, $boolean = 'AND')
    {
        $this->addWhere($column, $operator, $value, $boolean);

        return $this;
    }

    /**
     * Adds a or where clause.
     *
     * @param  string       $column
     * @param  string       $operator
     * @param  string|int   $value
     *
     * @return $this
     */
    public function orWhere($column, $operator, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Adds where in clause.
     *
     * @param  string $column
     * @param  array $array
     * @param  string $boolean
     *
     * @return $this
     */
    public function whereIn($column, $array, $boolean = 'AND')
    {
        $this->addWhereIn($column, $array, $boolean);

        return $this;
    }

    /**
     * Adds or where in clause.
     *
     * @param  string $column
     * @param  array $array
     *
     * @return $this
     */
    public function orWhereIn($column, $array)
    {
        return $this->whereIn($column, $array, 'OR');
    }

    /**
     * Adds a order by subquery.
     *
     * @param  string $column
     * @param  string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->addOrderBy($column, $direction);

        return $this;
    }

    /**
     * Adds a order by subquery.
     *
     * @param  string $column
     *
     * @return $this
     */
    public function groupBy($column)
    {
        $this->addGroupBy($column);

        return $this;
    }

    /**
     * Adds a basic inner join to the query.
     *
     * @param  string $onTable
     * @param  string $firstColumn
     * @param  string $secondColumn
     * @param  string $type
     *
     * @return $this
     */
    public function join($onTable, $secondColumn = null, $firstColumn = 'id', $type = 'INNER')
    {
        $this->addJoin($onTable, $secondColumn, $firstColumn, $type);

        return $this;
    }

    /**
     * Adds a left join to the query.
     *
     * @param  string $onTable
     * @param  string $firstColumn
     * @param  string $secondColumn
     *
     * @return $this
     */
    public function leftJoin($onTable, $secondColumn = null, $firstColumn = 'id')
    {
        return $this->join($onTable, $secondColumn, $firstColumn, 'LEFT OUTER');
    }

    /**
     * Adds a right join to the query.
     *
     * @param  string $onTable
     * @param  string $firstColumn
     * @param  string $secondColumn
     *
     * @return $this
     */
    public function rightJoin($onTable, $secondColumn = null, $firstColumn = 'id')
    {
        return $this->join($onTable, $secondColumn, $firstColumn, 'RIGHT OUTER');
    }

    /**
     * Adds a limit to the query.
     *
     * @param  int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds a offset to the query.
     *
     * @param  int $offset
     *
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Gets an array after the select statement is executed.
     *
     * @return array
     */
    public function get()
    {
        $result = $this->run();

        // If we have no Connection, the result will be the compiled query string.
        if (is_string($result)) {
            return $result;
        }

        // If something went wrong, return the error info.
        if (! $result) {
            return $this->prepared->errorInfo();
        }

        // If everything works as expected, we return the result as an associative array.
        return $this->prepared->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sets the limit to 1, executes the query and returns the first result.
     *
     * @return array
     */
    public function first()
    {
        $this->limit = 1;

        return $this->get();
    }

    /**
     * Gets the count of the selected rows.
     *
     * @return int
     */
    public function count()
    {
        $this->run();
        return intval($this->prepared->rowCount());
    }

    /**
     * Prepared and executes the statement.
     *
     * @return bool|string
     */
    public function run()
    {
        // We return the full compiled query if there is no database connection for testing purposes.
        if (! $this->isConnected()) {
            return $this->getQuery();
        }

        $this->prepare();
        return $this->execPreparedStatement();
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        $this->closeConnection();
    }
}
