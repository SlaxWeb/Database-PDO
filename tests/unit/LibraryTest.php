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

        $pdoLoader = function () use ($data, $testQuery) {
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

            return $pdo;
        };

        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $lib->__construct($pdoLoader);

        $this->assertTrue($lib->execute($testQuery, $data));
    }

    /**
     * Test Premature Fetch
     *
     * Ensure that the fetch method provides an empty result set when no other statement
     * has been executed before.
     *
     * @return void
     */
    public function testPrematureFetch()
    {
        $result = (new Library(function() {}))->fetch();
        $this->assertEmpty($result->getResults());
    }

    /**
     * Test Fetch
     *
     * Ensure that the 'fetch' method will return a propper Result object when everything
     * is ok with the fetching of data from the PDOStatement.
     *
     * @return void
     */
    public function testFetch()
    {
        $pdoLoader = function () {
            $pdo = $this->createMock("PDO");
            $statement = $this->createMock("PDOStatement");
            $statement->expects($this->once())
                ->method("fetchAll")
                ->willReturn(null);

            $pdo->expects($this->once())
                ->method("prepare")
                ->willReturn($statement);

            return $pdo;
        };

        $lib = $this->getMockBuilder(Library::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->setMockClassName("LibraryMock")
            ->getMock();

        $lib->__construct($pdoLoader);

        $lib->execute("", []);
        $this->assertInstanceOf(Result::class, $lib->fetch());
    }
}
