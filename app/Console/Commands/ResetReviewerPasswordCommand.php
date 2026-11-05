<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetReviewerPasswordCommand extends Command
{
    protected $signature = 'app:reset-reviewer-password {email=reviewer@csc2027.ph} {password?}';

    protected $description = 'Safely set or reset the reviewer account password';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("User with email [{$email}] not found.");
            return Command::FAILURE;
        }

        $password = $this->argument('password');
        if (empty($password)) {
            $password = $this->secret('Enter new secure password (or leave blank to generate a random 16-character password):');
        }

        if (empty($password)) {
            $password = Str::password(16, symbols: true);
            $this->info("Generated secure password: {$password}");
        }

        $user->password = Hash::make((string) $password);
        $user->save();

        $this->info("Password for [{$email}] successfully updated.");

        return Command::SUCCESS;
    }
}
