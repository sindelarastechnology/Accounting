<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HelpPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Panduan & Help';

    protected static ?string $title = 'Panduan Penggunaan';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.help-page';
}
