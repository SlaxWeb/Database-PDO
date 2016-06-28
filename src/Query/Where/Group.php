<?php
/**
 * Where Predicate Group
 *
 * The Where Predicate Group class groups multiple Predicate objects that allows
 * building more complicated where statements.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Query\Where;

class Group
{
    /**
     * Predicament list
     *
     * @var array
     */
    protected $_list = [];

    /**
     * Logical operator
     *
     * @var string
     */
    protected $_opr = "";

    /**
     * Class constructor
     *
     * Sets the logical operator that will be used to link this Predicate Group
     * in the SQL DML WHERE statement.
     *
     * @param string $opr Logical operator name, default string("AND")
     * @return void
     */
    public function __construct(string $opr = "AND")
    {
        $this->_opr = $opr;
    }

    /**
     * Convert Predicate Group to Statement
     *
     * Creates the SQL DML Where statement from the predicate list and returns it
     * to the caller.
     *
     * @return string
     */
    public function convert(): string
    {
        if (count($this->_list) < 1) {
            return "";
        }

        $where = " {$this->_opr} (";
        $first = array_shift($this->_list);
        $where .= $first["predicate"]->convert();
        foreach ($this->_list as $predicate) {
            $where .= " {$predicate["opr"]} " . $predicate["predicate"]->convert();
        }
        return "{$where})";
    }

    /**
     * Add predicament
     *
     * Creates a predicate object with the input parameters, and adds it to the
     * list of predicates. It returns an object of itself for method call linking.
     *
     * @param string $column Name of the column for the predicate
     * @param mixed $value Value for the predicate
     * @param stirng $lOpr Logical operator, default Predicate::OPR_EQUAL
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return void
     */
    public function where(string $column, $value, string $lOpr = Predicate::OPR_EQUAL, string $cOpr = "AND")
    {
        $this->_list[] = [
            "opr"       =>  $cOpr,
            "predicate" =>  (new Predicate)
                ->setColumn($column)
                ->setValue($value)
                ->setOperator($lOpr)
        ];
    }
}
