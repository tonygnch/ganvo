<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Turn a preview password into the bcrypt hash that goes in PREVIEW_PASSWORD_HASH.
 *
 * The prompt is hidden, so the password never lands in shell history, in a
 * process list, or in any file — only its hash does, and a hash cannot be
 * turned back into the password.
 */
class PreviewPassword extends Command
{
    protected $signature = 'ganvo:preview-password';

    protected $description = 'Hash a preview password for PREVIEW_PASSWORD_HASH (prompts without echoing)';

    public function handle(): int
    {
        $pass = (string) $this->secret('Preview password (not echoed)');
        if ($pass === '') {
            $this->error('Empty password — nothing generated.');
            return self::FAILURE;
        }
        if (mb_strlen($pass) < 8) {
            $this->error('Use at least 8 characters. This gate is the only thing in front of the site.');
            return self::FAILURE;
        }
        if ((string) $this->secret('Type it again to confirm') !== $pass) {
            $this->error('They did not match — nothing generated.');
            return self::FAILURE;
        }

        $hash = Hash::make($pass);

        $this->newLine();
        $this->info('Add these to the server .env (the hash, never the password):');
        $this->newLine();
        $this->line('PREVIEW_LOCK=true');
        $this->line('PREVIEW_USER=sankevi');
        // Single quotes: a bcrypt hash contains $ signs, which some .env
        // parsers and most shells would try to expand.
        $this->line("PREVIEW_PASSWORD_HASH='" . $hash . "'");
        $this->newLine();
        $this->warn('Then: php artisan config:cache   (the value is read through config)');
        $this->newLine();

        return self::SUCCESS;
    }
}
