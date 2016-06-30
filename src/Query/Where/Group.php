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
     * Parameters
     *
     * @var array
     */
    protected $_params = [];

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
        $this->_params = array_merge($this->_params, $first["predicate"]->getParams());
        foreach ($this->_list as $predicate) {
            $where .= " {$predicate["opr"]} " . $predicate["predicate"]->convert();
            $this->_params = array_merge($this->_params, $predicate["predicate"]->getParams());
        }
        return "{$where})";
    }

    /**
     * Get parameters
     *
     * Returns the list of parameters for this predicate.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
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
     * @return self
     */
    public function where(string $column, $value, string $lOpr = Predicate::OPR_EQUAL, string $cOpr = "AND"): self
    {
        $this->_list[] = [
            "opr"       =>  $cOpr,
            "predicate" =>  (new Predicate)
                ->setColumn($column)
                ->setValue($value)
                ->setOperator($lOpr)
        ];
        return $this;
    }

    /**
     * Or Where predicate
     *
     * Alias for 'where' method call with OR logical operator.
     *
     * @param string $column Column name
     * @param mixed $value Value of the predicate
     * @param string $opr Logical operator
     * @return self
     */
    public function orWhere(string $column, $value, string $opr = Predicate::OPR_EQUAL): self
    {
        return $this->where($column, $value, $opr, "OR");
    }

    /**
     * Add Where Predicate Group
     *
     * Adds a group of predicates to the list. The closure received as input must
     * receive the builder instance for building groups.
     *
     * @param Closure $predicates Grouped predicates definition closure
     * @param string $cOpr Comparisson operator, default string("AND")
     * @return self
     */
    public function groupWhere(\Closure $predicates, string $cOpr = "AND"): self
    {
        $group = new Group($cOpr);
        $predicates($group);
        $this->_list[] = [
            "opr"       =>  "",
            "predicate" =>  $group
        ];
        return $this;
    }

    /**
     * Or Where Predicate Group
     *
     * Alias for 'whereGroup' method call with OR logical operator.
     *
     * @param closure $predicates Grouped predicates definition closure
     * @return self
     */
    public function orGroupWhere(\Closure $predicates): self
    {
        return $this->groupWhere($predicates, "OR");
    }
}
