<?php

namespace App\Filament\Resources\LeadNotes;

use App\Filament\Resources\LeadNotes\Pages\ManageLeadNotes;
use App\Models\LeadNote;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeadNoteResource extends Resource
{
    protected static ?string $model = LeadNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $modelLabel = 'ghi chú lead';

    protected static ?string $pluralModelLabel = 'Ghi chú chăm sóc';

    protected static string|UnitEnum|null $navigationGroup = 'Kinh doanh';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('lead_id')
                ->label('Khách hàng tiềm năng')
                ->relationship(
                    'lead',
                    'full_name',
                    modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->hasRole('sale')
                        ? $query->where('assigned_to', auth()->id())
                        : $query,
                )
                ->searchable()
                ->preload()
                ->required(),
            Textarea::make('body')->label('Nội dung ghi chú')->required()->maxLength(2000)->rows(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead.full_name')->label('Khách hàng')->searchable(),
                TextColumn::make('lead.phone')->label('Số điện thoại')->searchable(),
                TextColumn::make('body')->label('Ghi chú')->limit(100),
                TextColumn::make('author.name')->label('Người ghi'),
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lead', 'author']);
        $user = auth()->user();

        if ($user instanceof User && $user->hasRole('sale')) {
            $query->whereHas('lead', fn (Builder $leadQuery): Builder => $leadQuery->where('assigned_to', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return ['index' => ManageLeadNotes::route('/')];
    }
}
