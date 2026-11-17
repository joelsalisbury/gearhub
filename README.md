# GearHub

GearHub is a small Laravel application for organizations that lend shared equipment. Staff sign in to their organization's workspace; public organization pages are available without an account.

## Local setup

```sh
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

For a fresh checkout only, copy `.env.example` to `.env` if one does not already exist, then generate an application key with `php artisan key:generate`.

The seeder creates NEMBA and its child organization, Quiet Corner NEMBA (`/qcnemba`). It does not create users or passwords.

## Create an admin

Run the command without options to create an organization admin through interactive prompts for the organization slug, name, email, and a hidden, confirmed password:

```sh
php artisan gearhub:make-admin
```

For an interactive app-wide admin, add `--app` to the command:

```sh
php artisan gearhub:make-admin --app
```

For one-line organization-admin creation, set `ADMIN_PASSWORD` in your shell or secret manager, then pass its variable name so the password is not included in the command arguments:

```sh
php artisan gearhub:make-admin --organization=qcnemba --name="Your Name" --email=you@example.test --password-env=ADMIN_PASSWORD --no-interaction
```

Use `--app` to create an app-wide admin instead. App admins are separate from organization memberships and can access every organization workspace:

```sh
php artisan gearhub:make-admin --app --name="Your Name" --email=you@example.test --password-env=ADMIN_PASSWORD --no-interaction
```

Both modes create a new account and reject an email address already in use. The organization-admin mode assigns the `admin` role only in its specified organization. The app-admin flag cannot be set through normal mass assignment.

## Tests

```sh
php artisan test
```
