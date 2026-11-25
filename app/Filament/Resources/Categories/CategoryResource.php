<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getModelLabel(): string
    {
        return __('admin.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->paginationPageOptions([25, 50, 150])
            ->columns([
                TextColumn::make('name')->label(__('admin.mart_name'))->wrap()->searchable()->sortable(),
                TextColumn::make('default_name')->label(__('admin.default_name'))->state(fn (Category $record) => $record->defaultLabel()),
                TextInputColumn::make('name_ar')->label(__('admin.name_ar'))->rules(['nullable', 'string', 'max:100'])->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCategories::route('/')];
    }
}
