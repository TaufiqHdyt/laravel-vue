<?php

use App\Http\Controllers\GeneralController;
use Illuminate\Support\Facades\Route;

use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

use Laravel\Jetstream\Http\Controllers\Inertia\CurrentUserController;
use Laravel\Jetstream\Http\Controllers\Inertia\OtherBrowserSessionsController;
use Laravel\Jetstream\Http\Controllers\Inertia\PrivacyPolicyController;
use Laravel\Jetstream\Http\Controllers\Inertia\ProfilePhotoController;
use Laravel\Jetstream\Http\Controllers\Inertia\TermsOfServiceController;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;
use Laravel\Jetstream\Jetstream;

use Laravel\Fortify\RoutePath;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [GeneralController::class, 'index']);

/**
 * Custom Auth Routes
 */
Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
    $enableViews = config('fortify.views', true);

    // Authentication...
    if ($enableViews) {
        Route::get(RoutePath::for('login', '/auth/login'), [AuthenticatedSessionController::class, 'create'])->middleware(['guest:'.config('fortify.guard')])->name('login');
    }

    $limiter = config('fortify.limiters.login');
    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    Route::post(RoutePath::for('login', '/auth/login'), [AuthenticatedSessionController::class, 'store'])->middleware(array_filter(['guest:'.config('fortify.guard'), $limiter ? 'throttle:'.$limiter : null]));

    Route::post(RoutePath::for('logout', '/auth/logout'), [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Password Reset...
    if (Features::enabled(Features::resetPasswords())) {
        if ($enableViews) {
            Route::get(RoutePath::for('password.request', '/auth/forgot-password'), [PasswordResetLinkController::class, 'create'])->middleware(['guest:'.config('fortify.guard')])->name('password.request');

            Route::get(RoutePath::for('password.reset', '/auth/reset-password/{token}'), [NewPasswordController::class, 'create'])->middleware(['guest:'.config('fortify.guard')])->name('password.reset');
        }

        Route::post(RoutePath::for('password.email', '/auth/forgot-password'), [PasswordResetLinkController::class, 'store'])->middleware(['guest:'.config('fortify.guard')])->name('password.email');

        Route::post(RoutePath::for('password.update', '/auth/reset-password'), [NewPasswordController::class, 'store'])->middleware(['guest:'.config('fortify.guard')])->name('password.update');
    }

    // Registration...
    if (Features::enabled(Features::registration())) {
        if ($enableViews) {
            Route::get(RoutePath::for('register', '/auth/register'), [RegisteredUserController::class, 'create'])->middleware(['guest:'.config('fortify.guard')])->name('register');
        }

        Route::post(RoutePath::for('register', '/auth/register'), [RegisteredUserController::class, 'store'])->middleware(['guest:'.config('fortify.guard')]);
    }

    // Email Verification...
    if (Features::enabled(Features::emailVerification())) {
        if ($enableViews) {
            Route::get(RoutePath::for('verification.notice', '/auth/email/verify'), [EmailVerificationPromptController::class, '__invoke'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])->name('verification.notice');
        }

        Route::get(RoutePath::for('verification.verify', '/auth/email/verify/{id}/{hash}'), [VerifyEmailController::class, '__invoke'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard'), 'signed', 'throttle:'.$verificationLimiter])->name('verification.verify');

        Route::post(RoutePath::for('verification.send', '/auth/email/verification-notification'), [EmailVerificationNotificationController::class, 'store'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard'), 'throttle:'.$verificationLimiter])->name('verification.send');
    }

    // Passwords...
    if (Features::enabled(Features::updatePasswords())) {
        Route::put(RoutePath::for('user-password.update', '/dashboard/user/password'), [PasswordController::class, 'update'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])->name('user-password.update');
    }

    // Password Confirmation...
    if ($enableViews) {
        Route::get(RoutePath::for('password.confirm', '/dashboard/user/confirm-password'), [ConfirmablePasswordController::class, 'show'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')]);
    }

    Route::get(RoutePath::for('password.confirmation', '/dashboard/user/confirmed-password-status'), [ConfirmedPasswordStatusController::class, 'show'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])->name('password.confirmation');

    Route::post(RoutePath::for('password.confirm', '/dashboard/user/confirm-password'), [ConfirmablePasswordController::class, 'store'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])->name('password.confirm');


    // Profile Information...
    if (Features::enabled(Features::updateProfileInformation())) {
        Route::put(RoutePath::for('user-profile-information.update', '/dashboard/user/profile-information'), [ProfileInformationController::class, 'update'])->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])->name('user-profile-information.update');
    }
});

Route::group(['middleware' => config('jetstream.middleware', ['web'])], function () {
    if (Jetstream::hasTermsAndPrivacyPolicyFeature()) {
        Route::get('/terms-of-service', [TermsOfServiceController::class, 'show'])->name('terms.show');
        Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');
    }

    $authMiddleware = config('jetstream.guard') ? 'auth:'.config('jetstream.guard') : 'auth';

    $authSessionMiddleware = config('jetstream.auth_session', false) ? config('jetstream.auth_session') : null;

    Route::group(['middleware' => array_values(array_filter([$authMiddleware, $authSessionMiddleware]))], function () {
        // User & Profile...
        Route::get('/dashboard/user/profile', [UserProfileController::class, 'show'])->name('profile.show');

        Route::delete('/dashboard/user/other-browser-sessions', [OtherBrowserSessionsController::class, 'destroy'])->name('other-browser-sessions.destroy');

        Route::delete('/dashboard/user/profile-photo', [ProfilePhotoController::class, 'destroy'])->name('current-user-photo.destroy');

        if (Jetstream::hasAccountDeletionFeatures()) {
            Route::delete('/dashboard/user', [CurrentUserController::class, 'destroy'])->name('current-user.destroy');
        }
    });
});


/**
 * Dashboard Routes
 */
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/dashboard', [GeneralController::class, 'dashboard'])->name('dashboard');
});
