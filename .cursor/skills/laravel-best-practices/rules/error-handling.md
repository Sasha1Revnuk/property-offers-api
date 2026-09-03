# Error Handling Best Practices

## Exception Reporting and Rendering

There are two valid approaches — choose one and apply it consistently across the project.

**Co-location on the exception class** — keeps behavior alongside the exception definition, easier to find:

```php
class InvalidOrderException extends Exception
{
    public function report(): void { /* custom reporting */ }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => TranslationHelper::get('translations.offer.invalid')], 422);
    }
}
```

**Centralized in `bootstrap/app.php`** — all exception handling in one place, easier to see the full picture:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (InvalidOrderException $e) { /* ... */ });
    $exceptions->render(function (InvalidOrderException $e, Request $request) {
        return response()->json(['success' => false, 'message' => TranslationHelper::get('translations.offer.invalid')], 422);
    });
})
```

Check the existing codebase and follow whichever pattern is already established.

## Use `ShouldntReport` for Exceptions That Should Never Log

More discoverable than listing classes in `dontReport()`.

```php
class PodcastProcessingException extends Exception implements ShouldntReport {}
```

## Throttle High-Volume Exceptions

A single failing integration can flood error tracking. Use `throttle()` to rate-limit per exception type.

## Enable `dontReportDuplicates()`

Prevents the same exception instance from being logged multiple times when `report($e)` is called in multiple catch blocks.

## Force JSON Error Rendering for API Routes

Laravel auto-detects `Accept: application/json` but API clients may not set it. Explicitly declare JSON rendering for API routes.

```php
$exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
```

## Add Context to Exception Classes

Attach structured data to exceptions at the source via a `context()` method — Laravel includes it automatically in the log entry.

```php
class InvalidOrderException extends Exception
{
    public function context(): array
    {
        return ['order_id' => $this->orderId];
    }
}
```
