# Hướng dẫn cài Toyota Kon Tum trên XAMPP và triển khai máy chủ

Tài liệu này hướng dẫn chạy dự án trên Windows/XAMPP để phát triển, sau đó triển khai lên máy chủ Linux hoặc hosting cPanel. Không dùng máy chủ phát triển (`php artisan serve`) cho website production.

## 1. Yêu cầu của dự án

- PHP **8.3 trở lên**. Xác nhận phiên bản và đường dẫn PHP thực tế bằng `where.exe php` và `php -v`.
- Các extension PHP: BCMath, Ctype, cURL, DOM, Fileinfo, Filter, GD (có WebP) hoặc Imagick, Hash, Mbstring, OpenSSL, PDO với `pdo_mysql`, Session, Tokenizer, XML và ZIP.
- Composer 2.
- MySQL 8.0+ hoặc phiên bản MariaDB còn được hỗ trợ.
- Node.js 20+ và npm để biên dịch giao diện.
- Git nếu tải mã nguồn bằng Git.

Phiên bản đang yêu cầu được khai báo trong `composer.json`. **Môi trường XAMPP của máy phát triển trước đây có PHP 8.2.12, không đáp ứng yêu cầu này.** Không chạy ứng dụng bằng Apache/PHP 8.2 của XAMPP. Có thể tiếp tục dùng MariaDB của XAMPP và chạy ứng dụng bằng PHP CLI 8.3+, hoặc cài một bản XAMPP có PHP 8.3+.

## 2. Chạy local trên Windows với MariaDB của XAMPP

### 2.1. Khởi động dịch vụ và kiểm tra PHP

1. Mở XAMPP Control Panel và khởi động **MySQL**. Apache chỉ cần khởi động nếu muốn dùng Apache thay cho máy chủ phát triển của Laravel.
2. Mở PowerShell tại thư mục dự án:

   ```powershell
   Set-Location 'C:\Users\admin\Desktop\src crm toyota'
   where.exe php
   php -v
   composer -V
   node -v
   npm -v
   ```

3. Nếu `php -v` báo thấp hơn 8.3, hãy cài/chọn PHP CLI 8.3+ và đặt PHP đó trước PHP XAMPP trong `PATH`, hoặc gọi trực tiếp `php.exe` 8.3 bằng đường dẫn đầy đủ. Mỗi terminal mới cần dùng đúng PHP 8.3+ khi chạy Composer và Artisan. Kiểm tra extension bằng:

   ```powershell
   php -m
   ```

   Đảm bảo các extension trong danh sách yêu cầu đã được bật trong đúng `php.ini`. `php artisan serve` dùng PHP CLI, có thể khác PHP mà Apache XAMPP sử dụng; bật extension cho Apache không tự bật extension cho CLI. Xem file cấu hình CLI đang nạp bằng:

   ```powershell
   php --ini
   php -m | Select-String 'pdo_mysql'
   ```

   Nếu `pdo_mysql` chưa xuất hiện và PHP CLI chưa có `php.ini`, có thể tạo cấu hình cạnh `php.exe` 8.3. Không ghi đè `php.ini` đã có; nếu đã có, hãy bổ sung các extension cần thiết vào file đó. Điều chỉnh nếu một DLL không có trong thư mục `ext`:

   ```powershell
   $phpDir = Split-Path (Get-Command php).Source
   $extDir = Join-Path $phpDir 'ext'
   $iniPath = Join-Path $phpDir 'php.ini'

   if (Test-Path $iniPath) {
       throw "Đã có php.ini tại $iniPath; hãy kiểm tra và bổ sung cấu hình thay vì ghi đè."
   }

   @(
       "extension_dir=`"$extDir`"",
       'extension=php_curl.dll',
       'extension=php_fileinfo.dll',
       'extension=php_gd.dll',
       'extension=php_intl.dll',
       'extension=php_mbstring.dll',
       'extension=php_openssl.dll',
       'extension=php_pdo_mysql.dll',
       'extension=php_pdo_sqlite.dll',
       'extension=php_sqlite3.dll',
       'extension=php_zip.dll',
       'upload_max_filesize=8M',
       'post_max_size=12M'
   ) | Set-Content -Path $iniPath -Encoding ASCII

   php --ini
   php -m | Select-String 'pdo_mysql'
   ```

   Tối thiểu, kết nối MariaDB cần `pdo_mysql`; SQLite cần cả `pdo_sqlite` và `sqlite3`; tải ảnh cần GD có hỗ trợ WebP hoặc Imagick. PHP CLI và Apache XAMPP có thể nạp hai file `php.ini` khác nhau. Nếu dùng Apache, chạy `php --ini` để xem đường dẫn PHP CLI và kiểm tra mục **Config → PHP (php.ini)** trong XAMPP Control Panel để mở cấu hình Apache. Bổ sung `upload_max_filesize=8M` và `post_max_size=12M` vào `php.ini` Apache, rồi khởi động lại Apache. Sau khi thay đổi cấu hình PHP CLI, dừng rồi chạy lại `php artisan serve`.

### 2.2. Tạo database

Trong phpMyAdmin (thường tại `http://localhost/phpmyadmin`), tạo database tên `toyota_kontum` với collation `utf8mb4_unicode_ci`. Nếu MariaDB cục bộ chỉ dùng cho máy phát triển, tài khoản `root` không mật khẩu có thể dùng tạm; không dùng cấu hình này trên máy chủ công khai.

