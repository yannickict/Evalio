# Password Reset

Guests can use **Forgot password?** on login to request an email link, choose a new password and return to login. Resetting a password never approves an account or changes its role. Pending users still require administrator approval before login.

## Implementation

The four guest routes are `password.request` (GET form), `password.email` (POST link request), `password.reset` (GET token form) and `password.update` (POST reset). Both POST routes have a five-requests-per-minute rate limit.

`ForgotPasswordRequest` validates the email; `ResetPasswordRequest` validates the token, email and confirmed password with an eight-character minimum. `PasswordResetController` delegates token creation and validation to Laravel's password broker. Existing `password_reset_tokens` storage and `config/auth.php` configure a 60-minute expiry and a 60-second wait between link requests for the same account. No new migration is needed.

Known and unknown emails receive the same public confirmation. A valid reset saves a hashed password, rotates the remember token, emits `PasswordReset`, consumes the reset token and redirects to login. The shared auth layout displays confirmation messages and validation errors. Resetting does not automatically log the user in or invalidate all existing authenticated sessions.

## Local email and logs

Use these local settings with the URL matching your development server:

```env
MAIL_MAILER=log
APP_URL=http://localhost:8000
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

Run `php artisan config:clear`, log out, and request a reset for an email that exists in the current database. The log mailer writes the message and reset link to `storage/logs/laravel.log`. Unknown accounts generate no reset email; recently requested accounts are broker-throttled. The generic confirmation alone does not prove an email was sent.

Operational entries record `Password reset link request processed.` and `Password reset attempt processed.` with the broker's `status`. These entries include no email addresses, passwords or reset tokens. The local log mailer itself includes the email and token-bearing link; use it only for development and do not commit or share its contents.

For diagnosis, inspect the active configuration and broker result in Tinker:

```php
config('mail.default');
config('logging.default');
Password::sendResetLink(['email' => 'admin@example.com']);
```

Common statuses are `passwords.sent`, `passwords.user`, `passwords.throttled`, `passwords.reset` and `passwords.token`. Sending exceptions remain application errors rather than successful confirmations.

## Production

Configure a real mail transport, valid sender address and your public HTTPS `APP_URL`. Restrict accepted hosts at the web server or through Laravel trusted-host configuration so generated reset links use the intended host. Expired token cleanup can be scheduled with `auth:clear-resets`; the broker rejects expired tokens even before cleanup.

## Verification

On 8 October 2026, the user confirmed that local reset email logging works. `PasswordResetTest` adds coverage for guest forms, notifications, generic confirmations, success and login, token reuse, invalid/expired/wrong-account tokens, pending accounts, validation, safe status logging and rate limits. This is separate from a passing automated test run.

```powershell
php artisan test --filter='PasswordResetTest|AuthTest|PasswordChangeTest'
composer ci:check
```

Installed dependencies require PHP 8.4 or newer. The currently available XAMPP PHP 8.2 cannot run the Laravel test suite or the required formatter.
