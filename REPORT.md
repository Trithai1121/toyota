# Báo cáo rà soát và kiểm thử Toyota Kon Tum

## Tóm tắt điều hành

- Dự án thực tế dùng PHP 8.3, Laravel 13 và Filament 5; khác với Laravel 11/PHP 8.2+ trong bối cảnh ban đầu.
- Đã bổ sung header bảo mật và mặc định bật Secure cho cookie phiên trong production; thêm test hồi quy và mở rộng kiểm thử lead, máy tính, khuyến mãi.
- Bộ kiểm thử cuối đạt **57/57 tests, 241 assertions**; build frontend thành công; `npm audit --offline` báo **0 vulnerabilities**.
- Luồng trình duyệt từ chọn xe, công cụ trả góp, gửi lead, cảm ơn; đăng nhập CRM, xem lead và mở biểu mẫu sửa xe đã được kiểm tra trên dữ liệu demo trong SQLite biệt lập.
- Chưa thể hoàn tất đo coverage, PHPStan/Larastan, Composer audit, Lighthouse, kiểm thử publish thực tế và xác nhận cấu hình production vì thiếu công cụ/quyền triển khai hoặc cần thông tin từ đại lý.

## Hiện trạng và phạm vi

- Kiểm kê: 40 routes, 20 models, 17 migrations, 2 jobs; không có Events/Listeners.
- Kiểm thử và E2E chỉ dùng SQLite biệt lập (`:memory:` hoặc file tạm trong `%TEMP%`); không kết nối MySQL/MariaDB hoặc CSDL thật.
- `.env` đã tồn tại nên không ghi đè và không chạy `key:generate`. `APP_DEBUG` được kiểm tra ở trạng thái OFF; không hiển thị giá trị secrets.
- Không chạy được `composer install`/`composer audit` do Composer CLI không có trên PATH. Dependencies Composer đã có trong `vendor`; dependencies frontend đã có sẵn nên không cần `npm install`.
- Không có thư mục `.git`: không kiểm tra được lịch sử, không thể tạo commit cho từng lỗi. Không có commit được tạo.

## Kết quả theo giai đoạn

| Giai đoạn | Việc đã làm | Kết quả / giới hạn |
|---|---|---|
| 0 — Nắm hiện trạng | Kiểm kê cấu trúc, phiên bản, routes, models, migrations, jobs; migrate/seed và chạy baseline trong SQLite test. | Baseline: **48 tests, 208 assertions, pass**. PHP 8.3.33; Laravel 13.37.0; Filament 5.10.2. `.env` có sẵn; không đụng CSDL thật. |
| 1 — Phân tích tĩnh | PHP syntax lint, Pint trên các file thay đổi, quét dependencies frontend và vùng source/config liên quan. | 146 PHP files không lỗi cú pháp. `npm audit --offline`: 0 vulnerabilities. Không có PHPStan/Larastan và Composer CLI; không thể chạy static analysis cấp 6 hoặc `composer audit`. Không có Git để quét lịch sử. Pint trên các file thay đổi thành công; lỗi import ordering cũ ở `routes/console.php` chưa sửa vì ngoài phạm vi thay đổi. |
| 2 — Bảo mật | Thêm middleware header bảo mật; bật HSTS chỉ ở production qua HTTPS; sửa mặc định Secure cookie theo production; thêm test hồi quy. Rà soát MFA, throttle, honeypot, CSRF, consent, export và upload. | Header: `nosniff`, `DENY`, `strict-origin-when-cross-origin`, `Permissions-Policy`; HSTS có điều kiện. Session cookie giữ `HttpOnly=true`, `SameSite=lax`; Secure mặc định true khi `APP_ENV=production`, vẫn cho phép cấu hình override. |
| 3 — Kiểm thử nghiệp vụ | Thêm test consent bắt buộc, giá âm, lãi suất và trả góp, promotion hết hạn, header và cấu hình session production. | Suite cuối: **57/57 tests, 241 assertions**. Không có PCOV/Xdebug nên không lấy được coverage; mục tiêu 70% chưa xác minh. |
| 4 — Giao diện/E2E | Kiểm tra 390/768/1360px; form và ảnh trên trang chủ; luồng xe → trả góp → lead → cảm ơn; đăng nhập admin → danh sách lead → mở form sửa xe. | Không thấy tràn ngang; form không có input thiếu nhãn, trang chủ không có ảnh thiếu alt; thanh liên hệ nhanh hiện trên mobile. Luồng gửi lead và CRM hoạt động trên dữ liệu tổng hợp. Chưa thực hiện thao tác lưu/sửa hoặc xuất bản trang trong admin; không cài Playwright/Dusk hoặc axe vào dự án. |
| 5 — Hiệu năng | Build production frontend; rà soát mã liên quan và kiểm tra responsive. | `npm run build` thành công. Plugin fonts cảnh báo thiếu gói tùy chọn `fontaine`; không chặn build. Không có phép đo Lighthouse, LCP/CLS/INP, thời gian tải hoặc số truy vấn trước/sau, nên không tuyên bố đã đạt ngưỡng hiệu năng. |
| 6 — Sửa lỗi/dọn mã | Sửa Secure cookie production và vùng chạm nút cài đặt cookie ở footer; định dạng PHP thay đổi; tạo báo cáo này. | Hai lỗi đã có test tái hiện trước khi sửa. Không có commit do không phải Git repository. |
| 7 — Trước khi ra mắt | Đối chiếu checklist triển khai và các tính năng production. | Các mục cần người vận hành/đại lý xác nhận được liệt kê bên dưới; không thực hiện thay đổi production. |

