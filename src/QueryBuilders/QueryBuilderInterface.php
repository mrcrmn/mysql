<?php

namespace Mrcrmn\Mysql\QueryBuilders;

use Mrcrmn\Mysql\Collector;

/**
 * The query builder contract.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
interface QueryBuilderInterface
{
    /**
     * The constructor which needs the Collector Instance.
     *
     * @param Collector $collector Instance which holds all collected data.
     */
    public function __construct(Collector $collector);

    /**
     * Main method which puts all collected data together and return a query for preparation.
     *
     * @return string
     */
    public function build();
}
