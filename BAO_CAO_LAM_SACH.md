# Báo cáo làm sạch và chuẩn bị triển khai

**Ngày kiểm tra:** 26/10/2026  
**Phạm vi:** project Laravel và website local `http://127.0.0.1:8081`. Không ghi/xóa dữ liệu DB thật. Lệnh tạo/xóa schema chạy trong SQLite `:memory:` riêng và kết thúc trong cùng tiến trình.

## Kết luận

Project đã qua được bộ test và build local sau các sửa đổi dưới đây, nhưng **chưa thể xác nhận sẵn sàng production**. Cần hoàn tất CSP, xác nhận thông tin showroom/domain/server, chạy Lighthouse trên môi trường có Chrome/Lighthouse, và xác minh lịch sử bí mật. `DEPLOY.md` chưa được tạo vì cần biết loại hosting và phiên bản PHP/MySQL server trước theo yêu cầu.

## Thay đổi đã thực hiện

- Chuẩn hóa định dạng PHP bằng Pint; `pint --test` hiện qua.
- Sửa validation `variant_id` cho phép bỏ trống ở bộ tính trả góp/lăn bánh trong [CalculatorController.php](app/Http/Controllers/CalculatorController.php:23). Cập nhật các test đang dùng dữ liệu/kỳ vọng cũ trong [CalculatorTest.php](tests/Feature/CalculatorTest.php), [DatabaseSeederTest.php](tests/Feature/DatabaseSeederTest.php), [PublicSeoTest.php](tests/Feature/PublicSeoTest.php) và [VehicleCatalogTest.php](tests/Feature/VehicleCatalogTest.php).
- Ngăn ảnh xe chưa có URL nguồn và xác nhận quyền sử dụng được hiển thị hoặc đưa vào Open Graph trong [VehicleImage.php](app/Models/VehicleImage.php:22). Bổ sung/điều chỉnh test để xác nhận ảnh chưa xác minh không bị lộ trong phản hồi trang xe. Ảnh do `ToyotaVehicleImageSeeder` lấy về mà chưa được đánh dấu quyền sử dụng sẽ tiếp tục bị ẩn cho đến khi người quản trị xác minh quyền.
- Cập nhật Composer lock: Livewire `4.4.7` lên `4.4.8` theo advisory [GHSA-mhf7-xp6r-6qrc](https://github.com/advisories/GHSA-mhf7-xp6r-6qrc); các gói Symfony được ghim trở lại nhánh `7.4.x` như trước. Không đổi major và không gỡ package khỏi project.
- Đổi giá trị mẫu trong [.env.example](.env.example): production, debug tắt, URL mẫu HTTPS và cookie phiên chỉ gửi qua HTTPS. Giá trị bí mật không được đưa vào báo cáo.
- Chạy `php artisan boost:install --guidelines --no-interaction` theo hướng dẫn dự án; Boost tạo/cập nhật [CLAUDE.md](CLAUDE.md) và [boost.json](boost.json). `AGENTS.md` vẫn giữ nguyên hướng dẫn setup ban đầu.
- Build asset thành công. Vite tạo manifest, không có `public/hot`; có cảnh báo tùy chọn package `fontaine` chưa cài nên fallback font chưa được tối ưu.
- Tạo route/view/event cache; `storage:link` đã tồn tại và trỏ tới `storage/app/public`. Không chạy `config:cache` trên `.env` local để tránh đóng gói cấu hình/khóa của môi trường này.
- Vite đã thay asset CSS có hash cũ; đối chiếu ZIP backup và khôi phục file cũ vào `public/build/`, giữ cả file cũ lẫn build mới. Không chủ ý xóa hay di chuyển file nào khác.

## Kết quả kiểm tra

| Hạng mục | Kết quả |
|---|---|
| PHP/Laravel | PHP `8.3.33`, Laravel `13.17`; project yêu cầu PHP `^8.3`. PHP 8.3 còn hỗ trợ bảo mật đến 31/12/2027. Laravel 13 được sửa lỗi đến Q3/2027 và vá bảo mật đến 17/03/2028 theo [chính sách phát hành Laravel](https://laravel.com/framework/docs/releases); lịch hỗ trợ PHP ở [php.net](https://www.php.net/supported-versions.php). |
| PHP syntax | 211 file PHP thuộc project đã qua `php -l` trong lượt kiểm tra trước; hai file PHP đổi trực tiếp ở lượt hiện tại cũng qua. Vendor và node_modules không tính trong 211 file. |
| Pint | `php vendor/bin/pint --test` — pass. |
| PHPUnit | `php artisan test --no-coverage --compact` — **77/77 pass, 416 assertions**, 0 fail; chạy lại sau build/cache cũng pass. |
| Database | `migrate:fresh --seed` và `migrate:rollback` chạy thành công trên SQLite `:memory:`; DB biến mất cùng tiến trình, không đụng DB thật. |
| Composer/npm audit | `composer audit` và `npm audit` — 0 advisory/vulnerability. `composer validate --no-check-publish` — hợp lệ. |
| Build | `npm run build` — thành công. CSS giao diện chính 99.34 KB (18.44 KB gzip); CSS Filament 701.42 KB (71.53 KB gzip); JS ứng dụng 68.28 KB (23.18 KB gzip). CSS Filament là ứng viên tối ưu đáng xem xét. |
| Production install | `composer install --no-dev ... --dry-run` dự kiến gỡ 38 package dev khỏi `vendor/`; không chạy thật vì thao tác đó xóa package/file cục bộ mà chưa được liệt kê để xác nhận. `composer.json` đã tách `require` và `require-dev`. |
| Website | Trang chủ và chi tiết Corolla Cross trả HTTP 200 sau cache; trình duyệt không ghi console error, ảnh trang chủ không lỗi. Đã kiểm tra ngang ở 360/768/1366/1920 px không tràn ngang; menu mobile mở được. Chọn Corolla Cross 1.8V màu đỏ cho tổng 828 triệu; đổi sang 1.8HEV giữ phụ phí đỏ và tổng 873 triệu. |

**Lighthouse:** chưa chạy vì môi trường không có CLI Lighthouse/Chrome cài đặt được tìm thấy. Không tạo điểm số giả. Cần chạy Lighthouse cho trang chủ, catalog và chi tiết xe sau khi có môi trường phù hợp.

## Việc còn phải hoàn thành trước deploy

1. **CSP chưa được cấu hình.** [SecurityHeaders.php](app/Http/Middleware/SecurityHeaders.php) hiện gửi `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` và gửi HSTS khi production request dùng HTTPS. CSP cần kiểm kê nguồn script/style/frame và kiểm thử Alpine/Livewire/Filament trước khi bật để không làm hỏng giao diện hoặc biểu mẫu.
2. **Chưa thể xác minh Git history:** thư mục không có `.git` và lệnh Git không khả dụng. Có ZIP backup ngoài project [src crm toyota_backup_truoc_khi_lam_sach_full.zip](../src%20crm%20toyota_backup_truoc%20khi%20lam%20sach_full.zip), nhưng không thể chứng minh `.env`/khóa từng bị commit trong lịch sử trước đó. Nếu có lịch sử ở nơi khác, cần quét và đổi khóa nếu phát hiện lộ.
3. PHP 8.5 mặc định trên máy thiếu `intl` và `gd`; PHP 8.3.33 có đủ extension và được dùng chạy test. Server phải bật các extension Laravel/Filament cần (bao gồm `intl`, `gd`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `curl`, `zip`).
4. **Xác nhận dữ liệu showroom:** website local hiển thị đại lý Toyota Kon Tum nhưng bản đồ nhúng đặt tên “Toyota Gia Lai Chi Nhánh Quảng Ngãi” và địa chỉ có tỉnh Quảng Ngãi. Cần chủ dự án xác nhận địa chỉ, vị trí Maps và khu vực phục vụ trước khi gắn domain.
5. Chưa có domain, loại hosting, PHP/MySQL server version và lựa chọn canonical `www` hay không `www`; chưa thể lập DNS/HTTPS hoặc hướng dẫn deploy phù hợp.
6. Chưa chạy PHPStan/Larastan vì project không khai báo/cài công cụ này. Cần bổ sung và thiết lập level phù hợp trước khi xem phân tích tĩnh là hoàn tất.
7. Không chạy production config cache trên máy này, không chạy `composer install --no-dev` thật. Hai lệnh cần chạy ở môi trường deploy/staging riêng sau khi có `.env` production.
8. Chưa thay mật khẩu admin hay nhập khóa/dịch vụ ngoài. Tạo admin an toàn sau deploy, đổi mật khẩu khởi tạo, thiết lập SMTP/queue/cache/backup và chỉ nhập secrets qua secret manager hoặc `.env` server.

## Bảo lưu file, Git và commit

- Không có Git repository và Git CLI không khả dụng nên không thể tạo nhánh `chuan-bi-deploy`, commit riêng, hoặc tag. ZIP backup ở ngoài project là bản lưu dự phòng theo phương án thay thế đã thực hiện trước khi sửa.
- Không xóa hay di chuyển source/data. Một file CSS hash cũ bị Vite thay trong lần build đã được khôi phục từ backup; file build mới vẫn tồn tại.
- `public/build/`, cache và `public/storage` là artifact local. `public/storage` đã là junction tới disk public trước khi kiểm tra `storage:link` báo link tồn tại.

## Hướng xử lý tiếp

1. Xác nhận loại hosting, phiên bản PHP/MySQL, domain và chọn `www` hoặc không `www`.
2. Xác nhận lại dữ liệu Maps/showroom và quyền sử dụng ảnh; cập nhật ảnh thiếu chứng từ trước khi bật hiển thị.
3. Hoàn thiện CSP sau khi chốt các nguồn bên ngoài; chạy security/header test và UI smoke test.
4. Tạo staging với cấu hình độc lập; chạy Composer production install, cache production, Lighthouse và kiểm tra HTTPS/security headers tại staging.
5. Chỉ sau khi các bước trên qua mới viết `DEPLOY.md` theo môi trường server.
