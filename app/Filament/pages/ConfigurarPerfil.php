<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

use App\Models\Entrenador;
use App\Models\Atleta;
use App\Models\Gimnasio; // si usas el modelo directamente en los options de Select

class ConfigurarPerfil extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurar perfil';
    protected static ?string $slug = 'configurar-perfil';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.configurar-perfil';

    public ?array $data = [
        'user' => [],
        'entrenador' => [],
        'atleta' => [],
    ];

    public function getTitle(): string
    {
        return 'Configurar perfil';
    }

    /** ----------------------------------------------------------------
     *  Formularios dinámicos según rol
     *  ---------------------------------------------------------------- */
    protected function getForms(): array
    {
        $forms = [
            'formUser' => $this->makeForm()
                ->schema($this->userSchema())
                ->statePath('data.user'),
        ];

        $user = Auth::user();
        if ($user?->hasRole('entrenador')) {
            $forms['formEntrenador'] = $this->makeForm()
                ->schema($this->entrenadorSchema())
                ->statePath('data.entrenador');
        }

        if ($user?->hasRole('atleta')) {
            $forms['formAtleta'] = $this->makeForm()
                ->schema($this->atletaSchema())
                ->statePath('data.atleta');
        }

        return $forms;
    }

    /** ----------------------------------------------------------------
     *  Schemas
     *  ---------------------------------------------------------------- */
    protected function userSchema(): array
    {
        return [
            Forms\Components\Section::make('Datos de usuario')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('apellidos')
                        ->label('Apellidos')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('celular')
                        ->label('Celular')
                        ->tel()
                        ->maxLength(15),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),
        ];
    }

    protected function entrenadorSchema(): array
    {
        return [
            Forms\Components\Section::make('Datos de entrenador')
                ->columns(2)
                ->schema([
                    // Ajusta relationship si tu modelo Entrenador tiene ->gimnasio()
                    Forms\Components\Select::make('gimnasio_id')
                        ->label('Gimnasio')
                        ->options(fn() => Gimnasio::query()->pluck('nombre', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\TextInput::make('especialidad')
                        ->label('Especialidad')
                        ->maxLength(255),

                    Forms\Components\FileUpload::make('foto')
                        ->label('Foto')
                        ->directory('entrenadores_fotos')
                        ->image()
                        ->default('entrenadores_fotos/default_photo.png')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('experiencia')
                        ->label('Experiencia')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function atletaSchema(): array
    {
        return [
            Forms\Components\Section::make('Datos de atleta')
                ->columns(2)
                ->schema([
                    // entrenador_id: si quieres limitar a su propio entrenador:
                    Forms\Components\Select::make('entrenador_id')
                        ->label('Entrenador')
                        ->relationship('entrenador', 'id') // muestra id por defecto… mejor usa display personalizado:
                        ->options(fn() => Entrenador::query()
                            ->with('user')
                            ->get()
                            ->pluck(fn($e) => $e->user?->name ?? "Entrenador #{$e->id}", 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('gimnasio_id')
                        ->label('Gimnasio')
                        ->relationship('gimnasio', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\FileUpload::make('foto')
                        ->label('Foto')
                        ->directory('atletas_fotos')
                        ->image()
                        ->default('atletas_fotos/default_photo.png')
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->closeOnDateSelection()
                        ->nullable(),

                    Forms\Components\Select::make('genero')
                        ->label('Género')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('altura')
                        ->label('Altura (cm o m)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(300)
                        ->nullable(),

                    Forms\Components\TextInput::make('peso')
                        ->label('Peso (kg)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(500)
                        ->nullable(),

                    Forms\Components\TextInput::make('estilo_vida')
                        ->label('Estilo de vida')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\TextInput::make('lesiones_previas')
                        ->label('Lesiones previas')
                        ->maxLength(255)
                        ->nullable(),
                ]),
        ];
    }

    /** ----------------------------------------------------------------
     *  Ciclo de vida
     *  ---------------------------------------------------------------- */
    public function mount(): void
    {
        $user = Auth::user();

        // USER
        $this->getForm('formUser')->fill([
            'name' => $user->name,
            'apellidos' => $user->apellidos,
            'celular' => $user->celular,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ]);

        // ENTRENADOR
        if ($user->hasRole('entrenador')) {
            $entrenador = Entrenador::firstOrCreate(
                ['user_id' => $user->id],
                ['gimnasio_id' => null]
            );

            $this->getForm('formEntrenador')->fill([
                'gimnasio_id'  => $entrenador->gimnasio_id,
                'foto'         => $entrenador->foto,
                'especialidad' => $entrenador->especialidad,
                'experiencia'  => $entrenador->experiencia,
            ]);
        }

        // ATLETA
        if ($user->hasRole('atleta')) {
            $atleta = Atleta::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'entrenador_id' => null,
                    'gimnasio_id'   => null,
                    'genero'        => null,
                ],
            );

            $this->getForm('formAtleta')->fill([
                'entrenador_id'    => $atleta->entrenador_id,
                'gimnasio_id'      => $atleta->gimnasio_id,
                'foto'             => $atleta->foto,
                'fecha_nacimiento' => $atleta->fecha_nacimiento,
                'genero'           => $atleta->genero,
                'altura'           => $atleta->altura,
                'peso'             => $atleta->peso,
                'estilo_vida'      => $atleta->estilo_vida,
                'lesiones_previas' => $atleta->lesiones_previas,
            ]);
        }
    }

    /** ----------------------------------------------------------------
     *  Acciones (Guardar)
     *  ---------------------------------------------------------------- */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->action('saveAll'),
        ];
    }

    public function saveAll(): void
    {
        $user = Auth::user();

        // --- Validación de User
        $this->validate([
            'data.user.email'   => 'required|email|max:255|unique:users,email,' . $user->id,
            'data.user.name'    => 'required|string|max:255',
            'data.user.celular' => 'nullable|string|max:15',
        ]);

        // Guardar USER
        $userData = $this->getForm('formUser')->getState();
        $user->fill([
            'name' => $userData['name'] ?? $user->name,
            'apellidos' => $userData['apellidos'] ?? null,
            'celular' => $userData['celular'] ?? null,
            'email' => $userData['email'] ?? $user->email,
            'profile_photo_path' => $userData['profile_photo_path'] ?? $user->profile_photo_path,
        ])->save();

        // Guardar ENTRENADOR (si corresponde)
        if ($user->hasRole('entrenador')) {
            $entrenador = Entrenador::firstOrCreate(['user_id' => $user->id]);
            $entData = $this->getForm('formEntrenador')->getState();

            $entrenador->fill([
                'gimnasio_id'  => $entData['gimnasio_id'] ?? null,
                'foto'         => $entData['foto'] ?? $entrenador->foto,
                'especialidad' => $entData['especialidad'] ?? null,
                'experiencia'  => $entData['experiencia'] ?? null,
            ])->save();
        }

        // Guardar ATLETA (si corresponde)
        if ($user->hasRole('atleta')) {
            $atleta = Atleta::firstOrCreate(['user_id' => $user->id]);
            $atlData = $this->getForm('formAtleta')->getState();

            $this->validate([
                'data.atleta.genero' => 'nullable|in:Masculino,Femenino',
                'data.atleta.altura' => 'nullable|numeric|min:0|max:300',
                'data.atleta.peso'   => 'nullable|numeric|min:0|max:500',
            ]);

            $atleta->fill([
                'entrenador_id'    => $atlData['entrenador_id'] ?? null,
                'gimnasio_id'      => $atlData['gimnasio_id'] ?? null,
                'foto'             => $atlData['foto'] ?? $atleta->foto,
                'fecha_nacimiento' => $atlData['fecha_nacimiento'] ?? null,
                'genero'           => $atlData['genero'] ?? null,
                'altura'           => $atlData['altura'] ?? null,
                'peso'             => $atlData['peso'] ?? null,
                'estilo_vida'      => $atlData['estilo_vida'] ?? null,
                'lesiones_previas' => $atlData['lesiones_previas'] ?? null,
            ])->save();
        }

        Notification::make()
            ->title('Perfil actualizado')
            ->success()
            ->send();
    }
}