## Bảng phát hiện

| Mức độ | Phát hiện / nguyên nhân gốc | Trạng thái | Tham chiếu |
|---|---|---|---|
| Cao | Cookie phiên không được đặt Secure mặc định trong production nếu không khai báo `SESSION_SECURE_COOKIE`. | **Đã sửa** — production nay mặc định Secure; có test cấu hình hồi quy. | [config/session.php](./config/session.php) |
| Trung bình | Nút cài đặt cookie ở footer chỉ cao khoảng 20px do thiếu utility vùng chạm. | **Đã sửa** — dùng `.touch-target` (min-height/min-width 44px); có test render. | [site.blade.php](./resources/views/layouts/site.blade.php), [app.css](./resources/css/app.css) |
| Trung bình | Chưa thấy chống gửi trùng theo số điện thoại trong cửa sổ thời gian; throttle hiện có chủ yếu giới hạn theo IP. | **Chưa sửa** — cần xác nhận khoảng thời gian, cách xử lý yêu cầu khác nhau từ cùng số và thông báo cho khách. | [LeadController.php](./app/Http/Controllers/LeadController.php) |
| Trung bình | Chưa thấy quy tắc tự gán nhân viên và lịch sử trạng thái/hoạt động lead theo yêu cầu CRM. | **Chưa sửa** — cần xác nhận chiến lược phân công, quyền xem và dữ liệu cần lưu trong timeline. | [LeadController.php](./app/Http/Controllers/LeadController.php), cấu hình CRM |
| Trung bình | Chưa thấy lịch sử giá phiên bản và điều kiện khuyến mãi theo phiên bản xe; hiện khuyến mãi gắn với xe. | **Chưa sửa** — cần xác nhận mô hình giá, cách áp dụng ưu đãi và quy tắc hiệu lực trước khi đổi schema. | [migration danh mục xe](./database/migrations/2026_10_19_030000_create_vehicle_catalog_tables.php) |
| Thấp | Chưa cấu hình CSP. | **Chưa sửa** — cần thiết kế policy tương thích với inline Alpine/Filament và tích hợp môi trường triển khai; không áp dụng policy suy đoán gây hỏng giao diện quản trị. | [SecurityHeaders.php](./app/Http/Middleware/SecurityHeaders.php) |
| Thấp | Pint từng phát hiện lỗi import ordering có sẵn ngoài các file thay đổi. | **Chưa sửa** — không chỉnh file ngoài phạm vi. | [console.php](./routes/console.php) |
| Thấp | Không thể kiểm tra coverage, PHPStan/Larastan, `composer audit` hoặc Git history. | **Chưa xác minh** — thiếu công cụ/metadata, không phải kết quả audit sạch. | Môi trường chạy |

Không phát hiện mức **Nghiêm trọng** trong các vùng mã đã rà soát. Đây không phải chứng nhận rằng toàn bộ ứng dụng hoặc hạ tầng production không có lỗ hổng.

## So sánh trước và sau

