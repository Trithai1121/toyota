<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class ToyotaVehicleSeoSeeder extends Seeder
{
    private const VERIFIED_AT = '2026-10-24';

    /** Model-specific copy based on the corresponding Toyota Vietnam model page. */
    private const CONTENT = [
        'Wigo' => [
            'title' => 'Toyota Wigo tại Kon Tum | Phiên bản và thông tin xe',
            'meta' => 'Tìm hiểu Toyota Wigo tại Kon Tum: phiên bản Wigo G, động cơ 1.2L, hộp số D-CVT, hình ảnh, trang bị và thông tin từ Toyota Việt Nam.',
            'summary' => 'Toyota Wigo là mẫu hatchback đô thị 5 chỗ, có động cơ 1.2L và hộp số D-CVT.',
            'description' => "Toyota Wigo có kiểu dáng hatchback gọn cho nhu cầu đi lại trong đô thị. Phiên bản Wigo G tại Việt Nam sử dụng động cơ xăng 1.2L kết hợp hộp số D-CVT.\n\nTrang xe tổng hợp hình ảnh ngoại thất, nội thất, vận hành và an toàn từ Toyota Việt Nam. Các hỗ trợ như VSC, HAC, BSM hoặc RCTA cần được đối chiếu theo trang bị phiên bản hiện hành.",
        ],
        'vios' => [
            'title' => 'Toyota Vios tại Kon Tum | Phiên bản và thông tin xe',
            'meta' => 'Xem Toyota Vios tại Kon Tum: các phiên bản 1.5E MT, 1.5E CVT, 1.5G CVT, thông tin động cơ, hộp số, hình ảnh và trang bị theo Toyota Việt Nam.',
            'summary' => 'Toyota Vios là mẫu sedan 5 chỗ với động cơ xăng 1.5L và lựa chọn hộp số sàn hoặc CVT.',
            'description' => "Toyota Vios là sedan 5 chỗ thuộc phân khúc xe du lịch cỡ nhỏ. Dải phiên bản gồm lựa chọn hộp số sàn và CVT, giúp khách hàng cân nhắc theo cách sử dụng và ngân sách.\n\nTrang này tập hợp hình ảnh, thông tin vận hành và trang bị an toàn theo dữ liệu Toyota Việt Nam. Một số tính năng và số lượng túi khí thay đổi theo phiên bản; hãy chọn đúng grade trước khi so sánh.",
        ],
        'yaris-cross' => [
            'title' => 'Toyota Yaris Cross tại Kon Tum | Xăng và Hybrid',
            'meta' => 'Khám phá Toyota Yaris Cross tại Kon Tum: phiên bản xăng và Hybrid, thông tin vận hành, tiện nghi, an toàn cùng hình ảnh theo Toyota Việt Nam.',
            'summary' => 'Toyota Yaris Cross là SUV đô thị 5 chỗ, có phiên bản động cơ xăng và Hybrid tự sạc.',
            'description' => "Toyota Yaris Cross kết hợp kiểu dáng SUV đô thị với khoang xe 5 chỗ. Tại Việt Nam, mẫu xe có phiên bản xăng và Hybrid; hệ thống Hybrid tự nạp năng lượng trong quá trình vận hành, không cần cắm sạc ngoài.\n\nTrang xe cung cấp hình ảnh và nội dung theo nhóm ngoại thất, nội thất, vận hành và an toàn. Trang bị tiện nghi và hỗ trợ lái có thể khác giữa bản xăng và HEV, vì vậy thông tin cần được đối chiếu theo phiên bản.",
        ],
        'CorollaCross' => [
            'title' => 'Toyota Corolla Cross tại Kon Tum | Xăng và Hybrid',
            'meta' => 'Tìm hiểu Toyota Corolla Cross tại Kon Tum: phiên bản 1.8V và 1.8HEV, thông tin ngoại thất, nội thất, vận hành, an toàn từ Toyota Việt Nam.',
            'summary' => 'Toyota Corolla Cross là SUV 5 chỗ với lựa chọn động cơ xăng 1.8L hoặc Hybrid 1.8L.',
            'description' => "Toyota Corolla Cross là mẫu SUV 5 chỗ có hai cấu hình truyền động tại Việt Nam: động cơ xăng 1.8L và Hybrid 1.8L. Người mua có thể so sánh phiên bản theo giá niêm yết, trang bị và nhu cầu sử dụng.\n\nCác mục ngoại thất, nội thất, vận hành và an toàn trên trang được tổng hợp từ nguồn Toyota Việt Nam. Những tính năng như hỗ trợ đỗ xe, camera và Toyota Safety Sense phụ thuộc phiên bản.",
        ],
        'Raize' => [
            'title' => 'Toyota Raize tại Kon Tum | SUV Turbo 1.0L',
            'meta' => 'Xem Toyota Raize tại Kon Tum: SUV đô thị 5 chỗ, động cơ Turbo 1.0L, hộp số CVT, hình ảnh và trang bị theo Toyota Việt Nam.',
            'summary' => 'Toyota Raize là SUV đô thị 5 chỗ sử dụng động cơ Turbo 1.0L và hộp số CVT.',
            'description' => "Toyota Raize hướng đến nhu cầu di chuyển linh hoạt với thân xe SUV đô thị 5 chỗ. Xe sử dụng động cơ xăng Turbo 1.0L kết hợp hộp số CVT; bán kính quay vòng tối thiểu được Toyota công bố là 5,1 m.\n\nTrang này trình bày hình ảnh và các thông tin ngoại thất, nội thất, vận hành, an toàn. Trang bị hỗ trợ lái và cảm biến được ghi theo nội dung Toyota Việt Nam công bố cho mẫu xe.",
        ],
        'VelozCross' => [
            'title' => 'Toyota Veloz Cross tại Kon Tum | MPV 7 chỗ',
            'meta' => 'Khám phá Toyota Veloz Cross tại Kon Tum: MPV 7 chỗ, động cơ 1.5L, hộp số CVT, thông tin phiên bản và trang bị từ Toyota Việt Nam.',
            'summary' => 'Toyota Veloz Cross là MPV 7 chỗ với động cơ 1.5L, hộp số CVT và dẫn động cầu trước.',
            'description' => "Toyota Veloz Cross là mẫu MPV 7 chỗ sử dụng động cơ xăng 1.5L, hộp số CVT và dẫn động cầu trước. Các phiên bản CVT và CVT Top có khác biệt về trang bị.\n\nKhách hàng có thể xem hình ảnh theo từng hạng mục và tham khảo thông tin vận hành, tiện nghi, an toàn trên trang. Camera toàn cảnh và một số công nghệ hỗ trợ lái chỉ áp dụng theo cấu hình Toyota Việt Nam công bố.",
        ],
        'AvanzaPremio' => [
            'title' => 'Toyota Avanza Premio tại Kon Tum | MPV 7 chỗ',
            'meta' => 'Tìm hiểu Toyota Avanza Premio tại Kon Tum: MPV 7 chỗ, động cơ 1.5L, lựa chọn hộp số MT hoặc CVT, hình ảnh và trang bị theo phiên bản.',
            'summary' => 'Toyota Avanza Premio là MPV 7 chỗ với động cơ 1.5L và lựa chọn hộp số MT hoặc CVT.',
            'description' => "Toyota Avanza Premio có cấu hình MPV 7 chỗ, ba hàng ghế và động cơ xăng 1.5L. Khách hàng có thể chọn phiên bản hộp số sàn hoặc CVT theo nhu cầu sử dụng.\n\nTrang xe tổng hợp ảnh và thông tin về ngoại thất, nội thất, vận hành, an toàn. Trang bị tiện nghi và số túi khí khác nhau theo phiên bản, nên cần đối chiếu thông số của đúng grade khi lựa chọn.",
        ],
        'InnovaCross' => [
            'title' => 'Toyota Innova Cross tại Kon Tum | Xăng và Hybrid',
            'meta' => 'Xem Toyota Innova Cross tại Kon Tum: phiên bản 2.0G, 2.0V, 2.0HEV, cấu hình 7 hoặc 8 chỗ và trang bị theo Toyota Việt Nam.',
            'summary' => 'Toyota Innova Cross có lựa chọn động cơ xăng 2.0L hoặc Hybrid, với cấu hình 7 và 8 chỗ tùy phiên bản.',
            'description' => "Toyota Innova Cross là mẫu xe đa dụng có các phiên bản động cơ xăng 2.0L và Hybrid 2.0L. Cấu hình ghế thay đổi theo phiên bản: bản xăng có 8 chỗ, trong khi bản HEV bố trí 7 chỗ với hàng ghế thứ hai riêng biệt.\n\nTrang này cung cấp hình ảnh và thông tin chi tiết theo nhóm ngoại thất, nội thất, vận hành, an toàn. Gói Toyota Safety Sense cùng các tính năng camera, hỗ trợ đỗ xe được trang bị theo grade.",
        ],
        'Camry' => [
            'title' => 'Toyota Camry tại Kon Tum | Sedan Hybrid',
            'meta' => 'Khám phá Toyota Camry tại Kon Tum: sedan 5 chỗ phiên bản Hybrid, thông tin thiết kế, vận hành, tiện nghi và an toàn theo Toyota Việt Nam.',
            'summary' => 'Toyota Camry là sedan 5 chỗ với các phiên bản Hybrid và hộp số E-CVT.',
            'description' => "Toyota Camry là sedan 5 chỗ hướng đến trải nghiệm di chuyển tiện nghi cho cả người lái và hành khách. Các phiên bản Hybrid sử dụng động cơ xăng kết hợp mô-tơ điện và hộp số E-CVT.\n\nKhám phá hình ảnh cùng nội dung về ngoại thất, cabin, vận hành và an toàn trên trang xe. Các tiện nghi và công nghệ hỗ trợ lái có thể khác theo phiên bản; thông số hiển thị được đối chiếu với nguồn Toyota Việt Nam.",
        ],
        'Fortuner' => [
            'title' => 'Toyota Fortuner tại Kon Tum | SUV 7 chỗ',
            'meta' => 'Tìm hiểu Toyota Fortuner tại Kon Tum: SUV 7 chỗ, các phiên bản và cấu hình động cơ, dẫn động, tiện nghi, an toàn từ Toyota Việt Nam.',
            'summary' => 'Toyota Fortuner là SUV 7 chỗ với nhiều cấu hình động cơ, hộp số và hệ dẫn động theo phiên bản.',
            'description' => "Toyota Fortuner là mẫu SUV 7 chỗ với nhiều cấu hình phiên bản. Động cơ, hộp số và hệ dẫn động có thể khác nhau, giúp khách hàng cân nhắc giữa nhu cầu đi phố, đường trường và các hành trình cần khả năng vận hành đa dụng.\n\nTrang xe tổng hợp hình ảnh và thông tin về ngoại thất, nội thất, vận hành, an toàn. Hãy chọn đúng phiên bản để kiểm tra các trang bị như camera, BSM, RCTA và các hệ thống hỗ trợ khác.",
        ],
        'Hilux' => [
            'title' => 'Toyota Hilux tại Kon Tum | Bán tải diesel 2.8L',
            'meta' => 'Xem Toyota Hilux tại Kon Tum: bán tải 5 chỗ, động cơ diesel 2.8L, các phiên bản Standard, Pro, Trailhunter và trang bị theo grade.',
            'summary' => 'Toyota Hilux là bán tải 5 chỗ sử dụng động cơ diesel 2.8L với cấu hình hộp số và dẫn động theo phiên bản.',
            'description' => "Toyota Hilux là mẫu bán tải 5 chỗ với động cơ diesel 2.8L. Dải phiên bản Standard, Pro và Trailhunter có lựa chọn hộp số, dẫn động cùng trang bị khác nhau.\n\nTrang này cung cấp hình ảnh ngoại thất, nội thất, vận hành và an toàn theo nội dung Toyota Việt Nam. Các công nghệ hỗ trợ lái và địa hình không mặc định có trên mọi phiên bản; hãy xem thông số đúng grade trước khi quyết định.",
        ],
        'land-cruiser-300' => [
            'title' => 'Toyota Land Cruiser 300 tại Kon Tum | Thông tin xe',
            'meta' => 'Tìm hiểu Toyota Land Cruiser 300 tại Kon Tum: SUV 7 chỗ, động cơ V6 tăng áp kép 3.5L, hộp số tự động 10 cấp và trang bị theo Toyota Việt Nam.',
            'summary' => 'Toyota Land Cruiser 300 là SUV 7 chỗ với động cơ V6 tăng áp kép 3.5L và hộp số tự động 10 cấp.',
            'description' => "Toyota Land Cruiser 300 là SUV 7 chỗ thuộc dòng Land Cruiser. Toyota Việt Nam công bố cấu hình động cơ V6 tăng áp kép 3.5L kết hợp hộp số tự động 10 cấp cùng các chế độ hỗ trợ vận hành địa hình.\n\nTrang xe tập hợp hình ảnh và thông tin ngoại thất, nội thất, vận hành, an toàn. Vui lòng đối chiếu catalogue và phiên bản phân phối hiện hành để xác nhận đầy đủ cấu hình trước khi đặt xe.",
        ],
        'land-cruiser-hev' => [
            'title' => 'Toyota Land Cruiser HEV tại Kon Tum | Thông tin xe',
            'meta' => 'Khám phá Toyota Land Cruiser HEV tại Kon Tum: hình ảnh, các hạng mục ngoại thất, nội thất, vận hành và an toàn theo thông tin Toyota Việt Nam.',
            'summary' => 'Trang thông tin Toyota Land Cruiser HEV tổng hợp hình ảnh và nội dung theo nguồn Toyota Việt Nam.',
            'description' => "Trang Toyota Land Cruiser HEV tổng hợp hình ảnh và nội dung sản phẩm theo nguồn Toyota Việt Nam. Các mục ngoại thất, nội thất, vận hành và an toàn được trình bày riêng để khách hàng tra cứu thuận tiện.\n\nThông tin hệ truyền động và trang bị có thể thay đổi theo phiên bản phân phối. Hãy đối chiếu bảng thông số kỹ thuật hoặc catalogue chính thức của đúng grade trước khi sử dụng thông tin để so sánh hay đặt xe.",
        ],
        'land-cruiser-prado' => [
            'title' => 'Toyota Land Cruiser Prado tại Kon Tum | SUV 7 chỗ',
            'meta' => 'Tìm hiểu Toyota Land Cruiser Prado tại Kon Tum: SUV 7 chỗ, động cơ tăng áp 2.4L, hộp số tự động 8 cấp và trang bị địa hình theo grade.',
            'summary' => 'Toyota Land Cruiser Prado là SUV 7 chỗ với động cơ tăng áp 2.4L và hộp số tự động 8 cấp.',
            'description' => "Toyota Land Cruiser Prado là SUV 7 chỗ được phát triển cho những hành trình đa dạng. Cấu hình Toyota Việt Nam công bố sử dụng động cơ tăng áp 2.4L và hộp số tự động 8 cấp; hệ thống treo thích ứng và chế độ hỗ trợ địa hình tùy cấu hình.\n\nXem ảnh và thông tin về ngoại thất, nội thất, vận hành, an toàn trên trang xe. Các tính năng hỗ trợ lái và địa hình nên được xác nhận theo phiên bản cụ thể.",
        ],
        'land-cruiser-fj' => [
            'title' => 'Toyota Land Cruiser FJ tại Kon Tum | SUV địa hình',
            'meta' => 'Khám phá Toyota Land Cruiser FJ tại Kon Tum: SUV 5 chỗ, động cơ xăng 2.7L 2TR-FE, chế độ khởi hành số 2 và trang bị an toàn theo Toyota Việt Nam.',
            'summary' => 'Toyota Land Cruiser FJ là SUV 5 chỗ với động cơ 2.7L 2TR-FE và chế độ khởi hành số 2 hỗ trợ địa hình trơn trượt.',
            'description' => "Toyota Land Cruiser FJ là SUV 5 chỗ mang thiết kế phục vụ những hành trình khám phá. Xe sử dụng động cơ xăng 2.7L 2TR-FE; chế độ khởi hành từ số 2 hỗ trợ kiểm soát lực kéo trên một số bề mặt trơn trượt.\n\nTrang xe trình bày ảnh và thông tin ngoại thất, nội thất, vận hành, an toàn. Các tính năng như camera toàn cảnh, RCTA, BSM, PCS và DRCC được đối chiếu theo cấu hình Toyota Việt Nam công bố.",
        ],
        'alphard' => [
            'title' => 'Toyota Alphard HEV tại Kon Tum | MPV Hybrid 7 chỗ',
            'meta' => 'Tìm hiểu Toyota Alphard HEV tại Kon Tum: MPV Hybrid 7 chỗ, động cơ 2.5L, hình ảnh nội thất, tiện nghi và trang bị an toàn theo Toyota Việt Nam.',
            'summary' => 'Toyota Alphard HEV là MPV Hybrid 7 chỗ với động cơ xăng 2.5L kết hợp mô-tơ điện.',
            'description' => "Toyota Alphard HEV là mẫu MPV Hybrid 7 chỗ, sử dụng động cơ xăng 2.5L kết hợp mô-tơ điện. Không gian cabin và tiện nghi hàng ghế sau là những nội dung đáng chú ý khi khách hàng tìm hiểu mẫu xe này.\n\nTrang xe tổng hợp hình ảnh cùng thông tin ngoại thất, nội thất, vận hành và an toàn theo nguồn Toyota Việt Nam. Hãy tham khảo thông số của phiên bản đang phân phối để xác nhận cấu hình và trang bị cụ thể.",
        ],
    ];

    public function run(): void
    {
        foreach (self::CONTENT as $slug => $content) {
            $vehicle = Vehicle::query()->where('slug', $slug)->first();
            if (! $vehicle) {
                continue;
            }

            $vehicle->forceFill([
                'seo_title' => $content['title'],
                'seo_description' => $content['meta'],
                'summary' => $content['summary'],
                'description' => $content['description'],
                'content_verified_at' => self::VERIFIED_AT,
            ])->save();
        }
    }
}
