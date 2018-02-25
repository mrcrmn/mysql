<?php

namespace Mrcrmn\Mysql\Tests;

use Mrcrmn\Mysql\Database;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
{

    /**
     * Tests select queries.
     *
     * @param string $result
     * @param string $expected
     *
     * @dataProvider insertStatements
     */
    public function testBasicInsertFunction($result, $expected)
    {
        $this->assertEquals($result, $expected);
    }

    public function insertStatements()
    {
        $db = new Database();
        return [
            [
                $db->table('foo')->insert(['bar' => 'baz']),
                "INSERT INTO foo (bar) VALUES ('baz')"
            ],
            [
                $db->insert([
                    'table' => 'foo',
                    'bar' => 1,
                    'baz' => 'foo'
                ]),
                "INSERT INTO foo (bar,baz) VALUES (1,'foo')"
            ]
        ];
    }
}
