<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AtletaResource\Pages;
use App\Filament\Resources\AtletaResource\RelationManagers;
use App\Models\Atleta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AtletaResource extends Resource
{
    protected static ?string $model = Atleta::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Información del atleta')
                    ->columns(2)
                    ->relationship('user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->required(),
                        Forms\Components\TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('celular')
                            ->label('celular')
                            ->tel()
                            ->required()
                            ->maxLength(15),
                    ]),
                Forms\Components\FileUpload::make('foto')
                    ->image() // habilita preview y limita a imágenes
                    ->directory('atletas') // carpeta dentro del disco
                    ->disk('public') // usa el disco "public"
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->maxSize(2048) // 2 MB
                    ->helperText('Sube una foto de perfil (PNG/JPG, máx. 2 MB)')
                    ->openable()     // botón para abrir
                    ->downloadable(), // botón para descargar
                Forms\Components\DatePicker::make('fecha_nacimiento')
                    ->label('Fecha de Nacimiento'),
                Forms\Components\Select::make('genero')
                    ->label('Género')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Femenino'  => 'Femenino',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('altura')
                    ->label('Altura (cm)')
                    ->minValue(0)
                    ->maxValue(300)
                    ->step(0.01)
                    ->prefix('cm')
                    ->numeric(),
                Forms\Components\TextInput::make('peso')
                    ->label('Peso (kg)')
                    ->minValue(0)
                    ->maxValue(500)
                    ->step(0.01)
                    ->prefix('kg')
                    ->numeric(),
                Forms\Components\TextInput::make('estilo_vida')
                    ->maxLength(255),
                Forms\Components\TextInput::make('lesiones_previas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Atleta')
                    ->getStateUsing(fn($record) => trim(($record->user?->name ?? '') . ' ' . ($record->user?->apellidos ?? '')))
                    ->searchable(query: function ($query, string $search) {
                        // Busca por nombre o apellidos
                        $query->where(
                            fn($q) =>
                            $q->where('user_name', 'like', "%{$search}%")
                                ->orWhere('user_apellidos', 'like', "%{$search}%")
                        );
                    }),
                Tables\Columns\TextColumn::make('edad')
                    ->label('Edad')
                    ->getStateUsing(function ($record) {
                        return $record->fecha_nacimiento
                            ? Carbon::parse($record->fecha_nacimiento)->age
                            : null;
                    }),
                Tables\Columns\TextColumn::make('user.celular')
                    ->label('Correo Electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('genero'),
                Tables\Columns\TextColumn::make('altura')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('peso')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estilo_vida')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lesiones_previas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAtletas::route('/'),
            'create' => Pages\CreateAtleta::route('/create'),
            'edit' => Pages\EditAtleta::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        // Ajusta los nombres de roles a los tuyos
        if ($user->hasAnyRole(['super_admin'])) {
            return $query; // ve todo
        }

        // Si usas tabla Entrenador relacionada a User:
        $entrenadorId = optional($user->entrenador)->id;

        // Si el User ES el entrenador (sin tabla Entrenador), usa:
        // $entrenadorId = $user->id;

        return $query->where('entrenador_id', $entrenadorId);
    }
}
