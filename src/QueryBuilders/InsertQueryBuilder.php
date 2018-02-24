<?php

namespace Mrcrmn\Mysql\QueryBuilders;

use Mrcrmn\Mysql\Collector;
use Mrcrmn\Mysql\QueryBuilders\BaseQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\QueryBuilderInterface;

/**
 * Constructs an insert query.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
class InsertQueryBuilder extends BaseQueryBuilder implements QueryBuilderInterface
{
    /**
     * The connection instance.
     *
     * @var \Mrcrmn\Mysql\Collector
     */
    protected $collector;

    /**
     * The query.
     *
     * @var string
     */
    public $query;

    /**
     * The constructor needs all parameters which have been collected by the public API.
     *
     * @param Collector $collector
     */
    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }

    public function build()
    {
        $this->query = $this->addInsert();

        return $this->query;
    }
}
