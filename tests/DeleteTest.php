<?php

namespace Mrcrmn\Mysql\Tests;

use Mrcrmn\Mysql\Database;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{

    /**
     * Tests select queries.
     *
     * @param string $result
     * @param string $expected
     *
     * @dataProvider deleteStatements
     */
    public function testBasicDeleteFunction($result, $expected)
    {
        $this->assertEquals($result, $expected);
    }

    public function deleteStatements()
    {
        $db = new Database();
        return [
            [
                $db->table('foo')->where('baz', 'bar')->delete(),
                "DELETE FROM foo WHERE baz = 'bar'"
            ],
            [
                $db->table('baz')->where('foo', 'bar')->where('baz', '!=', 'foo')->delete(),
                "DELETE FROM baz WHERE foo = 'bar' AND baz != 'foo'"
            ]
        ];
    }
}
