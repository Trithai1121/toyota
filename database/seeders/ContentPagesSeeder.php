<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentPagesSeeder extends Seeder
{
    public function run(): void
    {
        $home = Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Toyota Kon Tum',
                'seo_title' => 'Toyota Kon Tum | Tư vấn xe Toyota tại Kon Tum, Gia Lai, Quảng Ngãi',
                'seo_description' => 'Tìm hiểu các dòng xe Toyota và gửi yêu cầu tư vấn tại Kon Tum, Gia Lai và Quảng Ngãi.',
                'status' => 'published',
                'is_published' => true,
            ],
        );

        if ($home->wasRecentlyCreated) {
            $home->blocks()->createMany([
                [
                    'type' => 'hero',
                    'title' => 'Tìm hiểu xe Toyota phù hợp với nhu cầu của bạn',
                    'description' => 'Nhận tư vấn về xe Toyota tại Kon Tum, Gia Lai và Quảng Ngãi. Giá, phiên bản và ưu đãi chỉ được công bố sau khi đại lý xác nhận.',
                    'cta_label' => 'Nhận báo giá',
                    'cta_url' => '/dang-ky?type=quote',
                    'sort_order' => 1,
                ],
                [
                    'type' => 'vehicle_grid',
                    'title' => 'Các mẫu xe Toyota',
                    'description' => 'Thông tin mẫu xe hiển thị sau khi được đại lý duyệt.',
                    'settings' => [],
                    'sort_order' => 2,
                ],
                [
                    'type' => 'promotion_strip',
                    'title' => 'Chương trình ưu đãi',
                    'description' => 'Thông tin ưu đãi sẽ được cập nhật khi có nội dung chính thức.',
                    'sort_order' => 3,
                ],
                [
                    'type' => 'recent_posts',
                    'title' => 'Tin tức',
                    'sort_order' => 4,
                ],
                [
                    'type' => 'calculator',
                    'title' => 'Công cụ tham khảo',
                    'description' => 'Công cụ chỉ hiển thị kết quả khi đại lý cấu hình đầy đủ giá, phí và lãi suất.',
                    'sort_order' => 5,
                ],
                [
                    'type' => 'lead_form',
                    'title' => 'Đăng ký lái thử hoặc nhận báo giá',
                    'description' => 'Để lại thông tin để đại lý tiếp nhận yêu cầu.',
                    'cta_label' => 'Gửi yêu cầu',
                    'settings' => ['request_type' => 'quote'],
                    'sort_order' => 6,
                ],
                [
                    'type' => 'cta',
                    'title' => 'Cần thêm thông tin về xe?',
                    'description' => 'Gửi yêu cầu để nhân viên đại lý liên hệ tư vấn.',
                    'cta_label' => 'Đăng ký tư vấn',
                    'cta_url' => '/dang-ky?type=quote',
                    'sort_order' => 7,
                ],
                [
                    'type' => 'showroom_map',
                    'title' => 'Bản đồ showroom',
                    'description' => 'Bản đồ sẽ hiển thị sau khi đại lý cung cấp đường dẫn chính thức.',
                    'sort_order' => 8,
                ],
            ]);

            $home->createRevision(summary: 'Khởi tạo nội dung website');
        }

        if (! $home->blocks()->where('type', 'lead_form')->exists()) {
            $home->blocks()->where('sort_order', '>=', 6)->increment('sort_order');
            $home->blocks()->create([
                'type' => 'lead_form',
                'title' => 'Đăng ký lái thử hoặc nhận báo giá',
                'description' => 'Để lại thông tin để đại lý tiếp nhận yêu cầu.',
                'cta_label' => 'Gửi yêu cầu',
                'settings' => ['request_type' => 'quote'],
                'sort_order' => 6,
            ]);

            if (! $home->wasRecentlyCreated) {
                $home->createRevision(summary: 'Bổ sung biểu mẫu đăng ký trên trang chủ');
            }
        }

        foreach ([
            'bao-duong' => [
                'title' => 'Dịch vụ bảo dưỡng',
                'body' => 'Liên hệ đại lý để được xác nhận dịch vụ, lịch tiếp nhận và thông tin hiện hành.',
            ],
            'phu-tung-phu-kien' => [
                'title' => 'Phụ tùng và phụ kiện',
                'body' => 'Thông tin sản phẩm, khả năng cung ứng và điều kiện bảo hành cần được đại lý xác nhận.',
            ],
            'gioi-thieu' => [
                'title' => 'Giới thiệu đại lý',
                'body' => 'Thông tin đại lý sẽ được cập nhật sau khi được đại lý cung cấp và xác nhận.',
            ],
            'lien-he' => [
                'title' => 'Liên hệ',
                'body' => 'Thông tin liên hệ chính thức sẽ được cập nhật sau khi được đại lý cung cấp và xác nhận.',
            ],
            'chinh-sach-bao-mat' => [
                'title' => 'Chính sách bảo mật',
                'body' => 'Chính sách cần được đại lý rà soát và phê duyệt theo quy trình xử lý dữ liệu thực tế trước khi website tiếp nhận thông tin khách hàng.',
            ],
        ] as $slug => $data) {
            $page = Page::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'seo_title' => $data['title'].' | Toyota Kon Tum',
                    'seo_description' => $data['body'],
                    'status' => 'published',
                    'is_published' => true,
                ],
            );

            if ($page->wasRecentlyCreated) {
                $page->blocks()->create([
                    'type' => 'text_image',
                    'title' => $data['title'],
                    'body' => '<p>'.e($data['body']).'</p>',
                    'sort_order' => 1,
                ]);
                $page->createRevision(summary: 'Khởi tạo nội dung trang');
            }
        }
    }
}
