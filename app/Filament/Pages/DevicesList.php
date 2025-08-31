<?php
namespace App\Filament\Pages;

use App\Models\Device;
use App\Models\DeviceSession;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class DevicesList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Dispositivos';
    protected static ?string $title = 'Dispositivos';
    protected static ?string $slug = 'devices-list'; // URL: /powerCheck/devices-list
    protected static string $view = 'filament.pages.devices-list'; // usa layout base de Filament

    public function table(Table $table): Table
    {
        return $table
            ->query(Device::query()->orderByDesc('last_seen'))
            ->poll('10s') // auto-refresh
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('ip')->label('IP')->copyable()->sortable(),
                TextColumn::make('last_seen')->label('Último ping')->since()->sortable(),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn (Device $r) => $r->is_available ? 'Disponible' : 'Offline')
                    ->colors([
                        'success' => fn (Device $r) => $r->is_available,
                        'gray' => fn (Device $r) => ! $r->is_available,
                    ]),
                BadgeColumn::make('ocupado')
                    ->label('Ocupación')
                    ->formatStateUsing(fn (Device $r) => optional($r->activeSession()->first())->athlete_id ? 'Ocupado' : 'Libre')
                    ->colors([
                        'primary' => fn (Device $r) => optional($r->activeSession()->first())->athlete_id !== null,
                        'info' => fn (Device $r) => optional($r->activeSession()->first())->athlete_id === null,
                    ]),
            ])
            ->actions([
                Action::make('conectar')
                    ->label('Conectar')->icon('heroicon-o-link')
                    ->visible(fn (Device $r) => $r->is_available && ! optional($r->activeSession()->first())->athlete_id)
                    ->action(function (Device $r) {
                        if (optional($r->activeSession()->first())->athlete_id) {
                            Notification::make()->title('Este dispositivo ya está ocupado.')->danger()->send();
                            return;
                        }
                        DeviceSession::create([
                            'device_id'  => $r->id,
                            'athlete_id' => auth()->id(),
                            'status'     => 'active',
                            'started_at' => Carbon::now(),
                            'expires_at' => Carbon::now()->addMinutes(30),
                        ]);
                        Notification::make()->title('¡Dispositivo conectado!')->success()->send();
                    }),
                Action::make('liberar')
                    ->label('Liberar')->icon('heroicon-o-no-symbol')->color('gray')
                    ->visible(function (Device $r) {
                        $active = $r->activeSession()->first();
                        return $active && $active->athlete_id === auth()->id();
                    })
                    ->action(function (Device $r) {
                        $active = $r->activeSession()->first();
                        if ($active && $active->athlete_id === auth()->id()) {
                            $active->update(['status' => 'ended', 'ended_at' => now()]);
                            Notification::make()->title('Dispositivo liberado.')->success()->send();
                        } else {
                            Notification::make()->title('No tienes este dispositivo asignado.')->danger()->send();
                        }
                    }),
            ]);
    }
}
