<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class ToyotaOfficialNewsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->stories() as $story) {
            Post::query()->firstOrCreate(
                ['slug' => $story['slug']],
                [
                    ...$story,
                    'cover_image_path' => null,
                    'cover_image_alt' => null,
                    'image_credit' => null,
                    'status' => 'draft',
                    'published_at' => null,
                ],
            );
        }
    }

    private function stories(): array
    {
        return [
            [
                'title' => 'Toyota Việt Nam xác nhận các mẫu xe tương thích với xăng E10',
                'slug' => 'toyota-viet-nam-xac-nhan-tuong-thich-xang-e10',
                'excerpt' => 'Toyota Việt Nam cho biết các mẫu xe hãng phân phối chính thức từ năm 1997 trở lại đây tương thích với xăng E10. Chủ xe vẫn cần dùng nhiên liệu đạt chuẩn và bảo dưỡng theo hướng dẫn của nhà sản xuất.',
                'source_url' => 'https://www.toyota.com.vn/tin-tuc/san-pham/kha-nang-tuong-thich-voi-xang-sinh-hoc-e10-cua-cac-mau-xe-toyota-44402',
                'body' => '<h2>Thông tin từ Toyota Việt Nam</h2><p>Theo thông báo ngày 08/06/2026, Toyota Việt Nam xác nhận các mẫu xe do hãng sản xuất, lắp ráp, nhập khẩu và phân phối chính thức từ năm 1997 trở lại đây được thiết kế tương thích với xăng E10.</p><p>Hãng khuyến nghị giữ nguyên trạng hệ thống nhiên liệu, sử dụng nhiên liệu đạt chuẩn tại điểm bán uy tín và thực hiện bảo dưỡng định kỳ theo sách hướng dẫn của từng xe.</p><h2>Lưu ý cho chủ xe</h2><p>Thông tin trên là nội dung tóm lược từ thông báo của Toyota Việt Nam. Với xe nhập khẩu không chính thức, xe đã cải tạo hoặc trường hợp cần xác nhận theo số khung, vui lòng kiểm tra sách hướng dẫn và liên hệ đại lý Toyota.</p>',
                'seo_title' => 'Toyota Việt Nam xác nhận xe tương thích xăng E10',
                'seo_description' => 'Tóm lược thông báo chính thức của Toyota Việt Nam về khả năng tương thích xăng E10 và lưu ý sử dụng nhiên liệu, bảo dưỡng.',
            ],
            [
                'title' => 'Toyota Việt Nam giới thiệu Land Cruiser FJ hoàn toàn mới',
                'slug' => 'toyota-viet-nam-gioi-thieu-land-cruiser-fj-moi',
                'excerpt' => 'Land Cruiser FJ được Toyota Việt Nam giới thiệu ngày 28/07/2026. Bài viết tóm lược thông tin ra mắt; giá và tình trạng xe tại đại lý cần được xác nhận riêng.',
                'source_url' => 'https://www.toyota.com.vn/tin-tuc/san-pham/toyota-viet-nam-chinh-thuc-gioi-thieu-land-cruiser-fj-hoan-toan-moi-44406',
                'body' => '<h2>Mẫu SUV mới trong gia đình Land Cruiser</h2><p>Toyota Việt Nam thông báo Land Cruiser FJ chính thức có mặt tại hệ thống đại lý trên toàn quốc từ ngày 28/07/2026. Mẫu xe hướng tới người dùng cần một chiếc SUV gọn hơn nhưng vẫn mang phong cách địa hình đặc trưng của dòng Land Cruiser.</p><p>Theo thông tin công bố, xe nhập khẩu nguyên chiếc từ Thái Lan, trang bị động cơ xăng 2.7L, hộp số tự động 6 cấp và hệ dẫn động bốn bánh. Trang bị, giá bán và thời điểm giao xe có thể khác theo cấu hình và chính sách tại từng thời điểm.</p><h2>Thông tin tại Toyota Kon Tum</h2><p>Liên hệ đại lý để xác nhận giá, phiên bản, màu xe và lịch xe thực tế trước khi đến xem hoặc đăng ký lái thử.</p>',
                'seo_title' => 'Land Cruiser FJ mới: thông tin ra mắt tại Việt Nam',
                'seo_description' => 'Thông tin tóm lược từ Toyota Việt Nam về Land Cruiser FJ; liên hệ đại lý để xác nhận giá và tình trạng xe.',
            ],
            [
                'title' => 'Toyota Việt Nam công bố doanh số bán hàng tháng 8/2026',
                'slug' => 'toyota-viet-nam-doanh-so-thang-8-2026',
                'excerpt' => 'Thông tin doanh số tháng 8/2026 do Toyota Việt Nam công bố, gồm kết quả toàn hãng và một số mẫu xe có lượng bán nổi bật.',
                'source_url' => 'https://www.toyota.com.vn/tin-tuc/san-pham/toyota-viet-nam-cong-bo-doanh-so-ban-hang-thang-8-2026-45051',
                'body' => '<h2>Kết quả kinh doanh tháng 8/2026</h2><p>Theo công bố ngày 03/09/2026 của Toyota Việt Nam, doanh số trong tháng đạt 4.900 xe nếu tính cả Lexus; riêng các mẫu Toyota đạt 4.767 xe. Hãng ghi nhận 1.046 xe Hybrid Electric trong tháng.</p><p>Trong nhóm mẫu xe Toyota, Yaris Cross đạt 920 xe và Innova Cross đạt 829 xe theo số liệu hãng công bố. Đây là số liệu toàn quốc, không đại diện cho doanh số riêng tại Kon Tum.</p><p>Số liệu được tóm lược từ nguồn chính thức bên dưới; kết quả từng tháng có thể thay đổi theo thời điểm và thị trường.</p>',
                'seo_title' => 'Doanh số Toyota Việt Nam tháng 8/2026',
                'seo_description' => 'Tóm lược số liệu doanh số Toyota Việt Nam tháng 8/2026 theo thông báo chính thức của hãng.',
            ],
        ];
    }
}
