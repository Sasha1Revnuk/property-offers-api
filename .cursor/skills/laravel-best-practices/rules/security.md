# Security Best Practices

## Mass Assignment Protection

Every model must define `$fillable` (whitelist) or `$guarded` (blacklist).

Incorrect:
```php
class User extends Model
{
    protected $guarded = []; // All fields are mass assignable
}
```

Correct:
```php
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

Never use `$guarded = []` on models that accept user input.

## No authentication on /api/v1

This API is public. Do **not** add policies, gates, Sanctum, session auth, or `Gate::authorize()`. Form Request `authorize()` returns `true`.

## Prevent SQL Injection

Always use parameter binding. Never interpolate user input into queries.

Incorrect:
```php
DB::select("SELECT * FROM users WHERE name = '{$request->name}'");
```

Correct:
```php
User::where('name', $request->name)->get();

// Raw expressions with bindings
User::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->get();
```

## Rate Limit API Routes

Apply `throttle` to `/api/v1` routes. There is no login endpoint.

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});
```

## Validate File Uploads

Validate extension, MIME type, and size. The `mimes` rule checks extensions; use `mimetypes` for actual MIME type validation. Never trust client-provided filenames.

```php
public function rules(): array
{
    return [
        'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ];
}
```

Store with generated filenames:

```php
$path = $request->file('avatar')->store('avatars', 'public');
```

## Keep Secrets Out of Code

Never commit `.env`. Access secrets via `config()` only.

Incorrect:
```php
$key = env('API_KEY');
```

Correct:
```php
// config/services.php
'api_key' => env('API_KEY'),

// In application code
$key = config('services.api_key');
```

## Audit Dependencies

Run `composer audit` periodically to check for known vulnerabilities in dependencies. Automate this in CI to catch issues before deployment.

```bash
composer audit
```

## Encrypt Sensitive Database Fields

Use `encrypted` cast for API keys/tokens and mark the attribute as `hidden`.

Incorrect:
```php
class Integration extends Model
{
    protected function casts(): array
    {
        return [
            'api_key' => 'string',
        ];
    }
}
```

Correct:
```php
class Integration extends Model
{
    protected $hidden = ['api_key', 'api_secret'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
        ];
    }
}
```
