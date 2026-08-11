<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the HTTP route that your GraphQL server responds to.
    | The route is registered under the `api` middleware group by Laravel,
    | so the full URL will be /api/graphql.
    |
    | NOTE: The GraphQL endpoint is only active when config('api.graphql_enabled')
    | is true. Lighthouse's ServiceProvider is conditionally registered in
    | AppServiceProvider based on that flag.
    |
    */

    'route' => [
        /*
         * The URI the endpoint responds to.
         * Since Lighthouse registers its own routes independently (not inside
         * the api route group), we use the `prefix` option to place it under /api/graphql.
         */
        'uri' => '/graphql',

        /*
         * The `prefix` key places Lighthouse's route under the given path prefix,
         * resulting in the full URL /api/graphql.
         */
        'prefix' => 'api',

        /*
         * Lighthouse creates a named route for convenient URL generation and redirects.
         */
        'name' => 'graphql',

        /*
         * Beware that middleware defined here runs before the GraphQL execution phase.
         * Make sure to return spec-compliant responses in case an error is thrown.
         */
        'middleware' => [
            // Ensures the request is not vulnerable to cross-site request forgery.
            // Nuwave\Lighthouse\Http\Middleware\EnsureXHR::class,

            // Always set the `Accept: application/json` header.
            Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,

            // Logs in a user if they are authenticated. In contrast to Laravel's 'auth'
            // middleware, this delegates auth and permission checks to the field level.
            Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication::class,

            // Apply the same rate limiting as the REST API (Requirement 16.4).
            'throttle:api',

            // Apply the same Spatie permission rules as the REST API (Requirement 16.3).
            // Checks authentication and baseline read permissions at the HTTP level.
            // Fine-grained field-level auth is handled by @guard directives in the schema.
            \App\Http\Middleware\GraphQL\ApplySpatiePermissions::class,
        ],

        /*
         * The `prefix`, `domain` and `where` configuration options are optional.
         */
        // 'prefix' => '',
        // 'domain' => '',
        // 'where' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | The guards to use for authenticating GraphQL requests, if needed.
    | Used in directives such as `@guard` or the `AttemptAuthentication` middleware.
    | Falls back to the Laravel default if `null`.
    |
    */

    'guards' => ['sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Schema Path
    |--------------------------------------------------------------------------
    |
    | Path to your .graphql schema file.
    | Additional schema files may be imported from within that file.
    |
    */

    'schema_path' => base_path('graphql/schema.graphql'),

    /*
    |--------------------------------------------------------------------------
    | Schema Cache
    |--------------------------------------------------------------------------
    |
    | A large part of schema generation consists of parsing and AST manipulation.
    | This operation is very expensive, so it is highly recommended enabling
    | caching of the final schema to optimize performance of large schemas.
    |
    */

    'schema_cache' => [
        /*
         * Setting to true enables schema caching.
         */
        'enable' => env('LIGHTHOUSE_SCHEMA_CACHE_ENABLE', env('APP_ENV') !== 'local'),

        /*
         * File path to store the lighthouse schema.
         */
        'path' => env('LIGHTHOUSE_SCHEMA_CACHE_PATH', base_path('bootstrap/cache/lighthouse-schema.php')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Directive Tags
    |--------------------------------------------------------------------------
    |
    | Should the `@cache` directive use a tagged cache?
    |
    */
    'cache_directive_tags' => false,

    /*
    |--------------------------------------------------------------------------
    | Query Cache
    |--------------------------------------------------------------------------
    |
    | Caches the result of parsing incoming query strings to boost performance on subsequent requests.
    |
    */

    'query_cache' => [
        /*
         * Setting to true enables query caching.
         */
        'enable' => env('LIGHTHOUSE_QUERY_CACHE_ENABLE', true),

        /*
         * Configures which mechanism to use for the query cache.
         * - store: use an external shared cache through a Laravel cache store like Redis or Memcached
         * - opcache: store parsed queries in PHP files on the local filesystem to leverage OPcache
         * - hybrid: leverage OPcache, but use a shared cache store when local files are not found
         */
        'mode' => env('LIGHTHOUSE_QUERY_CACHE_MODE', 'store'),

        /*
         * Specifies the path where the PHP files are stored when using opcache or hybrid mode.
         * The given path must be a folder, as every query will produce its own file.
         */
        'opcache_path' => env('LIGHTHOUSE_QUERY_CACHE_OPCACHE_PATH', base_path('bootstrap/cache')),

        /*
         * Allows using a specific cache store, uses the app's default if set to null.
         * Not relevant when using opcache mode.
         */
        'store' => env('LIGHTHOUSE_QUERY_CACHE_STORE', null),

        /*
         * Duration in seconds the query should remain cached, null means forever.
         * Not relevant when using opcache mode.
         */
        'ttl' => env('LIGHTHOUSE_QUERY_CACHE_TTL', 24 * 60 * 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Cache
    |--------------------------------------------------------------------------
    |
    | Caches the result of validating queries to boost performance on subsequent requests.
    |
    */

    'validation_cache' => [
        /*
         * Setting to true enables validation caching.
         */
        'enable' => env('LIGHTHOUSE_VALIDATION_CACHE_ENABLE', false),

        /*
         * Allows using a specific cache store, uses the app's default if set to null.
         */
        'store' => env('LIGHTHOUSE_VALIDATION_CACHE_STORE', null),

        /*
         * Duration in seconds the validation result should remain cached, null means forever.
         */
        'ttl' => env('LIGHTHOUSE_VALIDATION_CACHE_TTL', 24 * 60 * 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Parse source location
    |--------------------------------------------------------------------------
    */

    'parse_source_location' => true,

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | These are the default namespaces where Lighthouse looks for classes to
    | extend functionality of the schema. You may pass in either a string
    | or an array, they are tried in order and the first match is used.
    |
    */

    'namespaces' => [
        'models' => ['App', 'App\\Models'],
        'queries' => 'App\\GraphQL\\Queries',
        'mutations' => 'App\\GraphQL\\Mutations',
        'subscriptions' => 'App\\GraphQL\\Subscriptions',
        'types' => 'App\\GraphQL\\Types',
        'interfaces' => 'App\\GraphQL\\Interfaces',
        'unions' => 'App\\GraphQL\\Unions',
        'scalars' => 'App\\GraphQL\\Scalars',
        'directives' => 'App\\GraphQL\\Directives',
        'validators' => 'App\\GraphQL\\Validators',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Control how Lighthouse handles security related query validation.
    | Read more at https://webonyx.github.io/graphql-php/security/
    |
    */

    'security' => [
        'max_query_complexity' => GraphQL\Validator\Rules\QueryComplexity::DISABLED,
        'max_query_depth' => GraphQL\Validator\Rules\QueryDepth::DISABLED,
        'disable_introspection' => (bool) env('LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION', false)
            ? GraphQL\Validator\Rules\DisableIntrospection::ENABLED
            : GraphQL\Validator\Rules\DisableIntrospection::DISABLED,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_count' => 25,
        'max_count' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    */

    'debug' => env('LIGHTHOUSE_DEBUG', GraphQL\Error\DebugFlag::INCLUDE_DEBUG_MESSAGE | GraphQL\Error\DebugFlag::INCLUDE_TRACE),

    /*
    |--------------------------------------------------------------------------
    | Error Handlers
    |--------------------------------------------------------------------------
    */

    'error_handlers' => [
        Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
        Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
        Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
        Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Middleware
    |--------------------------------------------------------------------------
    */

    'field_middleware' => [
        Nuwave\Lighthouse\Schema\Directives\TrimDirective::class,
        Nuwave\Lighthouse\Schema\Directives\ConvertEmptyStringsToNullDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SanitizeDirective::class,
        Nuwave\Lighthouse\Validation\ValidateDirective::class,
        Nuwave\Lighthouse\Schema\Directives\TransformArgsDirective::class,
        Nuwave\Lighthouse\Schema\Directives\SpreadDirective::class,
        Nuwave\Lighthouse\Schema\Directives\RenameArgsDirective::class,
        Nuwave\Lighthouse\Schema\Directives\DropArgsDirective::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global ID
    |--------------------------------------------------------------------------
    */

    'global_id_field' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Persisted Queries
    |--------------------------------------------------------------------------
    */

    'persisted_queries' => true,

    /*
    |--------------------------------------------------------------------------
    | Transactional Mutations
    |--------------------------------------------------------------------------
    */

    'transactional_mutations' => true,

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment Protection
    |--------------------------------------------------------------------------
    */

    'force_fill' => true,

    /*
    |--------------------------------------------------------------------------
    | Batchload Relations
    |--------------------------------------------------------------------------
    */

    'batchload_relations' => true,

    /*
    |--------------------------------------------------------------------------
    | Shortcut Foreign Key Selection
    |--------------------------------------------------------------------------
    */

    'shortcut_foreign_key_selection' => false,

    /*
    |--------------------------------------------------------------------------
    | GraphQL Subscriptions
    |--------------------------------------------------------------------------
    */

    'subscriptions' => [
        'queue_broadcasts' => env('LIGHTHOUSE_QUEUE_BROADCASTS', true),
        'broadcasts_queue_name' => env('LIGHTHOUSE_BROADCASTS_QUEUE_NAME', null),
        'storage' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', 'redis'),
        'storage_ttl' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE_TTL', null),
        'encrypted_channels' => env('LIGHTHOUSE_SUBSCRIPTION_ENCRYPTED', false),
        'broadcaster' => env('LIGHTHOUSE_BROADCASTER', 'pusher'),
        'broadcasters' => [
            'log' => [
                'driver' => 'log',
            ],
            'echo' => [
                'driver' => 'echo',
                'connection' => env('LIGHTHOUSE_SUBSCRIPTION_REDIS_CONNECTION', 'default'),
                'routes' => Nuwave\Lighthouse\Subscriptions\SubscriptionRouter::class . '@echoRoutes',
            ],
            'pusher' => [
                'driver' => 'pusher',
                'connection' => 'pusher',
                'routes' => Nuwave\Lighthouse\Subscriptions\SubscriptionRouter::class . '@pusher',
            ],
            'reverb' => [
                'driver' => 'pusher',
                'connection' => 'reverb',
                'routes' => Nuwave\Lighthouse\Subscriptions\SubscriptionRouter::class . '@reverb',
            ],
        ],
        'exclude_empty' => env('LIGHTHOUSE_SUBSCRIPTION_EXCLUDE_EMPTY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defer
    |--------------------------------------------------------------------------
    */

    'defer' => [
        'max_nested_fields' => 0,
        'max_execution_ms' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Apollo Federation
    |--------------------------------------------------------------------------
    */

    'federation' => [
        'entities_resolver_namespace' => 'App\\GraphQL\\Entities',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    */

    'tracing' => [
        'driver' => Nuwave\Lighthouse\Tracing\ApolloTracing\ApolloTracing::class,
    ],
];
