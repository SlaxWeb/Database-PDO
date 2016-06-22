<?php
/**
 * No Data Exception
 *
 * Thrown when a 'fetch' is attempted and an statement has not yet been executed
 * or has not returned a valid result set.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Exception;

class NoDataException extends \Exception
{
}
