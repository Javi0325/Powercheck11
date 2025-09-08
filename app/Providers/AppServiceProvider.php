<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// 👇 importa las clases correctas de Filament
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction as FilamentDeleteAction;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aplica estilo de botón a TODAS las actions (en vez de link)
        if (class_exists(FilamentAction::class)) {
            FilamentAction::configureUsing(function (FilamentAction $action) {
                $action->button(); // ahora se renderiza como botón
            });
        }

        // Delete como botón rojo con ícono, en todo el panel
        if (class_exists(FilamentDeleteAction::class)) {
            FilamentDeleteAction::configureUsing(function (FilamentDeleteAction $action) {
                $action
                    ->button()
                    ->color('danger')
                    ->icon('heroicon-m-trash'); // opcional
            });
        }
    }
}
