<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Game\GameRepository;
use App\Repositories\Game\GameRepositoryInterface;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Booking\BookingRepositoryInterface;
use App\Repositories\Venue\VenueRepository;
use App\Repositories\Venue\VenueRepositoryInterface;
use App\Repositories\GameParticipant\GameParticipantRepository;
use App\Repositories\GameParticipant\GameParticipantRepositoryInterface;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\CourtPrice\CourtPriceRepository;
use App\Repositories\CourtPrice\CourtPriceRepositoryInterface;
use App\Repositories\BookedCourt\BookedCourtRepository;
use App\Repositories\BookedCourt\BookedCourtRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\BankAccount\BankAccountRepository;
use App\Repositories\BankAccount\BankAccountRepositoryInterface;
use App\Services\Game\GameService;
use App\Services\Booking\BookingService;
use App\Services\Booking\BookingServiceInterface;
use App\Services\Venue\VenueService;
use App\Services\Venue\VenueServiceInterface;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\BankAccount\BankAccountService;
use App\Services\BankAccount\BankAccountServiceInterface;
use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\Venue;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameRepository::class, function ($app) {
            return new GameRepository(
                new Game(),
                new GameParticipant(),
                new Venue(),
                new User()
            );
        });

        $this->app->singleton(GameService::class, function ($app) {
            return new GameService(
                $app->make(GameRepository::class)
            );
        });

        // Đăng ký Repositories
        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );

        $this->app->bind(
            VenueRepositoryInterface::class,
            VenueRepository::class
        );

        $this->app->bind(
            GameRepositoryInterface::class,
            GameRepository::class
        );

        $this->app->bind(
            GameParticipantRepositoryInterface::class,
            GameParticipantRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        $this->app->bind(
            CourtPriceRepositoryInterface::class,
            CourtPriceRepository::class
        );

        $this->app->bind(
            BookedCourtRepositoryInterface::class,
            BookedCourtRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            BankAccountRepositoryInterface::class,
            BankAccountRepository::class
        );

        // Đăng ký Services
        $this->app->bind(
            BookingServiceInterface::class,
            BookingService::class
        );

        $this->app->bind(
            VenueServiceInterface::class,
            VenueService::class
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );

        $this->app->bind(
            BankAccountServiceInterface::class,
            BankAccountService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
