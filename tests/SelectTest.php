<?php

namespace Mrcrmn\Mysql\Tests;

use Mrcrmn\Mysql\Database;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{

    /**
     * Tests select queries.
     *
     * @param string $result
     * @param string $expected
     *
     * @dataProvider selectStatements
     */
    public function testBasicSelectFunction($result, $expected)
    {
        $this->assertEquals($result, $expected);
    }

    public function selectStatements()
    {
        $db = new Database();
        return [
            [
                $db->select()->from('foo')->get(),
                "SELECT * FROM foo"
            ],
            [
                $db->select('foo', 'bar')->from('bar')->get(),
                "SELECT foo,bar FROM bar"
            ],
            [
                $db->select(['foo', 'bar', 'baz as b'])->from('bar')->get(),
                "SELECT foo,bar,baz as b FROM bar"
            ],
            [
                $db->select('foo,bar')->from('bar')->where('foo', 'LIKE', 'baz')->get(),
                "SELECT foo,bar FROM bar WHERE foo LIKE 'baz'"
            ],
            [
                $db->select()->from('foo')->whereIn('foo', ['bar', 'baz'])->orWhere('foo', 'foo')->limit(1)->get(),
                "SELECT * FROM foo WHERE foo IN ('bar','baz') OR foo = 'foo' LIMIT 1"
            ],
            [
                $db->select()->from('foo')->where('foo', 'bar')->where('bar', 'foo')->orWhere('foo', 'foo')->get(),
                "SELECT * FROM foo WHERE foo = 'bar' AND bar = 'foo' OR foo = 'foo'"
            ],
            [
                $db->select()->from('foo')->join('bar', 'foo_id')->get(),
                "SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id"
            ],
            [
                $db->select()->from('foo')->join('bar', 'foo_id')->rightJoin('baz', 'foo_id', 'baz_id')->get(),
                "SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id RIGHT OUTER JOIN baz ON foo.baz_id = baz.foo_id"
            ],
            [
                $db->select()->from('foo')->join('bar', 'foo_id')->leftJoin('baz', 'foo_id', 'baz_id')->limit(8)->offset(8)->get(),
                "SELECT * FROM foo INNER JOIN bar ON foo.id = bar.foo_id LEFT OUTER JOIN baz ON foo.baz_id = baz.foo_id LIMIT 8 OFFSET 8"
            ],
            [
                $db->select()->from('foo')->orderBy('foo')->get(),
                "SELECT * FROM foo ORDER BY foo ASC"
            ],
            [
                $db->select()->from('foo')->orderBy('foo', 'ASC')->get(),
                "SELECT * FROM foo ORDER BY foo ASC"
            ],
            [
                $db->select()->from('foo')->orderBy('foo', 'DESC')->get(),
                "SELECT * FROM foo ORDER BY foo DESC"
            ],
            [
                $db->select()->from('foo')->orderBy('foo', 'DESC')->orderBy('bar')->get(),
                "SELECT * FROM foo ORDER BY foo DESC, bar ASC"
            ],
            [
                $db->select()->from('foo')->groupBy('bar')->get(),
                "SELECT * FROM foo GROUP BY bar"
            ],
            [
                $db->select()->from('foo')->orderBy('foo', 'DESC')->orderBy('bar')->groupBy('baz')->get(),
                "SELECT * FROM foo GROUP BY baz ORDER BY foo DESC, bar ASC"
            ],
            [
                $db->select()->from('foo')->orderBy('created_at', 'desc')->first(),
                "SELECT * FROM foo ORDER BY created_at DESC LIMIT 1"
            ]
        ];
    }
}