### 2.3. Cài ứng dụng

Chạy từ thư mục dự án. Nếu `.env` chưa tồn tại:

```powershell
Copy-Item .env.example .env
```

Sửa `.env` để dùng database XAMPP:

```dotenv
APP_NAME="Toyota Kon Tum"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8081

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toyota_kontum
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Giữ bí mật trong `.env`; không đưa file này lên Git hoặc gửi cho người khác. Không thay `APP_KEY` của một môi trường đang có dữ liệu mã hóa.

Cài dependency, sinh khóa ứng dụng lần đầu, tạo bảng và dữ liệu khởi tạo, rồi biên dịch giao diện:

```powershell
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
```

Để tạo tài khoản quản trị đầu tiên, trước khi chạy `php artisan migrate --seed`, điền `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL` và `INITIAL_ADMIN_PASSWORD` trong `.env`. Mật khẩu phải có ít nhất 12 ký tự và không được dùng lại mật khẩu thật ở nơi khác. Seeder không tạo tài khoản có mật khẩu mặc định; nếu các biến này chưa hợp lệ thì sẽ không có admin được tạo. Sau khi tạo tài khoản, xóa `INITIAL_ADMIN_PASSWORD` khỏi cấu hình môi trường và đăng nhập để hoàn tất xác thực hai yếu tố. Seeder có tạo một số dòng xe nháp minh họa; các dòng này chưa được xuất bản và không thay thế dữ liệu/giá đã được đại lý xác nhận.

Khởi chạy để phát triển:

```powershell
php artisan serve --host=127.0.0.1 --port=8081
```

Mở `http://127.0.0.1:8081`; trang quản trị ở `/admin`. Nếu cổng 8081 đang được dùng, chọn cổng khác và cập nhật `APP_URL` tương ứng. `php artisan serve` chỉ phù hợp cho phát triển/kiểm tra cục bộ.

### 2.4. Dùng Apache XAMPP thay cho `artisan serve` (tùy chọn)

Chỉ dùng cách này khi PHP được Apache nạp là **8.3+** và đã bật đầy đủ extension. Nếu Apache vẫn chạy PHP 8.2.12, không trỏ website vào dự án; nâng cấp/thay cấu hình PHP trước.

Tạo VirtualHost trỏ document root vào thư mục `public` của dự án, không trỏ vào thư mục gốc chứa `.env`:

```apache
<VirtualHost *:80>
    ServerName toyota-kontum.local
    DocumentRoot "C:/Users/admin/Desktop/src crm toyota/public"

    <Directory "C:/Users/admin/Desktop/src crm toyota/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Bật `mod_rewrite` và cấu hình tên `toyota-kontum.local` trong file `C:\Windows\System32\drivers\etc\hosts` trỏ đến `127.0.0.1`, sau đó khởi động lại Apache. Đặt `APP_URL=http://toyota-kontum.local` trong `.env`, chạy `php artisan config:clear` và truy cập địa chỉ đó. Chỉ dùng tên miền `.local` trên máy phát triển; cấu hình production cần HTTPS.

## 3. Chuyển database hiện có sang máy chủ

Nếu cần giữ dữ liệu hiện có, hãy sao lưu trước và chỉ chuyển dữ liệu khi có quyền xử lý dữ liệu đó. Database có thể chứa thông tin khách hàng; không gửi bản sao chưa ẩn danh qua email/chat hoặc đặt trong thư mục web công khai. Không chạy lệnh xóa database trên môi trường production.

