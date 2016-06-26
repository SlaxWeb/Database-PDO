<?php
/**
 * Where Statement Predicate Tests
 *
 * The Where Statement Predicate defines a Column, a value, and an comparison operator
 * for the SQL DML predicate. Its test ensures that the methods work as they should
 * and they produce proper WHERE predicates.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Tests\Unit;

use SlaxWeb\DatabasePDO\Query\Where\Predicate;

class PredicateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Predicate object
     *
     * @var \SlaxWeb\DatabasePDO\Query\Where\Predicate
     */
    protected $_predicate;

    protected function setUp()
    {
        $this->_predicate = (new Predicate)
            ->setColumn("\"foo\".\"bar\"");
    }

    protected function tearDown()
    {
    }

    /**
     * Test convert
     *
     * Test the conversion method that it successfuly converts the set Predicate
     * data to a correct SQL DML predicate. The test also ensures that all the comparison
     * operators that need to be handled differently, than a stanrdard "is equal"
     * operator, are handled correctly, and the conversion is done wihout error.
     *
     * @return void
     */
    public function testConvert()
    {
        $this->_predicate->setValue(1);
        $this->_predicate->setOperator(Predicate::OPR_EQUAL);
        $this->assertEquals("\"foo\".\"bar\" = 1", $this->_predicate->convert());

        $this->_predicate->setValue("foo");
        $this->_predicate->setOperator(Predicate::OPR_EQUAL);
        $this->assertEquals("\"foo\".\"bar\" = 'foo'", $this->_predicate->convert());

        $this->_predicate->setValue(null);
        $this->_predicate->setOperator(Predicate::OPR_NULL);
        $this->assertEquals("\"foo\".\"bar\" IS NULL", $this->_predicate->convert());

        $this->_predicate->setValue([1, 100]);
        $this->_predicate->setOperator(Predicate::OPR_BTWN);
        $this->assertEquals("\"foo\".\"bar\" BETWEEN 1 AND 100", $this->_predicate->convert());

        $this->_predicate->setValue([1, 2, 3, 4]);
        $this->_predicate->setOperator(Predicate::OPR_IN);
        $this->assertEquals("\"foo\".\"bar\" IN (1,2,3,4)", $this->_predicate->convert());
    }
}
