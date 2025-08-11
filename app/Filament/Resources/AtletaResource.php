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
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('entrenador_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('gimnasio_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('foto')
                    ->maxLength(255)
                    ->default('atletas_fotos/default_photo.png'),
                Forms\Components\DatePicker::make('fecha_nacimiento'),
                Forms\Components\TextInput::make('genero')
                    ->required(),
                Forms\Components\TextInput::make('altura')
                    ->numeric(),
                Forms\Components\TextInput::make('peso')
                    ->numeric(),
                Forms\Components\TextInput::make('estilo_vida')
                    ->maxLength(255),
                Forms\Components\TextInput::make('lesiones_previas')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entrenador_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gimnasio_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('foto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_nacimiento')
                    ->date()
                    ->sortable(),
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
}
