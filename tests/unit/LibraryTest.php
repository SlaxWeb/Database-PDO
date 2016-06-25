<?php
/**
 * PDO Database Library Test
 *
 * The test ensures that the library is functioning properly, by testing its API.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Test\Unit;

use SlaxWeb\DatabasePDO\Result;
use SlaxWeb\DatabasePDO\Library;
use SlaxWeb\Database\Exception\QueryException;
use SlaxWeb\DatabasePDO\Query\Builder as QueryBuilder;

class LibraryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Table
     *
     * @var string
     */
    protected $_testTable = "testTable";

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    /**
     * Test Execute Query
     *
     * Ensure that the execute method works properly, that it calls the appropriate
     * methods to the PDO and PDOStatemenet objects.
     *
     * @return void
     */
    public function testExecute()
    {
        $data = ["foo" => "bar", "baz" => "qux"];
        $testQuery = "query";

        $pdo = $this->createMock("PDO");
        $statement = $this->createMock("PDOStatement");

        $statement->expects($this->once())
            ->method("execute")
            ->with(array_values($data))
            ->willReturn(true);

        $pdo->expects($this->once())
            ->method("prepare")
            ->with($testQuery)
            ->willReturn($statement);

        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $lib->__construct($pdo, $this->createMock(QueryBuilder::class));

        $this->assertTrue($lib->execute($testQuery, $data));
    }

    /**
     * Test Insert
     *
     * Ensure the insert method works as intended, that it calls the execute method.
     *
     * @return void
     */
    public function testInsert()
    {
        $data = ["foo" => "bar", "baz" => "qux"];
        $testQuery = "INSERT INTO \"{$this->_testTable}\" (\""
            . implode("\",\"", array_keys($data))
            . "\") VALUES ("
            . rtrim(str_repeat("?,", count($data)), ",")
            . ");";

        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute"])
            ->getMock();

        $lib->expects($this->once())
            ->method("execute")
            ->with($testQuery, $data)
            ->willReturn(true);

        $lib->__construct($this->createMock("PDO"), $this->createMock(QueryBuilder::class));
        $this->assertTrue($lib->insert($this->_testTable, $data));
    }

    /**
     * Test Premature Fetch
     *
     * Ensure that the fetch method throws the appropriate Exception if called without
     * executing a statement before.
     *
     * @return \LibraryMock
     */
    public function testPrematureFetch(): \LibraryMock
    {
        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("LibraryMock")
            ->getMock();

        try {
            $lib->fetch();
        } catch (\SlaxWeb\Database\Exception\NoDataException $e) {
            $expected = "No statement has yet been executed. Unable to fetch data.";
            $eMsg = $e->getMessage();
            $this->assertEquals(
                $expected,
                $eMsg,
                "Raised exception does not yield expected message '{$expected}', actual: '{$eMsg}'"
            );
        }

        return $lib;
    }

    /**
     * Test Invalid Result Fetch
     *
     * Ensure that the 'fetch' method raises an exception when the statement does
     * not yield a valid result set.
     *
     * @param \LibraryMock $lib Mocked Database Library objecct
     * @return void
     *
     * @depends testPrematureFetch
     */
    public function testInvalidResultFetch(\LibraryMock $lib)
    {
        $pdo = $this->createMock("PDO");
        $statement = $this->createMock("PDOStatement");
        $statement->expects($this->once())
            ->method("fetchAll")
            ->willReturn(null);

        $pdo->expects($this->once())
            ->method("prepare")
            ->willReturn($statement);

        $lib->__construct($pdo, $this->createMock(QueryBuilder::class));

        $lib->execute("", []);
        try {
            $lib->fetch();
        } catch (\SlaxWeb\Database\Exception\NoDataException $e) {
            $expected = "Statement did not yield a valid result set.";
            $eMsg = $e->getMessage();
            $this->assertEquals(
                $expected,
                $eMsg,
                "Raised exception does not yield expected message '{$expected}', actual: '{$eMsg}'"
            );
        }
    }

    /**
     * Test Fetch
     *
     * Ensure that the 'fetch' method will return a propper Result object when everything
     * is ok with the fetching of data from the PDOStatement.
     *
     * @param \LibraryMock $lib Mocked Database Library objecct
     * @return void
     *
     * @depends testPrematureFetch
     */
    public function testFetch(\LibraryMock $lib)
    {
        $pdo = $this->createMock("PDO");
        $statement = $this->createMock("PDOStatement");
        $statement->expects($this->once())
            ->method("fetchAll")
            ->willReturn([]);

        $pdo->expects($this->once())
            ->method("prepare")
            ->willReturn($statement);

        $lib->__construct($pdo, $this->createMock(QueryBuilder::class));

        $lib->execute("", []);
        $this->assertInstanceOf(Result::class, $lib->fetch());
    }

    /**
     * Test Select
     *
     * Ensure that the 'select' method will call the execute method with propper
     * data that it retrieves from the query builder.
     *
     * @return void
     */
    public function testSelect()
    {
        $cols = ["foo", "bar"];
        $table = "baz";

        $pdo = $this->createMock("PDO");
        $qBuilder = $this->createMock(QueryBuilder::class);

        $qBuilder->expects($this->exactly(2))
            ->method("table")
            ->with($table)
            ->willReturn($qBuilder);

        $qBuilder->expects($this->exactly(2))
            ->method("select")
            ->with($cols)
            ->willReturn("TEST QUERY");

        $qBuilder->expects($this->exactly(2))
            ->method("getParams")
            ->willReturn([]);

        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(["execute", "fetch"])
            ->getMock();

        $lib->expects($this->exactly(2))
            ->method("execute")
            ->with("TEST QUERY", [])
            ->will($this->onConsecutiveCalls(true, false));

        $lib->expects($this->once())
            ->method("fetch")
            ->willReturn($this->createMock(Result::class));

        $lib->__construct($pdo, $qBuilder);

        $this->assertInstanceOf(Result::class, $lib->select($table, $cols));

        try {
            $lib->select($table, $cols);
        } catch (QueryException $e) {
            $this->assertEquals("Query execution resulted in an error", $e->getMessage());
            $thrown = true;
        } finally {
            $this->assertTrue($thrown);
        }
    }
}
