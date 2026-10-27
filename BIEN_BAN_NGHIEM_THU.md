# BIÊN BẢN NGHIỆM THU KỸ THUẬT ĐỘC LẬP

**Dự án:** Website bán xe Toyota Kon Tum (Laravel)  
**Ngày kiểm tra:** 24/10/2026  
**Môi trường:** workspace Windows; website local `http://127.0.0.1:8081`; PHP 8.5.0; Laravel 13.37.0.  
**Phạm vi:** kiểm kê, kiểm tra mã nguồn/dependency, test hiện có, migration SQLite tách biệt, HTTP và giao diện local, rà soát bảo mật mã nguồn. Không sửa mã ứng dụng, không gửi form, không chạy CRUD hay migration trên MySQL của website.

## 1. Kết luận nghiệm thu

### **TỪ CHỐI KÝ NHẬN KỸ THUẬT Ở THỜI ĐIỂM KIỂM TRA**

Chưa đủ bằng chứng để bên nhận chịu trách nhiệm vận hành production. Đây không phải kết luận rằng toàn bộ website không chạy được: nhiều trang trả 200, migration/seed/rollback SQLite thành công, và bộ chọn màu Corolla Cross hoạt động đúng. Các điểm chặn chính:

1. Workspace không có thư mục `.git`; không thể xác minh repo nguồn chính thức, lịch sử commit, nhánh/tag, tính đầy đủ của mã nguồn hoặc quét bí mật trong lịch sử.
2. Cài dependency sạch không đạt: `composer install --dry-run` dừng vì PHP CLI thiếu `ext-intl`; GD cũng thiếu và làm nhiều test upload ảnh không chạy.
3. `php artisan test`: **59 pass, 5 fail, 13 error trên 77 test**; lỗi tính trả góp có biểu hiện thật trên route công khai.
4. `composer audit` phát hiện Livewire 4.4.7 thuộc dải bị advisory GHSA mức Medium; chưa có bằng chứng phiên bản đã được vá trong lockfile.
5. Không có bằng chứng bàn giao hosting/domain/DNS, tài khoản quản trị, backup đã khôi phục thử, quyền ảnh Toyota hoặc cấu hình production thực tế.

Chỉ xem xét ký lại sau khi hoàn tất điều kiện bắt buộc ở mục 5 và cung cấp bằng chứng kiểm tra lại trên staging/DB thử.

## 2. Điểm theo mảng

| Mảng | Điểm | Cơ sở |
|---|---:|---|
| A. Kiểm kê bàn giao | D | Có mã nguồn và tài liệu trong workspace, nhưng thiếu Git metadata, quyền truy cập và bằng chứng tài sản vận hành. |
| B. Dựng lại | D | Migration/seed/rollback trên SQLite memory đạt; cài Composer sạch bị chặn bởi `ext-intl`; GD/Intl không có. |
| C. Mã nguồn | C | `php -l` đạt; kiến trúc Laravel có phân lớp; Pint báo 19 file; không có PHPStan/Larastan. |
| D. Chức năng | C- | Các route nội dung và trang xe trả 200; Corolla Cross đạt; calculator trả góp lỗi; admin CRUD chưa được thử. |
| E. Dữ liệu | C | Kiểu giá là số nguyên; màu/phụ phí Corolla Cross đúng trên website; chưa xác minh toàn bộ giá với đại lý/Toyota và chưa thử restore backup. |
| F. Bảo mật | C- | Có auth/roles, validation, throttling và một số security headers; có advisory Livewire, thiếu CSP trong response, production chưa kiểm chứng. |
| G. Hiệu năng/SEO | D | Sitemap/robots và metadata nền có; tiêu đề home hiện chứa “Laravel”; chưa đo Lighthouse; chưa lập chỉ số query. |
| H. Vận hành | F | Tài liệu mô tả quy trình nhưng chưa có chứng cứ hosting, deploy thực tế, backup, queue/scheduler đang hoạt động hoặc quy trình rollback. |

## 3. Bảng kiểm kê bàn giao

