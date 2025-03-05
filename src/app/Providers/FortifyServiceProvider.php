<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\CustomVerifyEmailResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Http\Responses\VerifyEmailResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
        app()->bind(VerifyEmailResponse::class, CustomVerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)
                ->where('is_active', true)
                ->first();
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
            return null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify_email');
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->email.$request->ip());
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot_password');
        });

        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset_password', ['request' => $request]);
        });

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Password::defaults(function () {
            return Password::min(8);
        });
    }
}