| Chỉ số | Trước | Sau | Ghi chú |
|---|---:|---:|---|
| PHPUnit | 48 tests / 208 assertions | **57 tests / 241 assertions** | Toàn bộ suite cuối pass. |
| Header bảo mật | Chưa có middleware chung | Có 4 header cơ bản; HSTS theo production + HTTPS | Có test cho header và điều kiện HSTS. |
| Secure cookie mặc định production | Không bảo đảm nếu thiếu env | **Bật mặc định** | Có test chứng minh fail trước/success sau. |
| Vùng chạm nút cài đặt cookie footer | Khoảng 20px | **44px tối thiểu** | Dùng utility `.touch-target` đã có. |
| Responsive | Chưa có số đo trong lượt audit này | Không tràn ngang tại 390/768/1360px | Đã kiểm tra trang chủ; thanh liên hệ nhanh hiện ở mobile. |
| Frontend build | Chưa đo trong lượt audit này | **Thành công** | Có cảnh báo `fontaine` tùy chọn, build không lỗi. |
| Lighthouse/coverage/truy vấn | Chưa đo | Chưa đo | Không bịa số liệu; cần tooling và cấu hình phù hợp. |

## Checklist ra mắt

| Hạng mục | Trạng thái | Ghi chú |
|---|---|---|
| PHPUnit pass | **Đạt** | 57/57 tests, 241 assertions. |
| Không có lỗi console trong luồng lead đã thử | **Đạt** | Luồng trình duyệt dùng dữ liệu demo và queue DB; không gửi email/Telegram thật. |
| APP_DEBUG=false trong production | **Cần xác nhận** | `.env` hiện tại được kiểm tra OFF, nhưng chưa xác minh cấu hình production đang triển khai. |
| APP_ENV=production, khóa mới và mật khẩu admin mới | **Cần xác nhận** | Không chạy `key:generate`, không đổi mật khẩu tài khoản thật. |
| MFA admin bật trên production | **Cần xác nhận** | Cấu hình mặc định đã rà soát; MFA được tắt riêng trong browser test để dùng tài khoản demo. |
| HTTPS, redirect domain và HSTS thực tế | **Cần xác nhận** | Chỉ kiểm tra điều kiện HSTS ở test; chưa kiểm tra domain/proxy production. |
| Sitemap/robots | **Đạt trong test** | Có test route và nội dung; chưa kiểm tra domain production. |
| Pixel/GA4/GTM, đồng ý cookie, thông báo thật | **Cần xác nhận** | Không có credentials production; không gửi sự kiện hoặc thông báo ra ngoài. |
| Backup và thử khôi phục; giám sát lỗi/cảnh báo | **Cần xác nhận** | Ngoài phạm vi môi trường local. |
| Gỡ dữ liệu demo và thay placeholder | **Cần xác nhận** | Dữ liệu audit dùng SQLite tạm đã xóa; placeholder giao diện vẫn cần đại lý cung cấp dữ liệu chính thức. |

## Cần xác nhận

1. Thời gian chống trùng số điện thoại và xử lý khi cùng khách gửi yêu cầu khác nhau.
2. Quy tắc tự gán lead (chia đều, theo khu vực, theo ca trực hay thủ công), quyền xem và trường timeline cần lưu.
3. Có cần quản lý lịch sử giá và khuyến mãi theo phiên bản hay không; nguồn dữ liệu và quy tắc hiệu lực.
4. Chính sách CSP phù hợp với Filament/Alpine, domain script/font/map/analytics và reverse proxy production.
5. Các thông tin đại lý cần thay placeholder: logo/ảnh được cấp quyền, tên pháp lý, địa chỉ, hotline/Zalo, giá, ưu đãi, điều kiện và hạn áp dụng, liên kết PDF, phản hồi dự kiến.
6. Thông tin triển khai: domain canonical/www, HTTPS, secrets/tracking, backup, giám sát và xác nhận MFA.

## Lệnh kiểm thử/build chính

- `vendor/bin/phpunit --no-coverage` — **57 tests, 241 assertions, pass**.
- `vendor/bin/pint --format agent ...` — pass trên các file PHP thay đổi.
- `npm.cmd run build` — pass.
- `npm.cmd audit --offline` — 0 vulnerabilities.
- PHP lint toàn bộ 146 file — pass ở Giai đoạn 1.
- `composer audit`, PHPStan/Larastan, Lighthouse và coverage — không chạy được do thiếu Composer/static-analysis/coverage tooling hoặc browser audit tooling.
