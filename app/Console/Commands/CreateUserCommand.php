<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Create an application user for phone-based login';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Full name'));
        $phone = trim((string) $this->ask('10-digit phone number (login username)'));
        $password = (string) $this->ask('Password (minimum 8 characters)');

        $validator = Validator::make([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'integer', 'digits:10', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create([
            'name' => $name,
            'phone' => (int) $phone,
            'email' => "admin@ledger.local",
            'password' => Hash::make($password),
        ]);

        $this->info("User created successfully. Phone username: {$user->phone}");

        return self::SUCCESS;
    }
}
