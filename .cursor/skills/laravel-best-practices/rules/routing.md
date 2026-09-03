# Routing & Controllers Best Practices

Versioned API lives under `/api/v1`. See `.cursor/rules/api-v1.mdc`.

## Use Implicit Route Model Binding

Let Laravel resolve models from route parameters.

Incorrect:
```php
public function show(int $id)
{
    $offer = Offer::findOrFail($id);
}
```

Correct:
```php
public function show(Offer $offer): JsonResponse
{
    return $this->apiService->ok(
        TranslationHelper::get('translations.offer.shown'),
        new OfferResource($offer),
    );
}
```

## Use Scoped Bindings for Nested Resources

Enforce parent-child relationships when routes are nested.

```php
Route::get('/offers/{offer}/photos/{photo}', ...)->scopeBindings();
```

## Use API resource routes

Use `Route::apiResource()` in `routes/api/v1.php` (not web `Route::resource()`).

```php
Route::apiResource('offers', OfferController::class);
```

## Keep Controllers Thin

Aim for under 10 lines per method. Extract business logic to **domain services**. Return the v1 envelope via `ApiServiceInterface` — not `view()`, `redirect()`, or raw `response()->json()`.

Incorrect: validation, file moves, Eloquent, events, and a redirect in the controller.

Correct:
```php
public function store(StoreOfferRequest $request): JsonResponse
{
    $offer = $this->offerService->create(CreateOfferDto::fromRequest($request));

    return $this->apiService->created(
        TranslationHelper::get('translations.offer.created'),
        new OfferResource($offer),
    );
}
```

## Type-Hint Form Requests

Type-hinting Form Requests runs validation before the method. `authorize()` returns `true` (no auth).
