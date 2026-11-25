<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Services\ArabicText;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function getModelLabel(): string
    {
        return __('admin.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.products');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('removed_at'))
            ->columns([
                ImageColumn::make('cover_image')->label(''),
                TextColumn::make('name')->label(__('admin.name'))->wrap()->searchable(query: function (Builder $query, string $search) {
                    $query->where('search_text', 'like', '%'.addcslashes(ArabicText::normalize($search), '%_\\').'%');
                }),
                TextColumn::make('cost_price')->label(__('admin.unit_cost'))->money('ILS')->sortable(),
                TextColumn::make('selling_price')->label(__('admin.selling_price'))->money('ILS')->sortable(),
                TextColumn::make('price_override')->label(__('admin.price_override'))->money('ILS')->placeholder('—'),
                TextColumn::make('total_quantity')->label(__('admin.stock'))->sortable(),
                ToggleColumn::make('hidden')->label(__('admin.hidden')),
                ToggleColumn::make('featured')->label(__('admin.featured')),
            ])
            ->filters([
                TernaryFilter::make('featured')->label(__('admin.featured')),
                TernaryFilter::make('hidden')->label(__('admin.hidden')),
                TernaryFilter::make('mart_active')->label(__('admin.mart_active')),
            ])
            ->recordActions([
                Action::make('price')->label(__('admin.set_price'))->icon('heroicon-o-pencil')
                    ->schema(fn (Product $record) => [
                        TextInput::make('price_override')->label(__('admin.price_override'))
                            ->helperText(__('admin.price_help', ['cost' => $record->cost_price]))
                            ->numeric()->minValue((float) $record->cost_price)->maxValue(99999999)
                            ->validationMessages(['min' => __('admin.errors.override_below_cost', ['cost' => $record->cost_price])]),
                    ])
                    ->fillForm(fn (Product $record) => ['price_override' => $record->price_override])
                    ->action(fn (Product $record, array $data) => $record->update([
                        'price_override' => filled($data['price_override']) ? $data['price_override'] : null,
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListProducts::route('/')];
    }
}
