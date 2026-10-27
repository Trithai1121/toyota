<?php

namespace App\Filament\Exports;

use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('Mã lead'),
            ExportColumn::make('full_name')->label('Họ tên'),
            ExportColumn::make('phone')->label('Số điện thoại'),
            ExportColumn::make('request_type')->label('Nhu cầu')->formatStateUsing(fn (string $state): string => match ($state) {
                'test_drive' => 'Lái thử',
                'quote' => 'Báo giá',
                default => 'Khác',
            }),
            ExportColumn::make('vehicle.name')->label('Xe quan tâm'),
            ExportColumn::make('variant.name')->label('Phiên bản'),
            ExportColumn::make('vehicleColor.name')->label('Màu xe'),
            ExportColumn::make('selected_price')->label('Giá đã chọn')->formatStateUsing(fn (?int $state): string => $state === null ? '' : number_format($state, 0, ',', '.').' VNĐ'),
            ExportColumn::make('region')->label('Khu vực'),
            ExportColumn::make('status')->label('Trạng thái')->formatStateUsing(fn (string $state): string => match ($state) {
                'new' => 'Mới',
                'called' => 'Đã gọi',
                'test_drive_scheduled' => 'Hẹn lái thử',
                'sold' => 'Đã bán',
                default => $state,
            }),
            ExportColumn::make('source')->label('Nguồn'),
            ExportColumn::make('created_at')->label('Ngày nhận'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Đã xuất '.Str::of('lead')->counted($export->successful_rows).'.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('lead')->counted($failedRowsCount).' chưa xuất được.';
        }

        return $body;
    }
}
