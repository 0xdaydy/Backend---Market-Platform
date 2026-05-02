<?php

namespace App\Console\Commands;

use Dedoc\Scramble\Scramble;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportOpenApi extends Command
{
    protected $signature = 'openapi:export {--path=docs/openapi.json : Export path}';

    protected $description = 'Export OpenAPI specification to a JSON file';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        $response = app()->handle(
            \Illuminate\Http\Request::create('/docs/api.json')
        );

        $spec = json_decode($response->getContent(), true);

        File::makeDirectory(dirname($path), 0755, true, true);
        File::put($path, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("OpenAPI spec exported to {$path}");

        return self::SUCCESS;
    }
}
