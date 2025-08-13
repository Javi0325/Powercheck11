<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrenadorResource\Pages;
use App\Filament\Resources\EntrenadorResource\RelationManagers;
use App\Models\Entrenador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EntrenadorResource extends Resource
{
    protected static ?string $model = Entrenador::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_name')
                    ->label('Nombres')
                    ->required(),
                Forms\Components\TextInput::make('user_apellidos')
                    ->label('Apellidos')
                    ->required(),
                Forms\Components\TextInput::make('user_celular')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('user_telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->required()
                    ->maxLength(15),
                Forms\Components\Select::make('gimnasio_id')
                    ->label('Gimnasio')
                    ->relationship('gimnasio', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\FileUpload::make('foto')
                    ->image() // habilita preview y limita a imágenes
                    ->directory('gym_logos') // carpeta dentro del disco
                    ->disk('public') // usa el disco "public"
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->maxSize(2048) // 2 MB
                    ->helperText('Sube una foto de perfil (PNG/JPG, máx. 2 MB)')
                    ->openable()     // botón para abrir
                    ->downloadable(), // botón para descargar
                Forms\Components\TextInput::make('especialidad')
                    ->maxLength(255),
                Forms\Components\Textarea::make('experiencia')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gimnasio_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('foto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('especialidad')
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
            'index' => Pages\ListEntrenadors::route('/'),
            'create' => Pages\CreateEntrenador::route('/create'),
            'edit' => Pages\EditEntrenador::route('/{record}/edit'),
        ];
    }
}