| Hạng mục | Trạng thái | Bằng chứng / nhận xét |
|---|---|---|
| Mã nguồn và dependency lock | **Có, chưa chứng minh đầy đủ** | Có `app/`, `routes/`, `database/`, `resources/`, `public/`, `composer.lock`, `package-lock.json`; workspace không có `.git`. `git status --short` không chạy do Git không có trong PATH; không thể xác minh lịch sử/nhánh/tag. |
| Tài liệu cài đặt/triển khai | **Có** | Có [README.md](./README.md), [HUONG-DAN-XAMPP-VA-DEPLOY.md](./HUONG-DAN-XAMPP-VA-DEPLOY.md), [QA-TEST-PLAN.md](./QA-TEST-PLAN.md), [UI-DESIGN.md](./UI-DESIGN.md), `REPORT.md`. Hướng dẫn có các bước backup, cron, queue, deploy VPS/cPanel. |
| Sơ đồ kiến trúc/nghiệp vụ | **Thiếu / chưa thấy** | Không thấy sơ đồ thành phần, ERD hoặc tài liệu mô tả luồng nghiệp vụ độc lập. Cần bàn giao cùng người phụ trách nghiệp vụ. |
| Hướng dẫn người dùng admin | **Thiếu / chưa thấy** | README mô tả khu vực admin ở mức tổng quan; chưa thấy manual theo thao tác, quy trình duyệt giá/ảnh/nội dung, xử lý lead, khôi phục revision. |
| `.env.example` | **Có** | Có biến APP, DB, mail, admin ban đầu, dealer, tracking và queue. Mẫu dùng `APP_ENV=local`, `APP_DEBUG=true`; tài liệu production yêu cầu ghi đè. Mẫu không cho thấy token thật. |
| Bí mật và tài khoản bên thứ ba | **Không rõ** | `.env` tồn tại, bị `.gitignore` loại trừ; APP_KEY và mail password được cấu hình nhưng không ghi giá trị trong biên bản. Không có danh mục chứng minh quyền hosting, domain/DNS, SMTP, Zalo, Google/Meta, storage hay API đã chuyển giao. |
| Hosting/domain/DNS/SSL | **Không rõ** | Chỉ kiểm tra được website local trên `127.0.0.1:8081`. Tài liệu đưa ra ví dụ XAMPP/Nginx/cPanel, không chứng minh server/domain thật đang thuộc quyền bên nhận. |
| Cron/queue/giám sát | **Có hướng dẫn, chưa chứng minh vận hành** | README và deployment guide nêu scheduler mỗi phút, queue worker, backup/log rotation. Không có cấu hình supervisor/cron thật hoặc dashboard/cảnh báo được bàn giao. |
| Backup DB và upload | **Không rõ** | Không thấy file backup trong các thư mục public/storage được rà; tài liệu có quy trình backup và yêu cầu thử restore. Chưa có bản backup đã xác minh hoặc biên bản restore. |
| Admin và phân quyền | **Không rõ** | `/admin` chuyển hướng đến login (302). Không có thông tin tài khoản để thử đăng nhập, 2FA, CRUD hoặc đổi màu. Không tìm thấy mật khẩu mặc định trong tài liệu đã xem. |
| License package | **Có một phần** | `composer.json` khai báo MIT; `composer licenses` ghi nhận MIT/BSD/Apache và một số dependency có GPL-2.0-only/GPL-3.0-only (các gói Nette). Cần pháp chế xác nhận tương thích phân phối thương mại và rà license npm/font. |
| Quyền ảnh/logo/font/nội dung Toyota | **Không rõ** | Không tìm thấy hồ sơ cấp phép/nguồn quyền sử dụng đi kèm. Nhiều nội dung/ảnh gắn nhãn nguồn Toyota Việt Nam; đường dẫn nguồn không thay thế giấy phép. Yêu cầu bên giao cung cấp tài liệu quyền sử dụng. |
| SKD/CodeIgniter cũ | **Chưa thấy dấu vết trong source đã rà** | Tìm `CodeIgniter`, `CI_Controller`, `CI_VERSION`, `system/core`, `application/controllers`, `skd` trong `app/routes/resources/database/tests/bootstrap/config` và tài liệu chính không có kết quả liên quan. Không thể kiểm tra Git history vì thiếu `.git`. |

## 4. Kiểm tra đã thực hiện và kết quả

### 4.1 Dựng lại và cấu hình

