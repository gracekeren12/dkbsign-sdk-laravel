<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DkbsignDownloadSignedCommand extends Command
{
    protected $signature = 'dkbsign:download
                            {url : URL download renvoyée après sign (signed_pdf_url)}
                            {output=storage/app/signed-output.pdf : Fichier de sortie}';

    protected $description = 'Télécharge le PDF signé (Bearer token depuis .env)';

    public function handle(): int
    {
        $token = config('dkbsign.token');
        if (blank($token)) {
            $this->error('DKBSIGN_TOKEN manquant dans .env');

            return self::FAILURE;
        }

        $url = $this->argument('url');
        $output = $this->argument('output');
        if (! str_starts_with($output, DIRECTORY_SEPARATOR) && ! preg_match('#^[A-Za-z]:[\\\\/]#', $output)) {
            $output = base_path(str_replace('/', DIRECTORY_SEPARATOR, $output));
        }

        $response = Http::withToken($token)
            ->accept('application/pdf')
            ->timeout((int) config('dkbsign.timeout', 120))
            ->get($url);

        if (! $response->successful()) {
            $this->error('Téléchargement échoué HTTP '.$response->status());
            $this->line($response->body());

            return self::FAILURE;
        }

        file_put_contents($output, $response->body());
        $this->info('PDF enregistré : '.$output);

        return self::SUCCESS;
    }
}
