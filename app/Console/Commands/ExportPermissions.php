<?php

namespace App\Console\Commands;

use App\Permissions\PermissionTypeScriptExporter;
use Illuminate\Console\Command;

class ExportPermissions extends Command
{
    protected $signature = 'permissions:export';

    protected $description = 'Generate the TypeScript permission constants for the frontend';

    public function handle(): int
    {
        file_put_contents(base_path(PermissionTypeScriptExporter::PATH), PermissionTypeScriptExporter::render());

        $this->components->info('Wrote '.PermissionTypeScriptExporter::PATH);

        return self::SUCCESS;
    }
}
