<?php

namespace App\Filament\Resources\VehicleOnRoadFees;

use App\Filament\Resources\VehicleOnRoadFees\Pages\ManageVehicleOnRoadFees;
use App\Models\VehicleLine;
use App\Models\VehicleOnRoadFee;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class VehicleOnRoadFeeResource extends Resource
{
    protected static ?string $model = VehicleOnRoadFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $modelLabel = 'bảng phí lăn bánh';

    protected static ?string $pluralModelLabel = 'Phí lăn bánh theo phiên bản';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dòng xe và phạm vi áp dụng')
                ->description('Mỗi bản ghi cấu hình một phiên bản tại một khu vực. Dữ liệu Excel ban đầu được tạo ở trạng thái nháp để đại lý rà soát trước khi công khai.')
                ->schema([
                    Select::make('vehicle_line_id')
                        ->label('Dòng xe')
                        ->options(fn (): array => VehicleLine::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->afterStateHydrated(function (Select $component, ?VehicleOnRoadFee $record): void {
                            if ($record !== null) {
                                $component->state($record->variant?->vehicle?->vehicle_line_id);
                            }
                        })
                        ->afterStateUpdated(fn (Set $set): mixed => $set('variant_id', null))
                        ->dehydrated(false)
                        ->required(),
                    Select::make('variant_id')
                        ->label('Phiên bản xe')
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('region', $get('region')),
                        )
                        ->relationship(
                            'variant',
                            'name',
                            modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                ->whereHas('vehicle', fn (Builder $vehicleQuery): Builder => $vehicleQuery
                                    ->where('vehicle_line_id', $get('vehicle_line_id') ?? 0)),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('vehicle_line_id')))
                        ->required(),
                    Select::make('region')
                        ->label('Khu vực')
                        ->options(['kon-tum' => 'Kon Tum', 'gia-lai' => 'Gia Lai', 'quang-ngai' => 'Quảng Ngãi'])
                        ->default('kon-tum')
                        ->live()
                        ->required(),
                ])->columns(3),
            Section::make('Chi phí đăng ký và bảo hiểm')
                ->schema([
                    TextInput::make('registration_rate_percent')->label('Lệ phí trước bạ (%)')->numeric()->minValue(0)->maxValue(100)->step('0.01')->required()
                        ->helperText('Xe con: 10%; bán tải: 6%. Tính trên giá niêm yết phiên bản.'),
                    TextInput::make('registration_fee')->label('Điều chỉnh trước bạ cố định (VNĐ)')->numeric()->minValue(0)->default(0)->required(),
                    TextInput::make('body_insurance_rate_percent')->label('Bảo hiểm thân vỏ (%)')->numeric()->minValue(0)->maxValue(100)->step('0.01')->nullable(),
                    TextInput::make('compulsory_insurance_fee')->label('Bảo hiểm TNDS bắt buộc (VNĐ)')->numeric()->minValue(0)->nullable(),
                ])->columns(3),
            Section::make('Chi phí phát sinh')
                ->description('Các khoản này lấy từ sheet “Khuyến mãi & Chi phí” trong bảng tính được cung cấp; có thể sửa riêng cho từng phiên bản và khu vực.')
                ->schema([
                    TextInput::make('license_plate_fee')->label('Phí đăng ký biển số (VNĐ)')->numeric()->minValue(0)->nullable(),
                    TextInput::make('inspection_fee')->label('Phí đăng kiểm (VNĐ)')->numeric()->minValue(0)->nullable(),
                    TextInput::make('road_use_fee')->label('Phí bảo trì đường bộ (VNĐ)')->numeric()->minValue(0)->nullable(),
                    TextInput::make('service_fee')->label('Phí dịch vụ (VNĐ)')->numeric()->minValue(0)->default(0)->required(),
                    TextInput::make('other_fee')->label('Chi phí khác (VNĐ)')->numeric()->minValue(0)->default(0)->required(),
                    Textarea::make('source_note')->label('Nguồn và ghi chú rà soát')->rows(3)->columnSpanFull(),
                    Toggle::make('is_enabled')->label('Cho phép hiển thị và tính dự toán')->default(false)
                        ->helperText('Chỉ bật sau khi xác nhận các khoản phí cho đúng khu vực và phiên bản.'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variant.vehicle.line.name')->label('Dòng xe')->searchable()->sortable(),
                TextColumn::make('variant.vehicle.name')->label('Mẫu xe')->searchable(),
                TextColumn::make('variant.name')->label('Phiên bản')->searchable()->sortable(),
                TextColumn::make('variant.list_price')->label('Giá niêm yết')->formatStateUsing(fn ($state): string => $state === null ? 'Thiếu giá' : number_format((int) $state, 0, ',', '.').' ₫'),
                TextColumn::make('region')->label('Khu vực')->formatStateUsing(fn (string $state): string => match ($state) {
                    'kon-tum' => 'Kon Tum', 'gia-lai' => 'Gia Lai', 'quang-ngai' => 'Quảng Ngãi', default => $state,
                }),
                TextColumn::make('registration_rate_percent')->label('Trước bạ')->formatStateUsing(fn ($state): string => $state === null ? 'Thiếu dữ liệu' : number_format((float) $state, 2, ',', '.').'%'),
                IconColumn::make('is_enabled')->label('Công khai')->boolean(),
            ])
            ->filters([
                SelectFilter::make('region')->label('Khu vực')->options(['kon-tum' => 'Kon Tum', 'gia-lai' => 'Gia Lai', 'quang-ngai' => 'Quảng Ngãi']),
                SelectFilter::make('is_enabled')->label('Trạng thái')->options([1 => 'Đang công khai', 0 => 'Bản nháp']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicleOnRoadFees::route('/')];
    }
}