| Lệnh/kiểm tra | Kết quả |
|---|---|
| `php -v` | PHP 8.5.0; Laravel yêu cầu PHP `^8.3`. PHP 8.5 là nhánh còn hỗ trợ tại thời điểm kiểm tra, nhưng bản CLI đang là `.0`, cần cập nhật patch mới nhất trước production. |
| `composer -V` | Composer 2.8.12. |
| `php artisan --version` | Laravel Framework 13.37.0. Laravel 13 đang trong thời hạn hỗ trợ theo [bảng phát hành chính thức](https://laravel.com/framework/docs/releases); PHP 8.5 support theo [PHP supported versions](https://www.php.net/supported-versions.php). |
| `composer validate --no-check-publish` | Đạt: `composer.json` hợp lệ. |
| `composer install --dry-run --no-scripts --no-interaction` | **Không đạt**: `filament/support` cần `ext-intl`; PHP CLI hiện không có extension này. Đây là blocker dựng sạch, không phải lỗi lockfile syntax. |
| `npm ci --dry-run --ignore-scripts --no-audit --no-fund` | Đạt: lockfile giải quyết dependency. |
| `npm run build` | Build Vite đạt; cảnh báo tùy chọn thiếu `fontaine` cho optimized font fallback. Output có CSS admin khoảng 701 KB trước gzip. |
| `php artisan migrate:fresh --seed` + `migrate:rollback` | Đạt trong **một tiến trình Laravel dùng SQLite `:memory:`**, tất cả migration lên/seed/rollback thành công; không kết nối MySQL đang dùng. |
| Dựng lại hoàn toàn theo tài liệu | **Chưa chứng minh sạch**: dependency đã có sẵn trong workspace; dry-run Composer thất bại do extension. Không chạy `key:generate` hoặc seeder trên DB ứng dụng. |
| Thời gian dựng lại <30 phút | **Chưa đạt bằng chứng**; có thể đạt sau khi bên nhận có PHP đúng extension, DB rỗng, credential và quyền deploy. |

### 4.2 Kiểm kê ứng dụng

- `php artisan route:list`: **63 route**. Public: `/`, `/xe-toyota`, `/xe-toyota/{slug}`, thông số kỹ thuật xe, `/tin-tuc`, `/tin-tuc/{slug}`, `/cong-cu/tra-gop`, `/cong-cu/chi-phi-lan-banh`, `/bao-duong`, `/phu-tung-phu-kien`, `/gioi-thieu`, `/lien-he`, chính sách, `/trang/{slug}`, landing page `/lp/{slug}`, đăng ký/thank-you, sitemap, robots, cookie consent. Admin: login/logout, dashboard, giao diện/theme/settings, pages/revisions, posts/promotions, leads/notes, users, vehicle lines/vehicles/variants/colors/images/specs/fees, campaign, UI strings, exports/imports. Có route framework Livewire, health check và storage.
- Controller ứng dụng: `CalculatorController`, `CampaignLandingPageController`, `CookieConsentController`, `HomeController`, `LeadController`, `PagePreviewController`, `PostController`, `SitemapController`, `StaticPageController`, `VehicleController`, `Controller`.
- Middleware ứng dụng: `SecurityHeaders`; Filament cấu hình session, CSRF/request forgery và `Authenticate` cho panel.
- Model: `BlockPreset`, `CampaignLandingPage`, `Lead`, `LeadNote`, `Page`, `PageBlock`, `PageDraft`, `PageRevision`, `Post`, `Promotion`, `Setting`, `SiteSetting`, `Spec`, `ThemeSetting`, `TrackingSettingAudit`, `UiString`, `User`, `Variant`, `Vehicle`, `VehicleCategory`, `VehicleColor`, `VehicleImage`, `VehicleLine`, `VehicleOnRoadFee`, `VehicleVariantColor`.
- Có 29 migration, 12 seeder, 25 Blade view; các view gồm home, calculators, campaigns, components, emails, Filament, layouts/partials, leads, pages, posts, vehicles/specifications.
- Package chính: PHP `^8.3`; Laravel `^13.17`; Filament `^5.0`; Spatie Permission `^7.0`; dev gồm Boost, Pint, PHPUnit 12.5, Collision, Faker, Pail/Pao. Frontend gồm Vite 8, Tailwind 4, Alpine 3, Laravel Vite plugin 3.

### 4.3 Mã nguồn và test

- `php -l` trên PHP trong `app`, `routes`, `database`, `tests`, `config`, `bootstrap`, `public`: không phát hiện lỗi cú pháp.
- `vendor/bin/pint --test`: **fail 19 file** (line ending, import thừa/sắp xếp, khoảng trắng/toán tử, blank line). Đây là chuẩn hóa, chưa chứng minh lỗi runtime.
- Không cài PHPStan/Larastan; phân tích kiểu tĩnh level cao nhất chưa thực hiện được.
- `php artisan test`: **77 test, 59 pass, 5 fail, 13 error, 286 assertions**. Coverage không thu được; PHP hiện không có Xdebug/PCOV và `phpunit.xml` không cấu hình coverage driver.
- Không thêm test mới: yêu cầu nghiệm thu đồng thời cấm sửa code trong lượt này. Những test còn thiếu/không chạy cần bổ sung sau khi có phê duyệt sửa.
- Fail đã định danh: 2 test installment (yêu cầu `variant_id` làm 302 và che lỗi `price`), 1 test on-road fee dùng fixture `Setting` cũ trong khi service hiện đọc bảng per-variant, 1 test seeder còn kỳ vọng 3 vehicle lines nhưng seed thực tế có 17, 1 test kỳ vọng Product schema cho model chưa có dữ liệu verified nhưng template không xuất schema trong trường hợp đó.
- 13 error là lỗi môi trường: thiếu GD gây `imagecreatetruecolor()`/ảnh test; thiếu Intl làm Filament format/table render lỗi.

### 4.4 Website local, giao diện và SEO

| Trang/kiểm tra | Kết quả |
|---|---|
| Trang chủ, `/xe-toyota`, 17 URL xe từ catalog | HTTP 200. |
| `/bao-duong`, `/phu-tung-phu-kien`, `/cong-cu/chi-phi-lan-banh`, `/tin-tuc`, `/lien-he`, `/robots.txt`, `/sitemap.xml` | HTTP 200. |
| `/cong-cu/tra-gop` | HTTP 302 quay lại trang trước cùng lỗi `The variant id field is required.`; trình duyệt không vào được màn tính khi đi từ menu không có variant. |
| `/admin` | HTTP 302 đến luồng đăng nhập; đây là hành vi mong đợi khi chưa đăng nhập, nhưng chưa thử role/CRUD. |
| URL không tồn tại | 404. `/.env`, `/.git/config`, `/vendor/autoload.php`, `/telescope`, `/_debugbar/open` trả 404; `/storage/logs/laravel.log` trả 403. |
| Corolla Cross | Trình duyệt hiển thị **2 phiên bản, 5 màu**; Đỏ (3U5) và Trắng ngọc trai (089) đều **+8.000.000 VNĐ**; giá/ảnh lựa chọn cập nhật trên trang. |
| Popup liên hệ | Click “Liên hệ” mở menu; thấy Gọi hotline, Liên hệ Zalo và Nhận tư vấn/báo giá. Không thấy 3 nút nổi cũ trong snapshot. |
| Navbar | Có menu xe/dịch vụ/công cụ/tin tức/liên hệ; CSS runtime đặt `--navbar-bg` theo `--footer-bg`. Không kiểm thử lưu màu trong admin vì không có account test. |
| Responsive | Kiểm tra viewport 360/768/1366/1920; không ghi nhận nội dung tràn ngang. Console browser không ghi nhận lỗi JavaScript trong lượt kiểm tra popup. |
| Ảnh | Bốn URL ảnh xe mẫu trả 200 với `image/png`/`image/webp`; kiểm tra trình duyệt lazy-load chưa kết thúc nên chưa xác nhận toàn bộ ảnh. |
| Sitemap/robots/meta | Sitemap và robots trả 200; layout có description, canonical, OG title/description/type/url. Không đo Lighthouse do Lighthouse/Pa11y không có trong `node_modules/.bin`. |
| Homepage title | Snapshot hiển thị `Toyota Kon Tum | Laravel`; cần sửa APP_NAME của môi trường hoặc cách ghép title. |
| Bản đồ showroom | Snapshot trình duyệt cho iframe ra `about:invalid#zClosurez`; link Maps ngoài vẫn có. Cần xác minh trên staging vì có thể do hạn chế browser/môi trường. |

**Lighthouse:** chưa có điểm Performance/Accessibility/SEO/Best Practices; không cài thêm công cụ trong phạm vi chỉ đọc. Không suy diễn điểm số. CSS admin build lớn 701 KB chưa gzip; cần đo riêng admin/public trước tối ưu. Query timing/N+1 chưa được profiling bằng DB thật; code có eager loading ở các luồng catalog chính.

## 5. Bảng lỗi và rủi ro

Ước lượng là công sức kỹ thuật ban đầu, chưa gồm thời gian chờ quyền truy cập/chủ sở hữu dữ liệu.

| Mã | Mức | Mảng | Vị trí / bằng chứng | Mô tả và tái hiện | Đề xuất sửa | Công | Trách nhiệm |
|---|---|---|---|---|---|---:|---|
| ACC-01 | **High** | A | Root workspace; không có `.git` trong `Get-ChildItem -Force` | Không có repo/history/branch/tag để xác nhận provenance, file bị thiếu hoặc secret từng commit. | Bàn giao URL repo chính thức, quyền read/write phù hợp, commit/tag phát hành, submodule/LFS nếu có; quét lịch sử bí mật trước khi ký. | 0.5–1 ngày | Bên giao trước ký |
| BUILD-01 | **High** | B | `composer install --dry-run`; `ext-intl` absent; [HUONG-DAN-XAMPP-VA-DEPLOY.md](./HUONG-DAN-XAMPP-VA-DEPLOY.md#L5) | Cài sạch không qua platform check; các test Filament cũng error. | Cung cấp runtime PHP có Intl (và GD/WebP theo chức năng upload); cập nhật checklist extension trong tài liệu; chứng minh `composer install` sạch. | 0.5 ngày | Bên giao trước ký |
| SEC-01 | **Medium** | F | `composer audit`; package lock `livewire/livewire` 4.4.7 | Advisory GHSA-mhf7-xp6r-6qrc mức Medium; dải ảnh hưởng kết thúc trước 4.4.8 theo advisory Composer. | Nâng Livewire lên bản đã vá tương thích Filament/Laravel; chạy test/audit lại; [advisory](https://github.com/advisories/GHSA-mhf7-xp6r-6qrc). | 0.5–1 ngày | Bên giao trước ký |
| CALC-01 | **High** | D | [CalculatorController.php](./app/Http/Controllers/CalculatorController.php#L23), [installment.blade.php](./resources/views/calculators/installment.blade.php#L18) | Vào `/cong-cu/tra-gop` từ menu khi không có `variant_id`: controller validate `required`, trả 302 và lỗi “variant id field is required”; UI lại ghi phiên bản là tùy chọn. Hai test installment fail. | Làm cho variant thực sự tùy chọn hoặc route chọn xe trước có hướng dẫn rõ; không redirect người dùng về trang trước với lỗi validation không liên quan. | 0.5–1 ngày | Bên giao trước ký |
| CALC-02 | **Medium** | D | [CalculatorController.php](./app/Http/Controllers/CalculatorController.php#L23), [installment.blade.php](./resources/views/calculators/installment.blade.php#L29) | Blade có ô `price`, nhưng rules không validate `price` và controller lấy giá chỉ từ `$variant?->list_price`; giá người dùng nhập bị bỏ qua. Test giá âm không nhận được lỗi `price` vì dừng ở variant ID. | Chốt nghiệp vụ: hoặc bỏ ô nhập và khóa giá theo phiên bản, hoặc validate giá trong khoảng hợp lệ và dùng giá đã validate. Bổ sung test zero/âm/quá lớn. | 0.5 ngày | Bên giao trước ký |
| DATA-01 | **Medium** | E | [OnRoadFeeSettings.php](./app/Services/OnRoadFeeSettings.php#L29), [CalculatorTest.php](./tests/Feature/CalculatorTest.php#L99) | Khi bảng `vehicle_on_road_fees` có mặt và có `variant_id`, service chỉ lấy cấu hình per-variant; test cũ ghi `Setting` nên không tạo kết quả mong đợi. Seed mới không chứng minh phí có dữ liệu đầy đủ. | Cập nhật fixture/test theo schema hiện tại; xuất danh sách vùng/variant đã có phí được kiểm chứng, nguồn và ngày; quyết định có fallback legacy hay không. | 1 ngày | Bên giao trước ký |
| TEST-01 | **Medium** | C | [DatabaseSeederTest.php](./tests/Feature/DatabaseSeederTest.php#L20), [ToyotaCatalogSeeder.php](./database/seeders/ToyotaCatalogSeeder.php#L20) | Seeder test mong đợi 3 dòng xe/0 xe; seeder hiện tạo catalog 17 models. Test suite đỏ dù migration/seed thành công. | Đồng bộ mục tiêu seeder/test; tách test catalog thật khỏi test mẫu nếu yêu cầu nghiệp vụ đã đổi; đặt ngưỡng test pass rõ trước bàn giao. | 0.5 ngày | Bên giao trước ký |
| SEO-01 | **Low** | G | [show.blade.php](./resources/views/vehicles/show.blade.php#L25), [PublicSeoTest.php](./tests/Feature/PublicSeoTest.php#L133) | Test yêu cầu Product schema không Offer cho model rỗng; template chỉ xuất schema khi có verified content/ảnh/variant. Hiện là bất đồng giữa test và contract. | Chốt SEO acceptance criteria; nếu published vehicle phải có Product schema tối thiểu, xuất `name/brand/url` không bịa Offer; nếu không, sửa test theo chính sách publish. | 0.5 ngày | Bên giao trước ký |
| SEO-02 | **Low** | G | [home.blade.php](./resources/views/home.blade.php#L3) | Home thực tế có title `Toyota Kon Tum | Laravel`; cấu hình app-name môi trường local không mang thương hiệu. | Đặt APP_NAME production là tên thương hiệu và rà tránh lặp thương hiệu trong title; kiểm tra tất cả page title. | 0.25 ngày | Bên giao trước ký |
| SEC-02 | **Medium** | F/H | `.env.example` APP_DEBUG=true; `php artisan about` xác nhận môi trường local/debug bật | Đây là cấu hình local, không chứng minh production bị bật debug; nhưng nếu sao chép mẫu mà không ghi đè, production có nguy cơ lộ exception. APP_KEY hiện được cấu hình nhưng không kiểm tra độ an toàn giá trị. | Cung cấp bằng chứng production `APP_ENV=production`, `APP_DEBUG=false`, APP_KEY riêng; kiểm tra trang 500 không lộ stack trace/đường dẫn. Không gửi secret vào biên bản. | 0.5 ngày | Bên giao trước ký |
| SEC-03 | **Low** | F | [SecurityHeaders.php](./app/Http/Middleware/SecurityHeaders.php#L20) và response HTTP | X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy có; CSP không có trên trang thường; response local lộ `X-Powered-By: PHP/8.5.0`. HSTS chỉ được code set khi production+HTTPS, nhưng local là HTTP. | Lập CSP phù hợp sau khi inventory script/font/iframe; tắt header phiên bản ở web server production; xác minh HSTS trên HTTPS staging. | 0.5–1 ngày | Bên giao trước ký |
| OPS-01 | **High** | A/H | Không có bằng chứng ngoài workspace | Chưa xác minh backup/restore, server/domain/DNS/SSL, cron/queue, monitoring, quyền storage/cache, credential chủ sở hữu. Tài liệu mô tả thủ tục không chứng minh đã vận hành. | Bàn giao danh mục tài sản + quyền, bản backup DB và media, biên bản restore trên DB mới, deploy/rollback record, cron/worker/log alert. | 1–2 ngày | Bên giao trước ký |
| LEGAL-01 | **Medium** | A/E | Không thấy hồ sơ quyền ảnh/nội dung/font trong workspace đã rà | Không thể chứng minh quyền thương mại cho ảnh xe/logo/font/nội dung lấy từ Toyota hoặc bên thứ ba. | Bàn giao license/authorization, nguồn, phạm vi, thời hạn và người cấp; thay tài sản không có căn cứ. | Theo số lượng asset | Bên giao trước ký |
| DATA-02 | **Medium** | E/H | [Lead.php](./app/Models/Lead.php#L8), [LeadPolicy.php](./app/Policies/LeadPolicy.php#L8) | Tên, số điện thoại, ghi chú và đồng ý marketing được lưu trong bảng leads; policy giới hạn admin/sale theo phân công, nhưng chưa có bằng chứng retention, encryption-at-rest của DB/backup, quy trình xóa/ẩn danh. | Bàn giao vị trí DB/backup, retention và quy trình xử lý yêu cầu dữ liệu cá nhân; xác nhận encryption/ACL ở hạ tầng. | 1 ngày | Bên giao trước ký |
| QA-01 | **Medium** | D/G | 5 fail + 13 error từ `php artisan test` | Bộ test chưa là cổng nghiệm thu xanh; coverage và Lighthouse chưa đo; admin CRUD/2FA chưa thử do không có tài khoản test. | Bên giao cung cấp staging credentials/test account, sửa test/logic, chạy suite trên PHP có Intl/GD; đính kèm coverage và test matrix. | 2–4 ngày | Bên giao trước ký |

## 6. Điều kiện bắt buộc trước khi ký nhận

1. Bàn giao repository chính thức, quyền truy cập, commit/tag phát hành và xác nhận không thiếu asset/submodule/upload cần thiết.
2. Cấp danh mục tài sản và quyền quản trị (hosting, domain/DNS, SSL, DB, SMTP, Zalo, GA4/GTM/Meta, file storage) qua password manager; luân chuyển credential sau ký.
3. Cung cấp PHP runtime có Intl/GD/WebP và chứng minh cài sạch qua Composer; `npm ci` và `npm run build` phải đạt từ checkout sạch.
4. Xử lý Livewire advisory; `composer audit` không còn advisory áp dụng hoặc có biên bản chấp nhận rủi ro cụ thể.
5. Sửa calculator trả góp và chốt cách dùng giá; đồng bộ tests/service phí lăn bánh/seeder/SEO schema; test suite phải đạt trên runtime đã hỗ trợ.
6. Bàn giao DB backup và uploaded-media backup, khôi phục thành công lên database/máy thử riêng, đối chiếu số bảng/media và xác nhận quy trình rollback.
7. Chứng minh cấu hình production an toàn: APP_DEBUG off, key riêng, HTTPS/HSTS/cookie secure, document root là `public`, không lộ `.env`/log/backup, giới hạn quyền ghi.
8. Cấp account staging có từng vai trò admin/marketing/sale để thử phân quyền, 2FA, CRUD và đổi màu; chỉ dùng dữ liệu giả.
9. Ký xác nhận catalog xe/giá/phụ phí/phí lăn bánh/địa chỉ/hotline và quyền sử dụng từng nhóm ảnh/nội dung.
10. Giao quy trình vận hành đã chạy thật: cron scheduler, queue worker/supervisor, log/alert, backup retention, cập nhật dependency và người chịu trách nhiệm.

## 7. Câu hỏi cần bên giao trả lời

1. URL repo nào là nguồn chuẩn? Commit/tag nào tương ứng website `127.0.0.1:8081` và production hiện tại? Vì sao workspace nghiệm thu không có `.git`?
2. Production đang ở nhà cung cấp nào, ai đứng tên domain/DNS, SSL, database, SMTP, Zalo và Google/Meta? Có tài khoản owner bàn giao không?
3. Backup DB và `storage/app/public` gần nhất ở đâu, lịch backup/retention là gì, lần restore gần nhất ngày nào và ai xác nhận?
4. PHP phiên bản nào được Apache/FPM thực sự nạp? Extension Intl/GD/WebP đã bật trên CLI lẫn web SAPI chưa?
5. Tại sao form trả góp ghi phiên bản tùy chọn nhưng controller bắt buộc `variant_id`, và giá nhập trong form có mục đích gì?
6. Dữ liệu on-road fee hiện nằm trong `vehicle_on_road_fees` hay legacy `settings`? Ai kiểm chứng nguồn nhà nước và ngày hiệu lực?
7. Vì sao test catalog vẫn kỳ vọng 3 vehicle lines trong khi seeder tạo 17? Test SEO Product schema đã được cập nhật theo yêu cầu nào?
8. Có danh sách account admin, chính sách 2FA/recovery, phân vai, quy trình reset và người quản lý tài khoản không?
9. Bản quyền ảnh xe/logo/font/bài viết được cấp bởi ai, cho phép dùng trên website/marketing trong thời hạn nào?
10. Lead cá nhân được giữ bao lâu, ai được export/xóa, backup có mã hóa không, có quy trình xử lý yêu cầu xóa dữ liệu không?

## 8. Kế hoạch 30 ngày đầu sau khi tiếp quản

| Thời gian | Ưu tiên |
|---|---|
| Ngày 1–3 | Nhận repo/credential qua kênh an toàn; xoay secret; sao lưu DB/media; xác minh restore và quyền hosting/domain/SSL. Không thao tác trực tiếp DB production ngoài cửa sổ đã duyệt. |
| Ngày 4–7 | Dựng staging sạch với PHP patch mới, Intl/GD/WebP; cài Composer/NPM; áp migration/seed vào DB mới; xử lý Livewire advisory; chạy audit/test. |
| Tuần 2 | Sửa calculator và đồng bộ test on-road/seeder/SEO; kiểm tra form/antispam, role, 2FA, CRUD trên dữ liệu giả; phê duyệt dữ liệu xe/ảnh. |
| Tuần 3 | Chạy Lighthouse trên home/catalog/detail/form; đo query và ảnh; rà CSP/HSTS/headers, backup retention, queue/scheduler/monitoring. |
| Tuần 4 | Diễn tập release/rollback, kiểm tra restore lần hai, nghiệm thu UAT với sales/marketing; cập nhật runbook, phân quyền và biên bản dữ liệu/licensing. |

## 9. Checklist ký nhận

- [ ] Repo chuẩn, lịch sử, tag release, quyền thành viên và xác nhận source đầy đủ.
- [ ] Tài liệu kiến trúc, nghiệp vụ, hướng dẫn admin, runbook deploy/rollback.
- [ ] Danh sách tài sản/hạ tầng/credential đã chuyển quyền; secrets được luân chuyển.
- [ ] Database và media backup có checksum; restore test có biên bản.
- [ ] PHP/Composer/Node phiên bản và extension đã xác nhận trên CLI + web server.
- [ ] Migration/seed sạch đạt; test suite đạt; static analyzer và coverage có kết quả.
- [ ] Catalog, giá, màu/phụ phí, phí vùng, liên hệ, địa chỉ được chủ dữ liệu ký.
- [ ] Vai trò admin/marketing/sale, 2FA, form, spam protection và CRUD đã UAT.
- [ ] HTTPS, headers, cookie, debug, storage permission, 404/500, secret exposure đã xác minh trên staging.
- [ ] License/quyền nội dung, ảnh, logo, font được lưu cùng hồ sơ bàn giao.
- [ ] Cron, queue, backup, log/alert, lịch cập nhật và người chịu trách nhiệm được chỉ định.

## 10. Nhật ký bằng chứng và giới hạn

- Đã chạy `php artisan route:list`, `php artisan about`, `composer validate`, `composer audit`, `npm audit --omit=dev`, `composer install --dry-run`, `npm ci --dry-run`, `npm run build`, `php -l` trên các thư mục PHP, `vendor/bin/pint --test`, `php artisan test`.
- HTTP local đã kiểm tra trang chủ, catalog, 17 trang xe, các trang dịch vụ/công cụ/tin tức/liên hệ, sitemap/robots, admin, 404 và một số đường dẫn nhạy cảm; các kết quả tiêu biểu ở mục 4.4.
- Playwright snapshot xác nhận selector Corolla Cross và popup liên hệ; viewport thử ở 360/768/1366/1920. Không có file screenshot độc lập được lưu vào repo; kết quả accessibility snapshot và HTTP status được ghi trong biên bản.
- Không có chứng cứ Lighthouse, PHPStan, production host, full brute-force test, SSL test, backup restore thật, role CRUD hoặc test POST lead. Những mục này để trạng thái chưa xác minh thay vì suy đoán.
- Không gửi POST form/CRUD. Test suite cấu hình SQLite `:memory:`; migrate/seed/rollback nghiệm thu chạy SQLite `:memory:`. Website local báo session driver database, vì vậy các GET trình duyệt có thể làm mới session thông thường; không có lead hoặc bản ghi nghiệp vụ nào được chủ động tạo.

