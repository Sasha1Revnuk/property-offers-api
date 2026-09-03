# Architecture Best Practices

## Domain services (not Action classes)

Put business logic in `app/Services/<Name>/`: interface in `Contracts/`, implementation, optional `Dto/` and `Enumerators/`. Do **not** add invokable Action classes.

Every public method on the service must exist on `<Name>ServiceInterface`. Bind the interface in a provider. Inject the **interface** in controllers and other services.

See `.cursor/rules/laravel-architecture.mdc`.

## Use Dependency Injection

Always use constructor injection. Avoid `app()` or `resolve()` inside classes.

Incorrect:
```php
class OfferController extends Controller
{
    public function store(StoreOfferRequest $request)
    {
        $service = app(OfferService::class);

        return $service->create($request->validated());
    }
}
```

Correct:
```php
class OfferController extends Controller
{
    public function __construct(private OfferServiceInterface $service) {}

    public function store(StoreOfferRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
```

## Code to Interfaces

Depend on **domain service interfaces** and on contracts at system boundaries (payment gateways, external APIs).

Incorrect (concrete domain service):
```php
class OfferController
{
    public function __construct(private OfferService $service) {}
}
```

Correct:
```php
class OfferController
{
    public function __construct(private OfferServiceInterface $service) {}
}
```

Also use interfaces for swappable infrastructure (HTTP clients, gateways). Bind in a service provider:

```php
$this->app->bind(OfferServiceInterface::class, OfferService::class);
```

## Default Sort by Descending

When no explicit order is specified, sort by `id` or `created_at` descending. Without an explicit `ORDER BY`, row order is undefined.

Incorrect:
```php
$posts = Post::paginate();
```

Correct:
```php
$posts = Post::latest()->paginate();
```

## Use Atomic Locks for Race Conditions

Prevent race conditions with `Cache::lock()` or `lockForUpdate()`.

```php
Cache::lock('order-processing-'.$order->id, 10)->block(5, function () use ($order) {
    $order->process();
});

// Or at query level
$product = Product::where('id', $id)->lockForUpdate()->first();
```

## Use `mb_*` String Functions

When no Laravel helper exists, prefer `mb_strlen`, `mb_strtolower`, etc. for UTF-8 safety. Standard PHP string functions count bytes, not characters.

Incorrect:
```php
strlen('José');          // 5 (bytes, not characters)
strtolower('MÜNCHEN');  // 'mÜnchen' — fails on multibyte
```

Correct:
```php
mb_strlen('José');             // 4 (characters)
mb_strtolower('MÜNCHEN');     // 'münchen'

// Prefer Laravel's Str helpers when available
Str::length('José');          // 4
Str::lower('MÜNCHEN');        // 'münchen'
```

## Use `defer()` for Post-Response Work

For lightweight tasks that don't need to survive a crash (logging, analytics, cleanup), use `defer()` instead of dispatching a job. The callback runs after the HTTP response is sent — no queue overhead.

Incorrect (job overhead for trivial work):
```php
dispatch(new LogPageView($page));
```

Correct (runs after response, same process):
```php
defer(fn () => PageView::create(['page_id' => $page->id, 'user_id' => auth()->id()]));
```

Use jobs when the work must survive process crashes or needs retry logic. Use `defer()` for fire-and-forget work.

## Use `Context` for Request-Scoped Data

The `Context` facade passes data through the entire request lifecycle — middleware, controllers, jobs, logs — without passing arguments manually.

```php
// In middleware
Context::add('tenant_id', $request->header('X-Tenant-ID'));

// Anywhere later — controllers, jobs, log context
$tenantId = Context::get('tenant_id');
```

Context data automatically propagates to queued jobs and is included in log entries. Use `Context::addHidden()` for sensitive data that should be available in queued jobs but excluded from log context. If data must not leave the current process, do not store it in `Context`.

## Use `Concurrency::run()` for Parallel Execution

Run independent operations in parallel using child processes — no async libraries needed.

```php
use Illuminate\Support\Facades\Concurrency;

[$users, $orders] = Concurrency::run([
    fn () => User::count(),
    fn () => Order::where('status', 'pending')->count(),
]);
```

Each closure runs in a separate process with full Laravel access. Use for independent database queries, API calls, or computations that would otherwise run sequentially.

## Convention Over Configuration

Follow Laravel conventions. Don't override defaults unnecessarily.

Incorrect:
```php
class Customer extends Model
{
    protected $table = 'Customer';
    protected $primaryKey = 'customer_id';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_customer', 'customer_id', 'role_id');
    }
}
```

Correct:
```php
class Customer extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```
