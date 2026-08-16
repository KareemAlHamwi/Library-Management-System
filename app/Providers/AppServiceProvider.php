<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\Author\AuthorRepositoryInterface;
use App\Repositories\Contracts\Book\BookRepositoryInterface;
use App\Repositories\Contracts\Category\CategoryRepositoryInterface;
use App\Repositories\Contracts\Favorite\FavoriteRepositoryInterface;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Repositories\Eloquent\Author\AuthorRepository;
use App\Repositories\Eloquent\Book\BookRepository;
use App\Repositories\Eloquent\Category\CategoryRepository;
use App\Repositories\Eloquent\Favorite\FavoriteRepository;
use App\Repositories\Eloquent\User\UserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            BookRepositoryInterface::class,
            BookRepository::class
        );
        $this->app->bind(
            AuthorRepositoryInterface::class,
            AuthorRepository::class,
        );
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );
        $this->app->bind(
            FavoriteRepositoryInterface::class,
            FavoriteRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->max(50)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Schema::defaultStringLength(191);

        Gate::define('is-admin', fn (User $user) => $user->role === UserRole::ADMIN);
        Gate::define('is-supervisor', fn (User $user) => $user->role === UserRole::SUPERVISOR);
        Gate::define('is-member', fn (User $user) => $user->role === UserRole::MEMBER);
    }
}
