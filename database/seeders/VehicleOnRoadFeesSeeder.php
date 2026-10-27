<?php

namespace Database\Seeders;

use App\Models\Variant;
use App\Models\VehicleOnRoadFee;
use Illuminate\Database\Seeder;

class VehicleOnRoadFeesSeeder extends Seeder
{
    /** [variant name, body insurance %, seats or pickup, review note] */
    private const VEHICLES = [
        ['WIGO G', 2.00, 5, null],
        ['RAIZE 1.0 TURBO', 1.30, 5, null],
        ['VIOS 1.5E-MT', 1.69, 5, null],
        ['VIOS 1.5E-CVT', 1.69, 5, null],
        ['VIOS 1.5G-CVT', 1.69, 5, null],
        ['YARIS CROSS', 1.30, 5, null],
        ['YARIS CROSS HEV', 1.30, 5, null],
        ['COROLLA CROSS 1.8V', 1.20, 5, null],
        ['COROLLA CROSS 1.8HEV', 1.20, 5, null],
        ['VELOZ CROSS CVT', 1.30, 7, null],
        ['VELOZ CROSS CVT TOP', 1.30, 7, null],
        ['AVANZA PREMIO MT', 1.46, 7, null],
        ['AVANZA PREMIO CVT', 1.46, 7, null],
        ['INNOVA CROSS 2.0G', 1.20, 7, null],
        ['INNOVA CROSS 2.0V', 1.20, 7, null],
        ['INNOVA CROSS 2.0HEV', 1.20, 7, null],
        ['CAMRY HEV LUXURY', 1.00, 5, 'Đối chiếu với tên CAMRY HEV TOP trong workbook.'],
        ['CAMRY HEV PREMIUM', 1.00, 5, null],
        ['FORTUNER 2.7AT 4x2', 1.10, 7, null],
        ['FORTUNER LEGENDER 2.7AT 4x2', 1.10, 7, null],
        ['FORTUNER LEGENDER 2.7AT 4x4', 1.10, 7, 'Workbook ghi trước bạ 125 triệu; áp dụng mức 10% theo yêu cầu hiện tại.'],
        ['HILUX STANDARD 2.8 4x2 MT', 1.33, 'pickup', null],
        ['HILUX PRO 2.8 4x2 AT', 1.50, 'pickup', null],
        ['HILUX TRAILHUNTER 2.8 4x4 AT', 1.45, 'pickup', null],
        ['LAND CRUISER FJ', null, 5, 'Workbook chưa có tỷ lệ bảo hiểm thân vỏ; khoản này chưa được cộng.'],
        ['ALPHARD HEV', null, 7, 'Workbook chưa có tỷ lệ bảo hiểm thân vỏ; khoản này chưa được cộng.'],
        ['LAND CRUISER PRADO', null, 7, 'Workbook chưa có tỷ lệ bảo hiểm thân vỏ; khoản này chưa được cộng.'],
        ['LAND CRUISER 300', null, 7, 'Workbook chưa có tỷ lệ bảo hiểm thân vỏ; khoản này chưa được cộng.'],
    ];

    public function run(): void
    {
        foreach (self::VEHICLES as [$variantName, $bodyRate, $class, $note]) {
            $variants = Variant::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($variantName)])
                ->get();

            if ($variants->isEmpty()) {
                $this->command?->warn("Không tìm thấy phiên bản để cấu hình phí lăn bánh: {$variantName}");

                continue;
            }

            foreach ($variants as $variant) {
                $isPickup = $class === 'pickup';
                $seats = $isPickup ? null : (int) $class;
                $registrationRate = $isPickup ? 6 : 10;
                $source = 'CRM_Ban_Xe_Toyota_v6.xlsx · Bảng giá và Khuyến mãi & Chi phí.';

                VehicleOnRoadFee::query()->updateOrCreate(
                    ['variant_id' => $variant->id, 'region' => 'kon-tum'],
                    [
                        'registration_fee' => 0,
                        'registration_rate_percent' => $registrationRate,
                        'body_insurance_rate_percent' => $bodyRate,
                        'license_plate_fee' => 2_000_000,
                        'inspection_fee' => 95_000,
                        'road_use_fee' => $isPickup ? 2_160_000 : 1_560_000,
                        'compulsory_insurance_fee' => $isPickup || $seats === 5 ? 630_700 : 1_113_400,
                        'service_fee' => 4_000_000,
                        'other_fee' => 0,
                        'source_note' => trim(implode(' ', array_filter([
                            $source,
                            "Trước bạ tính {$registrationRate}% trên giá niêm yết theo yêu cầu cấu hình.",
                            $note,
                        ]))),
                        'is_enabled' => true,
                    ],
                );
            }
        }
    }
}
