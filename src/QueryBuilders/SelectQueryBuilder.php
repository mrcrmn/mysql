<?php

namespace Mrcrmn\Mysql\QueryBuilders;

use \Exception;
use Mrcrmn\Mysql\QueryBuilders\BaseQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\QueryBuilderInterface;
use Mrcrmn\Mysql\Database;

/**
 * Constructs a select query.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
class SelectQueryBuilder extends BaseQueryBuilder implements QueryBuilderInterface
{

    /**
     * The connection instance.
     *
     * @var \Mrcrmn\Mysql\Database
     */
    protected $db;

    /**
     * The query.
     *
     * @var string
     */
    public $query;

    /**
     * The constructor needs all parameters which have been collected by the public API.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Adds the basic SELCET ... FROM ... to the query.
     *
     * @return  string
     */
    public function addBaseSelect()
    {
        if (empty($this->db->selectColumns)) {
            throw new Exception('Nothing to Select.');
        }

        if (empty($this->db->table)) {
            throw new Exception('No table selected.');
        }

        // 1. %s-> DISTINCT; 2. %s->column_1, column_2; 3. %s-> table_name
        return sprintf("SELECT%s %s FROM %s", $this->addDistinct(), $this->db->selectColumns, $this->db->table);
    }

    /**
     * When this function is called, the full query is being compiled and returned.
     *
     * @return string
     */
    public function build()
    {
        $this->query = $this->addBaseSelect();
        $this->query .= $this->addJoins();
        $this->query .= $this->addWheres();
        $this->query .= $this->addGroupBys();
        $this->query .= $this->addOrderBys();
        $this->query .= $this->addLimit();
        $this->query .= $this->addOffset();

        return $this->query;
    }
}
