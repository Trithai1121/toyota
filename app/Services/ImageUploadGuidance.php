<?php

namespace App\Services;

class ImageUploadGuidance
{
    public function warnings(string $path, ?float $expectedRatio = null): array
    {
        if (! is_file($path)) {
            return ['Không thể kiểm tra ảnh đã tải lên. Vui lòng chọn lại ảnh.'];
        }

        $size = filesize($path);
        $dimensions = @getimagesize($path);

        if ($size === false || $dimensions === false || $dimensions[1] === 0) {
            return ['Không thể đọc dung lượng hoặc kích thước ảnh. Vui lòng chọn ảnh khác.'];
        }

        $warnings = [];

        if ($size > 3 * 1024 * 1024) {
            $warnings[] = 'Ảnh lớn hơn 3 MB; nên tối ưu dung lượng để trang tải nhanh trên điện thoại.';
        }

        if ($expectedRatio !== null) {
            $actualRatio = $dimensions[0] / $dimensions[1];
            $tolerance = max(0.12, $expectedRatio * 0.1);

            if (abs($actualRatio - $expectedRatio) > $tolerance) {
                $warnings[] = sprintf(
                    'Tỉ lệ ảnh hiện tại là %.2f:1; khối này hiển thị tốt nhất gần %.2f:1.',
                    $actualRatio,
                    $expectedRatio,
                );
            }
        }

        return $warnings;
    }
}
