<?php
/**
 * Query Builder Tests
 *
 * Test the query builder to ensure it builds the appropriate queries. This test
 * directly uses Where Group and Predicate classes, so a failure here can be due
 * to a failure in one of those two classes. This unit test tests the whole builder
 * process.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Test\Unit;

use SlaxWeb\DatabasePDO\Query\Builder;
use SlaxWeb\DatabasePDO\Query\Where\Predicate;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Builder instance
     *
     * @var \SlaxWeb\DatabasePDO\Query\Builder
     */
    protected $builder = null;

    protected function setUp()
    {
        $this->builder = new Builder;
        // statically set delimiter and table name for all test
        $this->builder->setDelim("\"")->table("foos");
    }

    protected function tearDown()
    {
    }

    /**
     * Test insert
     *
     * Ensure the builder creates a proper INSERT SQL DML statement, and properly
     * populates the parameters array.
     *
     * @return void
     */
    public function testInsert()
    {
        $this->assertEquals(
            "INSERT INTO \"foos\" (\"foo\",\"bar\") VALUES (?,?)",
            $this->builder->insert(["foo" => "baz", "bar" => "qux"])
        );
        $this->assertEquals(["baz", "qux"], $this->builder->getParams());
    }

    /**
     * Test select
     *
     * Ensure that a normal select statement is built with the column list provided.
     * This test also ensures that the SQL DML function is used properly.
     *
     * @reutrn void
     */
    public function testSelect()
    {
        $this->assertEquals("SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1", $this->builder->select(["foo"]));
        $this->assertEquals(
            "SELECT COUNT(\"foos\".\"foo\") AS fooCnt,\"foos\".\"bar\",MAX(\"foos\".\"baz\") "
            . "AS bazMax FROM \"foos\" WHERE 1=1",
            $this->builder->select([
                [
                    "func"  =>  "count",
                    "col"   =>  "foo",
                    "as"    =>  "fooCnt"
                ],
                "bar",
                [
                    "func"  =>  "max",
                    "col"   =>  "baz",
                    "as"    =>  "bazMax"
                ]
            ])
        );
    }

    /**
     * Test basic where
     *
     * Test basic where methods are building the where statements as necesarry.
     *
     * @return void
     */
    public function testBasicWhere()
    {
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ?)",
            $this->builder->where("bar", "baz")->select(["foo"])
        );
        $this->assertEquals(["baz"], $this->builder->getParams());

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? OR \"foos\".\"bar\" = ?)",
            $this->builder->where("bar", "baz")->orWhere("bar", "qux")->select(["foo"])
        );
        $this->assertEquals(["baz", "qux"], $this->builder->getParams());

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" <> ?)",
            $this->builder->where("bar", "baz", Predicate::OPR_DIFF)->select(["foo"])
        );
        $this->assertEquals(["baz"], $this->builder->getParams());
    }

    /**
     * Test where groupping
     *
     * Ensure 'groupWhere' and 'orGroupWhere' work properly and are being combined
     * by the builder as they should be.
     *
     * @return void
     */
    public function testWhereGroupping()
    {
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? "
            . "  AND (\"foos\".\"bar\" < ? OR \"foos\".\"baz\" > ?))",
            $this->builder
                ->where("bar", "baz")
                ->groupWhere(function ($builder) {
                    $builder->where("bar", 10, Predicate::OPR_LESS)
                        ->orWhere("baz", 1, Predicate::OPR_GRTR);
                })->select(["foo"])
        );
        $this->assertEquals(["baz", 10, 1], $this->builder->getParams());

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? "
            . "  OR (\"foos\".\"bar\" < ? OR \"foos\".\"baz\" > ?))",
            $this->builder
                ->where("bar", "baz")
                ->orGroupWhere(function ($builder) {
                    $builder->where("bar", "10", Predicate::OPR_LESS)
                        ->orWhere("baz", "1", Predicate::OPR_GRTR);
                })->select(["foo"])
        );
        $this->assertEquals(["baz", 10, 1], $this->builder->getParams());
    }

    /**
     * Test nested where statements
     *
     * Ensure that statements are properly nested by the query builder.
     *
     * @return void
     */
    public function testNestedWhere()
    {
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? "
            . "AND \"foos\".\"bar\" IN (SELECT \"bars\".\"bar\" FROM \"bars\" WHERE 1=1))",
            $this->builder
                ->where("bar", "baz")
                ->nestedWhere("bar", function ($builder) {
                    return $builder->table("bars")
                        ->select(["bar"]);
                })->select(["foo"])
        );
        $this->assertEquals(["baz"], $this->builder->getParams());

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? "
            . "OR \"foos\".\"bar\" IN (SELECT \"bars\".\"bar\" FROM \"bars\" WHERE 1=1))",
            $this->builder
                ->where("bar", "baz")
                ->orNestedWhere("bar", function ($builder) {
                    return $builder->table("bars")
                        ->select(["bar"]);
                })->select(["foo"])
        );
        $this->assertEquals(["baz"], $this->builder->getParams());
    }

    /**
     * Test joins
     *
     * Ensure that joins can be added to the SELECT statement and they are properly
     * handled.
     *
     * @return void
     */
    public function testJoin()
    {
        $this->assertEquals(
            "SELECT \"foos\".\"foo\",\"bars\".\"bar\" FROM \"foos\" INNER JOIN \"bars\" ON "
            . "(1=1 AND \"foos\".\"id\" = \"bars\".\"id\") WHERE 1=1",
            $this->builder
                ->join("bars")
                ->joinCond("id", "id")
                ->joinCols(["bar"])
                ->select(["foo"])
        );

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\",\"bars\".\"bar\" FROM \"foos\" INNER JOIN \"bars\" ON "
            . "(1=1 AND \"foos\".\"id\" = \"bars\".\"id\" OR \"foos\".\"id\" < \"bars\".\"id\") WHERE 1=1",
            $this->builder
                ->join("bars")
                ->joinCond("id", "id")
                ->orJoinCond("id", "id", Predicate::OPR_LESS)
                ->joinCols(["bar"])
                ->select(["foo"])
        );

        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\",\"bars\".\"bar\",\"bazs\".\"baz\" FROM \"foos\" INNER JOIN \"bars\" ON "
            . "(1=1 AND \"foos\".\"id\" = \"bars\".\"id\") LEFT OUTER JOIN \"bazs\" ON (1=1 "
            . "AND \"foos\".\"id\" = \"bazs\".\"id\") WHERE 1=1",
            $this->builder
                ->join("bars")
                ->joinCond("id", "id")
                ->joinCols(["bar"])

                ->join("bazs", Builder::JOIN_LEFT)
                ->joinCond("id", "id")
                ->joinCols(["baz"])

                ->select(["foo"])
        );
    }

    /**
     * Test group by
     *
     * Ensure that the builder properly adds the column list to the group by.
     *
     * @return void
     */
    public function testGroupBy()
    {
        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\",\"foos\".\"bar\" FROM \"foos\" WHERE 1=1 "
            . "GROUP BY \"foos\".\"foo\",\"foos\".\"bar\"",
            $this->builder->groupBy("foo")->groupBy("bar")->select(["foo", "bar"])
        );
    }

    /**
     * Test order by
     *
     * Ensure the builder properly adds the order by to the SELECT statement.
     *
     * @return void
     */
    public function testOrderBy()
    {
        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\",\"foos\".\"bar\" FROM \"foos\" WHERE 1=1 "
            . "ORDER BY \"foos\".\"foo\" ASC,MAX(\"foos\".\"bar\") DESC",
            $this->builder->orderBy("foo")->orderBy("bar", Builder::ORDER_DESC, "MAX")->select(["foo", "bar"])
        );
    }

    /**
     * Test limit/offset
     *
     * Ensure that the LIMIT and OFFSET are properly set and added to the query.
     *
     * @return void
     */
    public function testLimitOffset()
    {
        $this->builder->reset();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 LIMIT 10",
            $this->builder->limit(10)->select(["foo"])
        );
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 LIMIT 10 OFFSET 10",
            $this->builder->limit(10, 10)->select(["foo"])
        );
    }

    /**
     * Test update
     *
     * Ensure that the builder constructs the statement properly, and that it appends
     * it with set where predicates.
     *
     * @reutrn void
     */
    public function testUpdate()
    {
        $this->builder->reset();
        $this->assertEquals(
            "UPDATE \"foos\" SET \"foos\".\"foo\" = 'bar' WHERE 1=1",
            $this->builder->update(["foo" => "bar"])
        );
        $this->assertEquals(
            "UPDATE \"foos\" SET \"foos\".\"foo\" = 'bar' WHERE 1=1 AND (\"foos\".\"bar\" = ?)",
            $this->builder->where("bar", "baz")->update(["foo" => "bar"])
        );
        $this->assertEquals(["baz"], $this->builder->getParams());
    }

    /**
     * Test delete
     *
     * Ensure that the builder constructs the delete statement properly, with the
     * previously added where predicates.
     *
     * @return void
     */
    public function testDelete()
    {
        $this->builder->reset();
        $this->assertEquals(
            "DELETE FROM \"foos\" WHERE 1=1",
            $this->builder->delete()
        );
        $this->assertEquals(
            "DELETE FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ?)",
            $this->builder->where("bar", "baz")->delete()
        );
        $this->assertEquals(["baz"], $this->builder->getParams());
    }
}
