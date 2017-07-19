<?php
/**
 * Migration Manager
 *
 * Migration manager controls adding, removing, editing, and executing database
 * migrations.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Migration;

use SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException;

class Manager
{
    /**
     * Migration repository directory
     *
     * @var string
     */
    protected $repository = "";

    /**
     * Migrations
     *
     * @param array
     */
    protected $migrations = [];

    /**
     * Executed migrations
     *
     * @param array
     */
    protected $executed = [];

    /**
     * Class constructor
     *
     * Copies the class dependencies into internal properties, and checks if the
     * retrieved repository directory is writable.
     *
     * @param string $repository Migration repository directory - must be writable.
     */
    public function __construct(string $repository)
    {
        $this->checkRepository($repository);
        $this->repository = rtrim($repository, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->loadStatus("migrations");
        $this->loadStatus("executed");
    }

    /**
     * Check repository
     *
     * Checks the migration repository derectory, if it exists and is writable.
     * If it does not exist, it will be created. Throws an exception if it can not
     * be created or is not writable.
     *
     * @param string $repository Migration repository directory
     * @return void
     *
     * @throws \SlaxWeb\DatabasePDO|Exception\MigrationRepositoryException
     */
    protected function checkRepository(string $repository)
    {
        if (file_exists($repository) === false) {
            if (mkdir($repository, 0755, true) === false) {
                throw new MigrationRepositoryException(
                    "Unable to create migration repository exception due to an error."
                );
            }
            return;
        }

        if (is_dir($repository) === false) {
            throw new MigrationRepositoryException(
                "Received migration repository is not a directory!"
            );
        }

        if (is_writable($repository) === false) {
            throw new MigrationRepositoryException(
                "Received migration repository is not writable!"
            );
        }
    }

    /**
     * Load migration status file
     *
     * Loads the migration status file, or creates a new one if it does not exist.
     *
     * @param string $name Name of the migration file
     * @return void
     *
     * @throws \SlaxWeb\DatabasePDO|Exception\MigrationRepositoryException
     */
    protected function loadStatus(string $name)
    {
        if (file_exists("{$this->repository}.{$name}.json")) {
            $this->{$name} = json_decode(
                file_get_contents("{$this->repository}.{$name}.json"),
                true
            );
            if (is_array($this->{$name}) === false) {
                throw new MigrationRepositoryException(
                    "Migration file is corrupted!"
                );
            }
            ksort($this->{$name});
            return;
        }
        // status file does not exist, create an empty one
        $this->{$name} = [];
        file_put_contents("{$this->repository}.{$name}.json", json_encode($this->{$name}));
    }
}
