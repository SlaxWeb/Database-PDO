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
     * Test Insert
     *
     * Ensure the insert method works as intended, that it calls the appropriate
     * methods to the PDO and PDOStatemenet objects.
     *
     * @return false
     */
    public function testInsert()
    {
        $data = ["foo" => "bar", "baz" => "qux"];
        $testQuery = "INSERT INTO \"{$this->_testTable}\" (\""
            . implode("\",\"", array_keys($data))
            . "\") VALUES ("
            . rtrim(str_repeat("?,", count($data)), ",")
            . ");";

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

        $lib = $this->getMockBuilder(\SlaxWeb\DatabasePDO\Library::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $lib->__construct($pdo);

        $this->assertTrue($lib->insert($this->_testTable, $data));
    }
}
