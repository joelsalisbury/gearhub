# GearHub

GearHub is a small Laravel application for organizations that lend shared equipment. Staff sign in to their organization's workspace; public organization pages are available without an account.

## Local setup

```sh
composer install
npm install
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

For a fresh checkout only, copy `.env.example` to `.env` if one does not already exist, then generate an application key with `php artisan key:generate`.

The seeder creates NEMBA and its child organization, Quiet Corner NEMBA (`/qcnemba`). It does not create users or passwords.
When `APP_ENV=local`, the default seeder also adds repeatable example gear for Quiet Corner NEMBA. Inventory seed data is not automatically added in other environments.

## Create an admin

Run the command without options to create an organization admin through interactive prompts for the organization slug, name, email, and a hidden, confirmed password:

```sh
php artisan gearhub:make-admin
```

For an interactive platform admin, add `--platform` to the command. The previous `--app` option remains an alias:

```sh
php artisan gearhub:make-admin --platform
```

For one-line organization-admin creation, set `ADMIN_PASSWORD` in your shell or secret manager, then pass its variable name so the password is not included in the command arguments:

```sh
php artisan gearhub:make-admin --organization=qcnemba --name="Your Name" --email=you@example.test --password-env=ADMIN_PASSWORD --no-interaction
```

Use `--platform` to create a platform admin instead. Platform admins are separate from organization memberships and can access all organization workspaces:

```sh
php artisan gearhub:make-admin --platform --name="Your Name" --email=you@example.test --password-env=ADMIN_PASSWORD --no-interaction
```

Both modes create a new account and reject an email address already in use. The organization-admin mode assigns the `admin` role only in its specified organization. The platform-admin flag cannot be set through normal mass assignment.

To grant or revoke platform administrator access for an existing user without changing their organization memberships, run:

```sh
php artisan gearhub:platform-admin you@example.test
php artisan gearhub:platform-admin you@example.test --revoke
```

This command never creates accounts. Platform administrators can manage organizations at `/admin/organizations`.
They can create organizations, assign parent organizations, review staff, and deactivate or reactivate organizations. Organization administrators manage their own organization's staff and settings from its workspace; membership is never inherited from a parent organization.

## Tests

```sh
php artisan test
```