Trong PowerShell, dùng `mysqldump.exe` và `mysql.exe` đi kèm XAMPP (điều chỉnh đường dẫn nếu XAMPP được cài nơi khác). Ví dụ tạo thư mục backup rồi xuất database; nhập mật khẩu khi được hỏi:

```powershell
New-Item -ItemType Directory -Force 'C:\backup'
& $env:ComSpec /c '"C:\xampp\mysql\bin\mysqldump.exe" --single-transaction --default-character-set=utf8mb4 -u root -p toyota_kontum > "C:\backup\toyota_kontum.sql"'
```

Nhập file sao lưu vào database đích đã tạo sẵn. Thay `DB_HOST`, `DB_USER` và `DB_NAME` bằng thông tin thật; nhập mật khẩu khi được hỏi:

```powershell
& $env:ComSpec /c '"C:\xampp\mysql\bin\mysql.exe" -h DB_HOST -P 3306 -u DB_USER -p DB_NAME < "C:\backup\toyota_kontum.sql"'
```

Với database đích từ xa, thay đường dẫn `mysql.exe` bằng client có trên máy quản trị và dùng host/port do nhà cung cấp cấp. Nhập mật khẩu tại lời nhắc tương tác; không ghi mật khẩu vào lệnh, Git, tài liệu hay log. Bảo vệ file `.sql` như dữ liệu nhạy cảm và xóa bản tạm an toàn sau khi đã xác minh backup/restore theo quy trình của đơn vị.

Nếu không cần giữ dữ liệu, **không nhập bản sao database cũ**: tạo database rỗng rồi chạy migration và seed trên máy chủ.

## 4. Triển khai production lên Ubuntu 24.04 + Nginx

Các lệnh sau là quy trình tham khảo cho VPS Ubuntu 24.04. Xác nhận phiên bản PHP và khả năng cài extension với nhà cung cấp trước khi triển khai. Thay các giá trị `<...>` bằng thông tin do chủ dự án/nhà cung cấp cấp; không đặt bí mật vào Git hoặc tài liệu này.

### 4.1. Chuẩn bị server

Cài Nginx, PHP 8.3-FPM cùng các extension cần thiết, Composer 2, Node.js 20+ và MySQL/MariaDB. Tên gói có thể khác theo repository của hệ điều hành; xác minh PHP được cài là 8.3+.

Tạo database riêng và user database chỉ có quyền trên database của ứng dụng. Ví dụ chạy trong client quản trị MySQL/MariaDB (thay tên, mật khẩu dài-ngẫu nhiên):

```sql
CREATE DATABASE toyota_kontum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'toyota_app'@'localhost' IDENTIFIED BY '<MAT_KHAU_NGAU_NHIEN_DAI>';
GRANT ALL PRIVILEGES ON toyota_kontum.* TO 'toyota_app'@'localhost';
FLUSH PRIVILEGES;
```

Không mở cổng database ra Internet nếu ứng dụng và database cùng máy. Nếu database ở máy riêng, giới hạn firewall theo địa chỉ IP của ứng dụng và dùng kết nối TLS phù hợp.

### 4.2. Đưa mã nguồn và cài dependency

Đặt mã nguồn trong thư mục không công khai, ví dụ `/var/www/toyota-kontum`; chỉ thư mục `public/` được Nginx phục vụ. Không chép `.env`, bản dump database, log, `vendor/` của Windows hoặc `node_modules/` từ máy phát triển lên web root.

Ví dụ nếu triển khai bằng Git:

```sh
cd /var/www
git clone <URL_REPOSITORY_DUOC_PHE_DUYET> toyota-kontum
cd /var/www/toyota-kontum
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Nếu server không nên cài Node.js, chạy `npm ci` và `npm run build` trong pipeline build đáng tin cậy, rồi chuyển `public/build/` cùng mã nguồn đã phát hành lên server.

Tạo `.env` trực tiếp trên server bằng secret store hoặc trình soạn thảo an toàn; không commit file này. Cấu hình tối thiểu:

```dotenv
APP_NAME="Toyota Kon Tum"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<TEN_MIEN_CUA_DAI_LY>
APP_KEY=<APP_KEY_DUY_NHAT_CUA_MOI_TRUONG>

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toyota_kontum
DB_USERNAME=toyota_app
DB_PASSWORD=<MAT_KHAU_DATABASE>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Đặt thêm SMTP, email thông báo, Telegram và thông tin đại lý chỉ khi đã được cung cấp/xác nhận. Không bật Pixel/GA4/GTM trước khi chính sách quyền riêng tư và cơ chế đồng ý được duyệt. Không lưu token tích hợp trong nội dung trang hoặc JSON giao diện.

