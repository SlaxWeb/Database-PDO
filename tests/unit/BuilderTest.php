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
    protected $_builder = null;

    protected function setUp()
    {
        $this->_builder = new Builder;
        // statically set delimiter and table name for all test
        $this->_builder->setDelim("\"")->table("foos");
    }

    protected function tearDown()
    {
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
        $this->assertEquals("SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1", $this->_builder->select(["foo"]));
        $this->assertEquals(
            "SELECT COUNT(\"foos\".\"foo\") AS fooCnt,\"foos\".\"bar\",MAX(\"foos\".\"baz\") "
            . "AS bazMax FROM \"foos\" WHERE 1=1",
            $this->_builder->select([
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
            $this->_builder->where("bar", "baz")->select(["foo"])
        );

        $this->_builder->resetPredicates();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? OR \"foos\".\"bar\" = ?)",
            $this->_builder->where("bar", "baz")->orWhere("bar", "qux")->select(["foo"])
        );

        $this->_builder->resetPredicates();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" <> ?)",
            $this->_builder->where("bar", "baz", Predicate::OPR_DIFF)->select(["foo"])
        );
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
            $this->_builder
                ->where("bar", "baz")
                ->groupWhere(function ($builder) {
                    $builder->where("bar", "10", Predicate::OPR_LESS)
                        ->orWhere("baz", "1", Predicate::OPR_GRTR);
                })->select(["foo"])
        );

        $this->_builder->resetPredicates();
        $this->assertEquals(
            "SELECT \"foos\".\"foo\" FROM \"foos\" WHERE 1=1 AND (\"foos\".\"bar\" = ? "
            . "  OR (\"foos\".\"bar\" < ? OR \"foos\".\"baz\" > ?))",
            $this->_builder
                ->where("bar", "baz")
                ->orGroupWhere(function ($builder) {
                    $builder->where("bar", "10", Predicate::OPR_LESS)
                        ->orWhere("baz", "1", Predicate::OPR_GRTR);
                })->select(["foo"])
        );
    }
}
