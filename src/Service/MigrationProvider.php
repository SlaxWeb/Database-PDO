<?php
/**
 * PDO Database Migration Provider
 *
 * Registers the Migration services to the Dependency Injection Container
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Service;

use Pimple\Container;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use SlaxWeb\DatabasePDO\Migration\Manager as MigrationManager;

class MigrationProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register Provider
     *
     * Called when the container is about to register this provider with the DIC.
     * It should define all the services, or call other methods that define the
     * services.
     *
     * @param \Pimple\Container $app Dependency Injection Container
     * @return void
     */
    public function register(Container $app)
    {
        $app["migration.service"] = function(Container $app) {
            $app["autoloader.service"]->addPsr4(
                $app["config.service"]["migration.namespace"],
                $app["config.service"]["migration.repository"]
            );

            return new MigrationManager(
                $app["config.service"]["migration.repository"],
                $app["loadMigrationClass.service"]
            );
        };

        $app["loadMigrationClass.service"] = $app->protect(
            function(string $migration) use ($app) {
                $className = "\\{$app["config.service"]["migration.namespace"]}{$migration}";

                if (class_exists($className) === false) {
                    throw new MigrationException(
                        "The migration class '{$className}' was not found. Please check "
                        . "the migration class."
                    );
                }

                return new $className(
                    $app["queryBuilder.service"],
                    $app["pdo.service"](),
                    $app["logger.service"]()
                );
            }
        );
    }
}
