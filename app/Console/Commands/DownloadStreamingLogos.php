<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadStreamingLogos extends Command
{
    /**
     * @var string
     */
    protected $signature = 'streaming:download-logos {--force : Re-download logos that already exist}';

    /**
     * @var string
     */
    protected $description = 'Descarga los logos de las plataformas de streaming (config/streaming.php) a la carpeta pública.';

    public function handle(): int
    {
        /** @var array<int, array{name: string, slug: string, max_profiles: int}> $services */
        $services = config('streaming.default_services', []);
        $logoPath = config('streaming.logo_path');
        $targetDir = public_path($logoPath);

        File::ensureDirectoryExists($targetDir);

        $downloaded = 0;
        $skipped = 0;
        $failed = [];

        foreach ($services as $service) {
            $slug = $service['slug'];
            $destination = $targetDir.DIRECTORY_SEPARATOR.$slug.'.svg';

            if (($service['download'] ?? true) === false) {
                $this->line("  <comment>•</comment> {$service['name']} (logo local incluido)");
                $skipped++;

                continue;
            }

            if (File::exists($destination) && ! $this->option('force')) {
                $this->line("  <comment>=</comment> {$service['name']} (ya existe)");
                $skipped++;

                continue;
            }

            $response = Http::timeout(15)->retry(2, 200, throw: false)->get("https://cdn.simpleicons.org/{$slug}");

            if ($response->successful() && str_contains($response->body(), '<svg')) {
                File::put($destination, $response->body());
                $this->line("  <info>✓</info> {$service['name']} → {$logoPath}/{$slug}.svg");
                $downloaded++;

                continue;
            }

            $this->line("  <fg=red>✗</fg=red> {$service['name']} ({$slug}) — HTTP {$response->status()}");
            $failed[] = $service['name'];
        }

        $this->newLine();
        $this->info("Descargados: {$downloaded} · Omitidos: {$skipped} · Fallidos: ".count($failed));

        if ($failed !== []) {
            $this->warn('Sin logo: '.implode(', ', $failed).'. Esos servicios usarán el ícono por defecto.');
        }

        return self::SUCCESS;
    }
}
