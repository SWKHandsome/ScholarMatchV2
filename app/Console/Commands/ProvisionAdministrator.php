<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\PasswordByteLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ProvisionAdministrator extends Command
{
    protected $signature = 'scholarmatch:admin {email} {--name=Administrator}';

    protected $description = 'Provision an administrator through a controlled interactive setup';

    public function handle(): int
    {
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $this->secret('Password (at least 12 characters)')];
        $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'name' => 'required|string|max:100', 'password' => ['required', 'string', 'min:12', 'max:72', new PasswordByteLimit]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        User::create($data + ['role' => 'admin', 'is_active' => true]);
        $this->info('Administrator provisioned.');

        return self::SUCCESS;
    }
}
