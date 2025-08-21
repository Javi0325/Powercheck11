<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class InfoExtra extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static string $view = 'filament.pages.info-extra';
    protected static ?string $navigationLabel = 'Info Extra';
    protected static ?string $slug = 'info-extra';


}