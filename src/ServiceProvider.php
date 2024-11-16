<?php

namespace Core;

use Illuminate\Support\{
    ServiceProvider as BaseServiceProvider
};

use Core\Response;
use Illuminate\Foundation\Configuration\{
    ApplicationBuilder,
    Exceptions,
    Middleware
};
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
    BadRequestHttpException,
    ConflictHttpException,
    ControllerDoesNotReturnResponseException,
    GoneHttpException,
    HttpException,
    LengthRequiredHttpException,
    LockedHttpException,
    MethodNotAllowedHttpException,
    NotAcceptableHttpException,
    NotFoundHttpException,
    PreconditionFailedHttpException,
    PreconditionRequiredHttpException,
    ServiceUnavailableHttpException,
    TooManyRequestsHttpException,
    UnauthorizedHttpException,
    UnexpectedSessionUsageException,
    UnprocessableEntityHttpException,
    UnsupportedMediaTypeHttpException,
    ResolverNotFoundException,
    NearMissValueResolverException,
};

class ServiceProvider extends BaseServiceProvider
{
    /**
     * The Folio / page middleware that have been defined by the user.
     *
     * @var array
     */
    protected array $pageMiddleware = [];

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->exceptions();
        $this->middlewares();
        $this->routes();
    }

    /**
     * Bootstrap the application events
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function routes()
    {
        $this->app[ApplicationBuilder::class]->withRouting(
            api: glob(app_path('Features/*/Router.php')), // TODO: how cache it ?
            apiPrefix: '',
            // commands: app_path('/routes/console.php'),
            // health: '/up',
        );
    }

    public function middlewares()
    {
        $this->app[ApplicationBuilder::class]->withMiddleware(function (Middleware $middleware) {
            // $middleware->alias([
            //     'authorizer' => Authorize::class,
            // ]);
            // $middleware->append(Authorize::class);
        });
    }

    public function exceptions()
    {
        $this->app[ApplicationBuilder::class]->withExceptions(function (Exceptions $x) {
            $x->renderable(function (ValidationException|HttpException|\Exception|\Throwable $e) {
                $response = match (get_class($e)) {
                    NotFoundHttpException::class,
                    MethodNotAllowedHttpException::class,
                    NotAcceptableHttpException::class,
                    ConflictHttpException::class,
                    ControllerDoesNotReturnResponseException::class,
                    RouteNotFoundException::class
                        => Response::notFound(),

                    UnauthorizedHttpException::class,
                    UnauthorizedException::class
                        => Response::unauthorized(),

                    AccessDeniedHttpException::class,
                    GoneHttpException::class,
                    LockedHttpException::class,
                    TooManyRequestsHttpException::class,
                    ServiceUnavailableHttpException::class,
                    UnexpectedSessionUsageException::class,
                    NearMissValueResolverException::class,
                    ResolverNotFoundException::class,
                    HttpException::class
                        => Response::fromStatusCode($e->getStatusCode()),

                    BadRequestHttpException::class,
                    LengthRequiredHttpException::class,
                    PreconditionFailedHttpException::class,
                    UnprocessableEntityHttpException::class,
                    UnsupportedMediaTypeHttpException::class,
                    PreconditionRequiredHttpException::class,
                    ValidationException::class,
                        => Response::badRequest()->errors(method_exists($e, 'errors') ? $e->errors() : []),

                    \Throwable::class,
                    \BadMethodCallException::class
                        => Response::internalError(),

                    default => config('app.debug') ? false : Response::internalError(get_class($e)),
                    // default => Response::internalError($e->getMessage())->data($e->getTrace()),
                };

                return $response && $e->getMessage()
                    ? $response->message($e->getMessage())
                    : $response;
            });
        });
    }
}
