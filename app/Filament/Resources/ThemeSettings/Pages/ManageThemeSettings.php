<?php

namespace App\Filament\Resources\ThemeSettings\Pages;

use App\Filament\Resources\ThemeSettings\ThemeSettingResource;
use App\Models\ThemeSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageThemeSettings extends ManageRecords
{
    protected static string $resource = ThemeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): ThemeSetting {
                    if (array_key_exists('settings_json', $data)) {
                        $data['value'] = $data['settings_json'];
                        unset($data['settings_json']);
                    }

                    return ThemeSetting::query()->create($data);
                })
                ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
        ];
    }
}