**Tạo `APP_KEY` đúng một lần cho môi trường mới** bằng `php artisan key:generate --show`, rồi lưu giá trị vào secret store. Không chạy `key:generate` trên lần triển khai tiếp theo hoặc trên ứng dụng đã mã hóa dữ liệu. Giữ `APP_KEY` cũ khi nâng cấp cùng môi trường.

### 4.3. Migration, tài khoản quản trị và quyền file

Đảm bảo `.env` production đã được nạp và database đã sẵn sàng. Tạo admin đầu tiên bằng secret tạm thời `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_PASSWORD` rồi chạy lần đầu:

```sh
php artisan migrate --seed --force
```

Seeder tạo tài khoản admin khi có email và mật khẩu hợp lệ (tối thiểu 12 ký tự), cùng role và nội dung khởi tạo. Không dùng tài khoản/mật khẩu ví dụ hoặc mật khẩu đã chia sẻ trong tin nhắn. Đăng nhập, bật và xác minh 2FA; sau đó gỡ secret tạo admin khỏi môi trường triển khai. Nếu database đã được khôi phục từ bản sao có sẵn, xem trạng thái migration và backup trước; không chạy seeder tùy tiện trên dữ liệu production.

Sau khi gỡ `INITIAL_ADMIN_PASSWORD`, chạy `php artisan config:clear` trước khi tạo config cache để tránh giữ secret khởi tạo trong cache.

Tạo liên kết storage và đặt quyền ghi tối thiểu cho user PHP-FPM. Ví dụ khi PHP-FPM chạy dưới user `www-data`:

