<?php
namespace SlaxWeb\DatabasePDO\Test\Unit;

use Exception;
use Mockery as m;
use SlaxWeb\DatabasePDO\Migration\Manager;
use SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException;

class MigrationManagerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $repository = __DIR__ . "/../_data/migration_repository/";

    public function testExistingRepositoryCheck()
    {
        mkdir($this->repository, 0755, true);
        try {
            $migrationManager = m::mock(Manager::class, array($this->repository));
        } catch (MigrationRepositoryException $e) {
            throw new Exception(
                "No exception was expected with an existing repository directory"
            );
        }
        $this->recurRmDir($this->repository);
    }

    public function testRepositoryCreation()
    {
        if (file_exists($this->repository)) {
            $this->recurRmDir($this->repository);
        }

        try {
            $migrationManager = m::mock(Manager::class, array($this->repository));
        } catch (MigrationRepositoryException $e) {
            throw new Exception(
                "No exception was expected when creating a test repository directory"
            );
        }

        $this->assertTrue(file_exists($this->repository));
        $this->recurRmDir($this->repository);
    }

    public function testRepositoryWritabilityCheck()
    {
        if (file_exists($this->repository) === false) {
            mkdir($this->repository, 0000, true);
        } else {
            chmod($this->repository, 0000);
        }

        $exception = false;
        try {
            $migrationManager = m::mock(Manager::class, array($this->repository));
        } catch (MigrationRepositoryException $e) {
            $exception = true;
            $this->assertEquals(
                "Received migration repository is not writable!",
                $e->getMessage()
            );
        }

        $this->assertTrue($exception);
        chmod($this->repository, 0755);
        $this->recurRmDir($this->repository);
    }

    protected function _before()
    {
    }

    protected function _after()
    {
        if (file_exists($this->repository)) {
            $this->recurRmDir($this->repository);
        }
        m::close();
    }

    protected function recurRmDir(string $dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }
            if (is_dir($file)) {
                $this->recurRmDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}
