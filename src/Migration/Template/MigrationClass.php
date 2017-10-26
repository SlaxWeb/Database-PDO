<?php
namespace App\Migration;

use SlaxWeb\DatabasePDO\Migration\BaseMigration;

class MigrationClass extends BaseMigration
{
    /**
     * @inheritDocs
     */
    protected function up(): bool
    {
        return true;
    }

    /**
     * @inheritDocs
     */
    protected function down(): bool
    {
        return true;
    }
}
