<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('gearhub:platform-admin
    {email : Email address of an existing GearHub user}
    {--revoke : Remove platform administrator access}')]
#[Description('Grant or revoke platform administrator access for an existing user')]
class SetPlatformAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No GearHub user exists with email [{$email}].");

            return self::FAILURE;
        }

        $isPlatformAdmin = ! $this->option('revoke');
        $user->forceFill(['is_platform_admin' => $isPlatformAdmin])->save();

        $action = $isPlatformAdmin ? 'granted to' : 'revoked from';
        $this->info("Platform administrator access {$action} {$user->email}.");

        return self::SUCCESS;
    }
}
