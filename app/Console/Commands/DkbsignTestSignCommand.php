<?php

namespace App\Console\Commands;

use Dkbs\LaravelDkbsign\Exceptions\DkbsignException;
use Dkbs\LaravelDkbsign\Facades\Dkbsign;
use Dkbs\LaravelDkbsign\SelfSign\SelfSignRequest;
use Dkbs\LaravelDkbsign\SelfSign\SignaturePlacement;
use Dkbs\LaravelDkbsign\SelfSign\SignParams;
use Illuminate\Console\Command;

class DkbsignTestSignCommand extends Command
{
    protected $signature = 'dkbsign:test-sign
                            {pdf : Chemin absolu ou relatif au projet vers le PDF}
                            {otp : Code OTP reçu par email (6 chiffres)}
                            {--image= : Chemin optionnel vers PNG/JPG signature}
                            {--x=25 : Position X en mm (coin haut-gauche de la zone)}
                            {--y=220 : Position Y en mm depuis le haut de la page}
                            {--width=120 : Largeur de la zone en mm (plus grand = signature plus nette)}
                            {--height=45 : Hauteur de la zone en mm}';

    protected $description = 'Test self-sign DKBSign (SDK) avec PDF + OTP';

    public function handle(): int
    {
        if (blank(config('dkbsign.token'))) {
            $this->error('DKBSIGN_TOKEN manquant dans .env');

            return self::FAILURE;
        }

        $pdfPath = $this->resolvePath($this->argument('pdf'));
        if (! is_file($pdfPath)) {
            $this->error('Fichier PDF introuvable : '.$pdfPath);
            $this->line('Copie un PDF vers storage/app/test.pdf ou passe un chemin absolu.');

            return self::FAILURE;
        }
        if (! is_readable($pdfPath)) {
            $this->error('PDF non lisible (droits) : '.$pdfPath);

            return self::FAILURE;
        }

        $imagePath = $this->option('image');
        if ($imagePath !== null && $imagePath !== '') {
            $imagePath = $this->resolvePath($imagePath);
            if (! is_file($imagePath) || ! is_readable($imagePath)) {
                $this->error('Image signature introuvable ou non lisible : '.$imagePath);

                return self::FAILURE;
            }
        } else {
            $imagePath = null;
        }

        $otp = preg_replace('/\D+/', '', (string) $this->argument('otp'));
        if (strlen($otp) !== 6) {
            $this->error('Le code OTP doit contenir exactement 6 chiffres (ex. copié depuis l’email).');

            return self::FAILURE;
        }

        // A4 ≈ 210×297 mm : zone bas-gauche visible (y mesuré depuis le haut, comme la doc DKBSign)
        $params = SignParams::forPage(
            0,
            new SignaturePlacement(
                x: (float) $this->option('x'),
                y: (float) $this->option('y'),
                width: (float) $this->option('width'),
                height: (float) $this->option('height'),
            ),
        )->withOtp($otp);

        try {
            $result = Dkbsign::sign(new SelfSignRequest(
                pdfPath: $pdfPath,
                params: $params,
                signatureImagePath: $imagePath,
            ));
        } catch (DkbsignException $e) {
            $this->error($e->getMessage());
            if ($e->statusCode > 0) {
                $this->line('HTTP '.$e->statusCode);
            }

            return self::FAILURE;
        }

        $this->info($result->message);
        $this->line('UUID: '.$result->documentUuid);
        $this->line('PDF signé: '.$result->signedPdfUrl);
        $this->line('Vérification: '.$result->verificationUrl);

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        return base_path(str_replace('/', DIRECTORY_SEPARATOR, $path));
    }
}