```sh
php artisan storage:link
sudo chown -R <DEPLOY_USER>:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Không dùng `chmod -R 777`. Mã nguồn và `.env` không cần được ghi bởi tiến trình PHP; chỉ `storage/` và `bootstrap/cache/` cần quyền ghi phù hợp.

### 4.4. Cấu hình Nginx và HTTPS

Tạo server block với `root` là thư mục `public/`. Ví dụ tối thiểu (thay hostname, đường dẫn và socket PHP-FPM đúng với server):

```nginx
server {
    listen 80;
    server_name <TEN_MIEN_CUA_DAI_LY> www.<TEN_MIEN_CUA_DAI_LY>;
    root /var/www/toyota-kontum/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Kiểm tra cấu hình (`sudo nginx -t`), sau đó nạp lại Nginx. Cấu hình chứng chỉ TLS hợp lệ và chuyển hướng HTTP sang HTTPS bằng công cụ/nhà cung cấp TLS đã chọn; cấu hình redirect tên miền www hoặc không-www nhất quán. Bảo đảm document root không bao giờ trỏ vào thư mục gốc dự án.

### 4.5. Cache, queue và lịch chạy

Sau khi `.env` production và quyền file đã đúng:

```sh
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ứng dụng dùng queue database cho thông báo; production cần một queue worker được quản lý bởi Supervisor hoặc systemd để tự khởi động lại khi lỗi/reboot:

```sh
php artisan queue:work --tries=3 --timeout=90
```

Thiết lập cron chạy Laravel scheduler mỗi phút:

```cron
* * * * * cd /var/www/toyota-kontum && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler xử lý xuất bản trang đã lên lịch. Kiểm tra SMTP/Telegram và queue bằng dữ liệu thử không phải thông tin khách thật; theo dõi failed jobs mà không ghi dữ liệu cá nhân vào log.

## 5. Hosting cPanel

Trước khi chọn gói hosting, xác nhận nhà cung cấp hỗ trợ PHP 8.3+, các extension bắt buộc, Composer 2, MySQL/MariaDB tương thích, HTTPS, cron mỗi phút, storage có thể ghi, và queue worker chạy liên tục. Nếu không hỗ trợ worker, xác nhận phương án queue phù hợp trước khi nhận lead thật.

1. Đưa mã nguồn lên một thư mục riêng ngoài `public_html` nếu hosting cho phép.
2. Cấu hình document root của domain trỏ tới thư mục `public/` của Laravel. Nếu không thể đặt mã nguồn ngoài document root, cần nhờ nhà cung cấp cấu hình web root an toàn; không chỉ đổi tên/move nội dung `public/` một cách tùy tiện.
3. Tạo database và database user trong cPanel, cấp quyền tối thiểu trên database đó.
4. Tạo `.env` bằng File Manager/SSH ở vị trí không thể tải qua web; đặt APP_ENV production, APP_DEBUG false, APP_URL HTTPS, APP_KEY riêng và thông tin DB chính xác.
5. Cài dependency production, build frontend, chạy migration có backup trước, tạo admin đầu tiên, tạo storage link và đặt quyền ghi như hướng dẫn VPS.
6. Bật HTTPS, cron scheduler, queue worker theo hướng dẫn nhà cung cấp; thử gửi form và xác nhận lead/notification hoạt động trước khi mở chiến dịch quảng cáo.

Nếu hosting không thể cung cấp web root an toàn, PHP 8.3+, TLS hoặc worker/scheduler cần thiết, chưa nên đưa website vào vận hành nhận khách thật.

## 6. Cập nhật phiên bản sau này

1. Sao lưu database và media; xác nhận khả năng khôi phục.
2. Tải bản phát hành đã kiểm tra; giữ `.env` và `APP_KEY` hiện có.
3. Cài dependency production và build asset.
4. Chạy `php artisan down` nếu có thời gian gián đoạn được duyệt.
5. Chạy `php artisan migrate --force`; không chạy lại seeder một cách mặc định khi cập nhật.
6. Xóa cache cũ, tạo cache mới, khởi động lại queue worker để nạp mã mới.
7. Chạy smoke test trang chủ, form lead, đăng nhập admin/2FA và upload ảnh; xem log và queue.
8. Chạy `php artisan up` nếu đã bật maintenance mode.

Không cập nhật hoặc migration trực tiếp trên production mà chưa có backup đã kiểm chứng và phương án quay lui.

## 7. Kiểm tra trước khi mở website

- [ ] PHP production là 8.3+; extension bắt buộc và `pdo_mysql` hoạt động.
- [ ] Domain trỏ tới `public/`; HTTPS hoạt động; `.env`, log, backup và mã nguồn không thể tải qua web.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` là HTTPS; `APP_KEY` an toàn và ổn định.
- [ ] Database user không dùng `root`; migration hoàn tất; backup và thử khôi phục thành công.
- [ ] Tài khoản admin riêng đã đăng nhập, bật và xác minh 2FA; mật khẩu khởi tạo đã gỡ khỏi secret tạm.
- [ ] Queue worker, scheduler, email và các thông báo được cấu hình và thử.
- [ ] Ảnh/logo, giá, ưu đãi, phí, thông tin liên hệ và chính sách quyền riêng tư đã được đại lý xác nhận; không còn placeholder chưa xử lý.
- [ ] Đã kiểm tra trang chủ, mobile, form lead, trang cảm ơn, quyền quản trị, SEO và log trên production.
- [ ] Pixel/GA4/GTM chỉ được bật sau khi cơ chế consent và thông báo quyền riêng tư được duyệt.

## 8. Khắc phục nhanh

- **Composer báo PHP quá cũ:** kiểm tra `where.exe php`/`php -v` trên Windows hoặc `php -v` trên server; chọn PHP 8.3+ cho đúng terminal, Composer và PHP-FPM/Apache.
- **Không kết nối được database:** kiểm tra XAMPP MySQL/MariaDB đang chạy, `DB_HOST`, `DB_PORT`, tên database/user/password và extension `pdo_mysql`; sau khi đổi `.env`, chạy `php artisan config:clear`.
- **Lỗi 500 hoặc trang báo lỗi chung:** production giữ `APP_DEBUG=false`; kiểm tra `storage/logs/laravel.log` qua SSH/console có phân quyền, không đưa log ra web và không chia sẻ thông tin cá nhân trong log.
- **Ảnh tải lên không hiển thị:** xác nhận đã chạy `php artisan storage:link`, `public/storage` trỏ đúng, user PHP có quyền ghi `storage/`, và GD hỗ trợ WebP hoặc Imagick đã bật.
- **Form nhận lead nhưng không có email/thông báo:** kiểm tra SMTP/Telegram, database queue, worker đang chạy và failed jobs; không gửi dữ liệu khách thật trong lần kiểm tra.
- **CSS/JavaScript không cập nhật:** chạy `npm ci` và `npm run build`, xác nhận `public/build/manifest.json` có mặt trong bản phát hành và xóa cache trình duyệt/CDN nếu cần.

Thông tin chi tiết về cấu trúc ứng dụng, quyền admin, trình dựng trang và production checklist cũng được mô tả trong `README.md`.
