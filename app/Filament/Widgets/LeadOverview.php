<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class LeadOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Tổng quan khách hàng tiềm năng';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'sale']) ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user?->hasAnyRole(['admin', 'sale'])) {
            return [];
        }

        $counts = Lead::query()
            ->when(
                $user->hasRole('sale'),
                fn (Builder $query): Builder => $query->where('assigned_to', $user->id),
            )
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $counts->sum();

        return [
            Stat::make('Tổng lead', $total)
                ->description('Trong phạm vi được phép xem')
                ->color('primary'),
            Stat::make('Mới', $counts->get('new', 0))
                ->color('warning'),
            Stat::make('Đã gọi', $counts->get('called', 0))
                ->color('info'),
            Stat::make('Hẹn lái thử', $counts->get('test_drive_scheduled', 0))
                ->color('success'),
            Stat::make('Đã bán', $counts->get('sold', 0))
                ->color('gray'),
        ];
    }
}
