<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Policy;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\VendorDocument;
use App\Policies\PermissionPolicy;
use App\Policies\PolicyPolicy;
use App\Policies\RolePolicy;
use App\Policies\SurveyPolicy;
use App\Policies\SurveyTemplatePolicy;
use App\Policies\TaxonomyPolicy;
use App\Policies\VendorDocumentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected string $redirectTo = '/app/login';

    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Taxonomy::class => TaxonomyPolicy::class,
        Policy::class => PolicyPolicy::class,
        Survey::class => SurveyPolicy::class,
        SurveyTemplate::class => SurveyTemplatePolicy::class,
        VendorDocument::class => VendorDocumentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
