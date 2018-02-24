<?php

namespace Mrcrmn\Mysql\QueryBuilders;

use Exception;
use Mrcrmn\Mysql\Collector;
use Mrcrmn\Mysql\QueryBuilders\BaseQueryBuilder;
use Mrcrmn\Mysql\QueryBuilders\QueryBuilderInterface;

/**
 * Constructs a delete query.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
class DeleteQueryBuilder extends BaseQueryBuilder implements QueryBuilderInterface
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
        if (count($this->collector->wheres) === 0 && ! $this->collector->withForce) {
            throw new Exception('You need to use force mode to execute dangerous queries. ->delete($force = true)');
        }
        $this->query = $this->addDelete();
        $this->query .= $this->addWheres();

        return $this->query;
    }
}
