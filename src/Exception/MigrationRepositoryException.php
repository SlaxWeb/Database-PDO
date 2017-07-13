<?php
/**
 * Migration Repository Exception
 *
 * Thrown if the migration repository exists, but is not writable, or it exists
 * but is not a directory, or it does not exist and it can not be created
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Exception;

class MigrationRepositoryException extends \Exception
{
}
