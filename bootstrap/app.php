<?php

use App\Exceptions\OfferSoldOutException;
use App\Http\Middleware\EnsureApiDocsEnabled;
use App\Services\Api\Contracts\ApiServiceInterface;
use App\Support\TranslationHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::get('/', function (Request $request) {
                if (config('api.docs_enabled')) {
                    return redirect('/api/documentation');
                }

                return response()->json([
                    'message' => 'Property Offers API',
                    'health' => url('/api/health'),
                ]);
            });

            Route::fallback(function (Request $request) {
                $accept = strtolower((string) $request->header('Accept', ''));
                $prefersHtml = str_contains($accept, 'text/html');

                if (
                    config('api.docs_enabled')
                    && $prefersHtml
                    && ! $request->is('api/*')
                    && ! $request->is('up')
                    && ! $request->is('docs')
                    && ! $request->is('docs/*')
                ) {
                    return redirect('/api/documentation');
                }

                abort(404);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.docs' => EnsureApiDocsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            return app(ApiServiceInterface::class)->validationError(
                TranslationHelper::get('translations.common.validation_error'),
                $e->errors(),
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return app(ApiServiceInterface::class)->notFound(
                TranslationHelper::get('translations.common.not_found'),
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return app(ApiServiceInterface::class)->notFound(
                TranslationHelper::get('translations.common.not_found'),
            );
        });

        $exceptions->render(function (OfferSoldOutException $e, Request $request) {
            return app(ApiServiceInterface::class)->conflict($e->getMessage());
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof HttpExceptionInterface) {
                return null;
            }

            return app(ApiServiceInterface::class)->serverError(
                $e->getMessage(),
                TranslationHelper::get('translations.common.server_error'),
            );
        });
    })->create();
