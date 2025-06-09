<?php

namespace Config;

use App\Filters\AuthFilter;
use App\Filters\AdminFilter;
use App\Filters\TechnicianFilter;
use App\Filters\CustomerFilter;
use App\Filters\GuestFilter;
use App\Filters\PermissionFilter;
use App\Filters\RoleFilter;
use App\Filters\SecurityFilter;
use App\Filters\RateLimitFilter;
use App\Filters\CorsFilter;
use App\Filters\ApiAuthFilter;
use App\Filters\ApiRateLimitFilter;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        // CodeIgniter Built-in Filters
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Custom Authentication & Authorization Filters
        'auth'          => AuthFilter::class,
        'guest'         => GuestFilter::class,
        'admin'         => AdminFilter::class,
        'technician'    => TechnicianFilter::class,
        'customer'      => CustomerFilter::class,

        // Permission-based Filters
        'permission'    => PermissionFilter::class,
        'role'          => RoleFilter::class,

        // Security Filters
        'security'      => SecurityFilter::class,
        'rate_limit'    => RateLimitFilter::class,
        'custom_cors'   => CorsFilter::class,

        // API Filters
        'api_auth'      => ApiAuthFilter::class,
        'api_rate_limit' => ApiRateLimitFilter::class,
    ];

    /**
     * List of special required filters.
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            'honeypot',
            'csrf' => [
                'except' => [
                    'api/*',
                    'health',
                    '.well-known/*',
                    'webhook/*'
                ]
            ],
            'invalidchars',
            'rate_limit' => [
                'except' => [
                    'health',
                    'api/v1/auth/login',
                    'api/v1/auth/register'
                ]
            ]
        ],
        'after' => [
            'toolbar' => [
                'except' => [
                    'api/*',
                    'ajax/*'
                ]
            ],
            'secureheaders'
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * @var array<string, list<string>>
     */
    public array $methods = [
        'POST' => ['csrf'],
        'PUT'  => ['csrf'],
        'PATCH' => ['csrf'],
        'DELETE' => ['csrf'],
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // Authentication filters
        'auth' => [
            'before' => [
                'dashboard',
                'dashboard/*',
                'profile',
                'profile/*',
                'admin',
                'admin/*',
                'work/*',
                'customer/*',
                'technician',
                'technician/*',
                'notifications/*',
                'search',
                'files/*',
                'api/v1/user/*',
                'api/v1/dashboard/*'
            ]
        ],

        'guest' => [
            'before' => [
                'auth/signin',
                'auth/signup',
                'auth/processLogin',
                'auth/processRegister',
                'auth/forgot-password',
                'auth/processForgotPassword',
                'auth/reset-password/*',
                'auth/processResetPassword',
                'login',
                'register',
                'forgot-password'
            ]
        ],

        // Role-based filters
        'admin' => [
            'before' => [
                'admin/*',
                'api/v1/admin/*'
            ]
        ],

        'technician' => [
            'before' => [
                'technician/*'
            ]
        ],

        'customer' => [
            'before' => [
                'customer/*',
                'api/v1/customer/*'
            ]
        ],

        // Permission-based filters for specific actions
        'permission:users.manage' => [
            'before' => [
                'admin/users',
                'admin/users/*',
                'api/v1/admin/users/*'
            ]
        ],

        'permission:roles.manage' => [
            'before' => [
                'admin/roles',
                'admin/roles/*'
            ]
        ],

        'permission:settings.manage' => [
            'before' => [
                'admin/settings',
                'admin/settings/*'
            ]
        ],

        'permission:reports.read' => [
            'before' => [
                'admin/reports',
                'admin/reports/*',
                'work/reports',
                'work/reports/*'
            ]
        ],

        'permission:audit.read' => [
            'before' => [
                'admin/audit',
                'admin/audit/*'
            ]
        ],

        'permission:orders.manage.all' => [
            'before' => [
                'work/orders',
                'work/orders/*'
            ]
        ],

        // Security filters
        'security' => [
            'before' => [
                'auth/processLogin',
                'auth/processRegister',
                'auth/processForgotPassword',
                'auth/processResetPassword',
                'profile/update',
                'profile/updatePassword',
                'admin/users/store',
                'admin/users/update/*',
                'admin/settings/update',
                'api/*'
            ]
        ],

        // API-specific filters
        'api_auth' => [
            'before' => [
                'api/v1/user/*',
                'api/v1/admin/*',
                'api/v1/work/*',
                'api/v1/customer/*',
                'api/v1/dashboard/*'
            ]
        ],

        'api_rate_limit' => [
            'before' => [
                'api/*'
            ]
        ],

        'custom_cors' => [
            'before' => [
                'api/*'
            ]
        ]
    ];
}