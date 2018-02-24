<?php

namespace Mrcrmn\Mysql\QueryBuilders;

use Mrcrmn\Mysql\Database;

/**
 * The query builder contract.
 *
 * @package mrcrmn/mysql
 * @author Marco Reimann <marcoreimann@outlook.de>
 */
interface QueryBuilderInterface
{
    public function __construct(Database $db);

    public function build();
}
