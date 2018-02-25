<?php

namespace Mrcrmn\Mysql\Tests;

use Mrcrmn\Mysql\Database;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{

    /**
     * Tests select queries.
     *
     * @param string $result
     * @param string $expected
     *
     * @dataProvider updateStatements
     */
    public function testBasicUpdateFunction($result, $expected)
    {
        $this->assertEquals($result, $expected);
    }

    public function updateStatements()
    {
        $db = new Database();
        return [
            [
                $db->table('foo')->where('baz', 'bar')->update([
                    'bar' => 1,
                    'baz' => 'bar'
                ]),
                "UPDATE foo SET bar = 1,baz = 'bar' WHERE baz = 'bar'"
            ],
            [
                $db->table('baz')->where('foo', 'bar')->where('baz', '!=', 'foo')->update([
                    'foo' => 'bar'
                ]),
                "UPDATE baz SET foo = 'bar' WHERE foo = 'bar' AND baz != 'foo'"
            ]
        ];
    }
}
