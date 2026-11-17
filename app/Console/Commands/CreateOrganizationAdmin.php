<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use App\OrganizationRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

#[Signature('gearhub:make-admin
    {--app : Legacy alias for --platform}
    {--platform : Create a GearHub platform administrator}
    {--organization= : Organization slug}
    {--name= : Admin name}
    {--email= : Admin email}
    {--password= : Admin password (avoid using in shared shell history)}
    {--password-env= : Environment variable containing the admin password}')]
#[Description('Create a platform-wide or organization admin user')]
class CreateOrganizationAdmin extends Command
{
    public function handle(): int
    {
        $isPlatformAdmin = (bool) $this->option('app') || (bool) $this->option('platform');

        /** @var array{organization: ?string, name: ?string, email: ?string, password: ?string} $values */
        $values = [
            'organization' => $this->option('organization'),
            'name' => $this->option('name'),
            'email' => $this->option('email'),
            'password' => $this->option('password'),
        ];
        $passwordEnvironment = $this->option('password-env');

        if (
            $values['password'] === null
            && is_string($passwordEnvironment)
            && $passwordEnvironment !== ''
        ) {
            $environmentPassword = getenv($passwordEnvironment);
            $values['password'] = $environmentPassword === false ? null : $environmentPassword;
        }

        if ($isPlatformAdmin && $values['organization'] !== null) {
            $this->error('--platform cannot be used with --organization.');

            return self::FAILURE;
        }

        if ($this->input->isInteractive()) {
            if (! $isPlatformAdmin) {
                $values['organization'] ??= $this->ask('Organization slug');
            }

            $values['name'] ??= $this->ask('Name');
            $values['email'] ??= $this->ask('Email address');

            if ($values['password'] === null) {
                $values['password'] = $this->secret('Password');

                if ($values['password'] !== $this->secret('Confirm password')) {
                    $this->error('The passwords do not match.');

                    return self::FAILURE;
                }
            }
        } else {
            $requiredValues = [
                'name' => $values['name'],
                'email' => $values['email'],
                'password' => $values['password'],
            ];

            if (! $isPlatformAdmin) {
                $requiredValues['organization'] = $values['organization'];
            }

            foreach ($requiredValues as $value) {
                if (! is_string($value) || trim($value) === '') {
                    $requiredOptions = $isPlatformAdmin
                        ? '--name, --email, and --password or --password-env'
                        : '--organization, --name, --email, and --password or --password-env';

                    $this->error("Non-interactive mode requires {$requiredOptions}.");

                    return self::FAILURE;
                }
            }
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
        ];

        if (! $isPlatformAdmin) {
            $rules['organization'] = ['required', 'string', 'max:255'];
        }

        $validator = Validator::make($values, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $organization = $isPlatformAdmin
            ? null
            : Organization::query()->where('slug', $values['organization'])->first();

        if (! $isPlatformAdmin && $organization === null) {
            $this->error("No organization exists with slug [{$values['organization']}].");

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($organization, $values, $isPlatformAdmin): User {
            $user = User::create([
                'name' => $values['name'],
                'email' => $values['email'],
                'password' => $values['password'],
            ]);

            if ($isPlatformAdmin) {
                $user->forceFill(['is_platform_admin' => true])->save();
            } else {
                $user->organizations()->attach($organization, [
                    'role' => OrganizationRole::Admin->value,
                ]);
            }

            return $user;
        });

        $message = $isPlatformAdmin
            ? "Platform admin user {$user->email} created."
            : "Admin user {$user->email} created for {$organization->name}.";

        $this->info($message);

        return self::SUCCESS;
    }
}
