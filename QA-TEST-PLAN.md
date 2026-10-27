# Kế hoạch kiểm thử QA — Toyota Kon Tum

**Phiên bản:** 1.0  
**Ngày lập:** 20/10/2026  
**Người thực hiện:** Chưa phân công  
**Môi trường mục tiêu:** Chưa được cung cấp (`[URL STAGING]`)  
**Tài khoản:** Chưa được cấp; không lưu mật khẩu, OTP hoặc API key trong tài liệu này.

## 1. Kế hoạch kiểm thử

### Mục tiêu và phạm vi

Ưu tiên xác nhận dữ liệu lead được ghi nhận và chuyển tới sale; phép tính giá/chi phí/trả góp đúng theo cấu hình staging đã được chủ dự án phê duyệt; đăng nhập, MFA và phân quyền CRM ngăn truy cập trái phép. Chỉ sử dụng staging và dữ liệu tổng hợp có nhãn `QA`, không dùng thông tin khách hàng thật.

**Trong phạm vi kế hoạch:**

- **A — Form lead:** báo giá/lái thử, xác thực trường, consent, dữ liệu tiếng Việt/ký tự đặc biệt, gửi lặp, nguồn UTM và kết quả lưu lead/thông báo.
- **B — Máy tính:** trả góp và chi phí lăn bánh; giá trị biên, cấu hình lãi suất/phí, thay đổi phiên bản/khu vực và nội dung ước tính.
- **C, D, F–L:** danh mục/chi tiết/so sánh xe, khuyến mãi, quản lý lead, quản trị nội dung, giao diện, truy cập, SEO/tracking, hiệu năng và kiểm tra bảo mật hộp đen an toàn.
- **E — Đăng nhập và phân quyền:** xác thực/MFA/đăng xuất/hết phiên; quyền từng vai trò, truy cập URL trực tiếp, giới hạn lead và CSV.

**Ngoài phạm vi thực thi hiện tại:** kiểm thử trình duyệt thực tế trên mọi thiết bị nếu chưa có thiết bị; gửi thông báo tới khách thật; kiểm thử tải/DoS; xóa hàng loạt; thay đổi cấu hình nghiệp vụ hoặc dữ liệu production. Các nhóm C, D, F–L có ca kiểm thử trong tài liệu nhưng chưa được thực thi.

### Môi trường, dữ liệu và cách thực hiện

- Môi trường yêu cầu: URL staging HTTPS riêng, phiên bản/build được ghi nhận trước khi chạy. Không dùng localhost hoặc production thay cho staging.
- Trình duyệt/thiết bị dự kiến: Chrome, Firefox, Edge trên desktop; Safari trên iOS và Chrome trên Android; viewport 390px, 768px, 1360px. Chỉ đánh dấu trình duyệt đã thực sự chạy.
- Tạo dữ liệu QA có thể nhận biết: tên `QA ...`, số điện thoại test được chủ dự án cấp, xe/phiên bản được đánh dấu test, hộp thư hoặc Telegram sink của staging. Ghi ID lead và xóa dữ liệu test chỉ theo quy trình được duyệt.
- Mỗi ca ghi thời gian, người chạy, môi trường/build, trình duyệt/thiết bị, dữ liệu đầu vào, kết quả, HTTP/console/network log và mã bằng chứng. Che PII/token trước khi lưu ảnh/log.
- Thực hiện ca hợp lệ trước, rồi mới đến dữ liệu sai/biên. Chỉ thử số lần đăng nhập sai tối đa theo ngưỡng đã xác nhận; không tiếp tục để tránh khóa tài khoản ngoài ý muốn.
- Khi bị chặn bởi cấu hình hoặc thông tin nghiệp vụ chưa rõ, ghi **Bị chặn** hoặc **Cần xác nhận**, không suy diễn thành lỗi.

### Mức độ rủi ro và tiêu chí hoàn thành

Thứ tự ưu tiên rủi ro: (1) lead mất hoặc gửi sai dữ liệu; (2) sai giá/ưu đãi/phép tính; (3) truy cập vượt quyền/IDOR; (4) lỗi mobile ảnh hưởng CTA; (5) tracking gửi sai hoặc khi chưa đồng ý.

Đợt A/B/E hoàn tất khi mọi ca có kết quả và bằng chứng; không còn lỗi Nghiêm trọng/Cao mở; các luồng P1 đạt 100% hoặc có ngoại lệ được chủ dự án chấp thuận bằng văn bản. Kết luận phát hành toàn website cần cả các nhóm C, D, F–L và hồi quy sau sửa; kế hoạch A/B/E một mình không đủ kết luận sẵn sàng ra mắt.

### Ghi nhận tiền kiểm từ mã nguồn (không phải kết quả staging)

- Form lead có throttle theo route, yêu cầu consent và chuẩn hóa một số dạng số điện thoại `+84`/`84`/`0084`. Đây là căn cứ thiết kế ca, không chứng minh email/Telegram hoặc staging đã hoạt động.
- Route trả góp nhận kỳ hạn 1–360 tháng, giá tối đa 10 tỷ và lấy lãi suất từ cấu hình; route lăn bánh lấy phí theo vùng từ cấu hình. Kỳ hạn hợp lệ về mặt đầu vào chưa chắc đã có lãi suất cấu hình.
- Cấu hình mã nguồn bật yêu cầu MFA admin mặc định, nhưng không có thông tin xác nhận cấu hình staging hoặc chính sách quyền từng vai trò.
- Chưa được cung cấp quy tắc chống trùng, công thức/phí chính thức, ma trận quyền, URL staging, tài khoản hoặc sink thông báo. Không kết luận lỗi từ các điểm chưa rõ này.

## 2. Bộ ca kiểm thử

**Trạng thái ban đầu của toàn bộ ca:** Chưa chạy — thiếu URL staging, tài khoản, dữ liệu được duyệt và quy tắc nghiệp vụ.  
**Ngày chạy/người chạy/bằng chứng:** điền khi thực thi. Không có lỗi thực nghiệm nào được ghi nhận trong tài liệu này.

### A. Form lead — khách hàng

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-001 | Gửi yêu cầu báo giá hợp lệ. Staging có xe QA và notification sink. | Mở trang xe QA → Báo giá → nhập tên/số test/khu vực hợp lệ → đồng ý xử lý dữ liệu → gửi một lần. | Trang xác nhận; đúng một lead mới với đúng dữ liệu/consent; lead xuất hiện trong CRM; notification tới sink staging đúng một lần. | P1 | Chưa chạy |
| TC-002 | Đăng ký lái thử hợp lệ, có ngày hẹn hợp lệ. | Mở form lái thử → nhập dữ liệu test → chọn xe/khu vực/ngày tương lai → đồng ý → gửi. | Lead có request type lái thử và ngày/xe đúng; trạng thái xác nhận; thông báo staging tới đúng sink. | P1 | Chưa chạy |
| TC-003 | Bắt buộc họ tên. | Để họ tên trống; nhập các trường còn lại đúng và consent; gửi. | Báo lỗi gắn với họ tên, giữ lại dữ liệu không nhạy cảm đã nhập, không tạo lead/notification. | P1 | Chưa chạy |
| TC-004 | Bắt buộc số điện thoại. | Để số điện thoại trống; điền các trường khác hợp lệ; gửi. | Báo lỗi số điện thoại; không tạo lead/notification. | P1 | Chưa chạy |
| TC-005 | Từ chối số quá ngắn/thiếu chữ số. | Nhập số ngắn hơn định dạng được chủ dự án chấp thuận; gửi. | Hiển thị lỗi định dạng rõ ràng; không tạo lead/notification. | P1 | Chưa chạy |
| TC-006 | Từ chối ký tự không phải số điện thoại. | Nhập chuỗi chữ và số, ví dụ `09abc12345`; gửi. | Báo lỗi số điện thoại; dữ liệu không được lưu thành lead. | P1 | Chưa chạy |
| TC-007 | Xác nhận chuẩn hóa định dạng quốc tế. | Nhập số test hợp lệ dạng `+84 ...` đã cấp; gửi; so sánh CRM và dữ liệu nguồn được phép xem. | Số được chuẩn hóa nhất quán thành dạng lưu trữ đã quy định; không mất/sai chữ số. | P1 | Chưa chạy |
| TC-008 | Xác nhận xử lý khoảng trắng/dấu phân cách số. | Nhập số test hợp lệ có khoảng trắng hoặc dấu phân cách được hỗ trợ; gửi. | Kết quả được chuẩn hóa hoặc báo hướng dẫn nhất quán với đặc tả; không tạo số sai. | P1 | Chưa chạy |
| TC-009 | Consent liên hệ là bắt buộc. | Điền form hợp lệ nhưng không chọn ô đồng ý; gửi. | Không gửi thành công, có thông báo consent, không tạo lead/notification. | P1 | Chưa chạy |
| TC-010 | Hỗ trợ tên tiếng Việt có dấu. | Nhập tên QA tiếng Việt có dấu; gửi hợp lệ; kiểm tra CRM và notification sink. | Dấu được giữ nguyên ở giao diện, cơ sở dữ liệu, email/Telegram và CSV nếu có quyền. | P1 | Chưa chạy |
| TC-011 | Kiểm tra giới hạn độ dài tên. | Nhập tên đúng giới hạn tối đa được công bố/cấu hình; gửi. | Không cắt dữ liệu âm thầm; xử lý đúng theo giới hạn và hiển thị hợp lệ. | P2 | Chưa chạy |
| TC-012 | Kiểm tra tên vượt giới hạn. | Nhập tên dài hơn giới hạn; gửi. | Báo lỗi độ dài rõ ràng; không tạo lead với dữ liệu bị cắt/ngầm chấp nhận. | P2 | Chưa chạy |
| TC-013 | Ký tự đặc biệt và chống XSS lưu trữ. | Dùng chuỗi test như `O'Neil & <b>QA</b>` ở trường tên/ghi chú; gửi, mở lead trong CRM. | Ký tự được lưu/hiển thị an toàn theo ngữ cảnh; HTML không bị thực thi hoặc làm vỡ giao diện. Không dùng payload phá hoại. | P1 | Chưa chạy |
| TC-014 | Kiểm tra quy tắc gửi lại cùng số (nghiệp vụ chưa xác nhận). | Gửi lead QA hợp lệ; gửi yêu cầu khác bằng cùng số trong khoảng thời gian cần xác nhận; đối chiếu CRM. | Xử lý đúng quy tắc chống trùng đã được chủ dự án phê duyệt; không tự giả định phải chặn hay gộp. | P1 | Bị chặn — cần quy tắc chống trùng |
| TC-015 | Kiểm tra bấm gửi liên tiếp/gửi đôi. | Với một form hợp lệ, bấm Gửi liên tiếp nhanh hai lần; đếm record và thông báo. | Theo hợp đồng gửi đôi đã xác nhận: không mất lead và không tạo lead/notification trùng ngoài ý muốn; nút có trạng thái chờ phù hợp. | P1 | Chưa chạy |
| TC-016 | Mất mạng và thử lại an toàn. | Mô phỏng offline trước khi gửi; thử gửi; bật mạng lại và gửi lại theo hướng dẫn; kiểm tra CRM. | Có thông báo thất bại/thử lại rõ; không báo thành công giả; lần retry không nhân đôi lead. | P1 | Chưa chạy |
| TC-017 | Lưu nguồn chiến dịch. | Mở landing bằng bộ `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term` QA đã định; gửi lead. | Trường UTM và nguồn/landing được lưu đúng trên lead; không ghi nhầm giữa các phiên. | P2 | Chưa chạy |
| TC-018 | Từ chối dữ liệu thiếu/sai ở nhiều trường. | Gửi form thiếu tên, điện thoại, khu vực và consent; sửa lần lượt các trường theo thông báo. | Mỗi lỗi chỉ đúng trường; sau sửa, form gửi được và không mất dữ liệu đã nhập. | P1 | Chưa chạy |

### B. Máy tính dự toán lăn bánh và trả góp

**Dữ liệu fixture tham khảo dưới đây chỉ dùng để kiểm tra phép tính, không phải giá/phí Toyota Kon Tum.** Chủ dự án phải xác nhận lãi suất, phí vùng, làm tròn và các điều kiện áp dụng trước khi đối chiếu staging.

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-019 | Đối chiếu lăn bánh bằng phép tính tay. Fixture QA: giá 100.000.000đ; tỷ lệ đăng ký 10%; phí cố định đăng ký 1.000.000đ; biển số 500.000đ; đường bộ 600.000đ; bảo hiểm bắt buộc 100.000đ; phí khác 0đ. | Chọn vùng Kon Tum QA và giá fixture; tính tay: đăng ký 11.000.000đ; tổng phí 12.200.000đ; tổng 112.200.000đ; so sánh UI. | Các khoản và tổng khớp fixture; ghi rõ ước tính/tham khảo; không trình bày fixture như báo giá thật. | P1 | Chưa chạy |
| TC-020 | Đối chiếu trả góp lãi suất 0%. Fixture QA: giá 120.000.000đ, trả trước 25%, kỳ hạn 12 tháng, lãi suất 0%. | Tính tay: trả trước 30.000.000đ, gốc 90.000.000đ, hàng tháng 7.500.000đ; so sánh UI. | Các số tiền khớp; lãi bằng 0; đơn vị tiền tệ/làm tròn rõ. | P1 | Chưa chạy |
| TC-021 | Đối chiếu trả góp lãi suất dương. Fixture QA: giá 120.000.000đ, trả trước 0%, 12 tháng, lãi suất năm 12%, trả đều theo dư nợ giảm dần. | Dùng bảng tính độc lập công thức niên kim `P*r/(1-(1+r)^-n)`, r=12%/12; đối chiếu tiền tháng/tổng UI. | Kết quả tham chiếu fixture: khoảng 10.661.855đ/tháng và 7.942.256đ tiền lãi tổng, theo quy tắc làm tròn hiện có; xác nhận lại công thức/quy tắc với chủ dự án trước khi dùng như oracle staging. | P1 | Chưa chạy |
| TC-022 | Trả trước 0%. | Chọn biến thể/giá QA; nhập 0%; chọn kỳ hạn có lãi suất cấu hình. | Gốc vay bằng toàn giá xe; kết quả không lỗi/NaN; mức trả trước hiển thị 0đ. | P1 | Chưa chạy |
| TC-023 | Trả trước 100%. | Chọn fixture hợp lệ, nhập 100%, chọn kỳ hạn có cấu hình. | Gốc và tiền trả hàng tháng bằng 0; không phát sinh giá trị âm hoặc chia cho 0. | P1 | Chưa chạy |
| TC-024 | Kỳ hạn tối thiểu và tối đa hợp lệ. | Chạy kỳ hạn 1 và 360 tháng; chỉ dùng kỳ hạn có rate cấu hình staging. | Kỳ hạn hợp lệ tính đúng; kỳ hạn không có rate phải báo chưa cấu hình, không bịa lãi suất. | P1 | Chưa chạy |
| TC-025 | Kỳ hạn ngoài biên. | Thử 0, -1, 361 và chuỗi chữ trong URL/form. | Đầu vào ngoài 1–360 bị từ chối/validation rõ; không trả kết quả tính sai. | P1 | Chưa chạy |
| TC-026 | Lãi suất 0 và lãi suất ngoài miền. | Dùng cấu hình QA có lãi suất 0%; thử cấu hình/đầu vào âm hoặc lớn hơn miền được chủ dự án cho phép nếu có quyền test cấu hình. | 0% cho kết quả chia đều gốc; giá trị ngoài miền không được dùng để tính; không tự chỉnh cấu hình staging khi chưa phê duyệt. | P1 | Bị chặn — cần quyền cấu hình |
| TC-027 | Giá âm/0/giá trị quá lớn. | Thử giá -1, 0, 10 tỷ và lớn hơn 10 tỷ + 1; ghi nhận thông báo/kết quả. | Giá âm/0/vượt giới hạn bị từ chối; giới hạn tối đa xử lý đúng theo đặc tả đã xác nhận. | P1 | Chưa chạy |
| TC-028 | Thiếu trường đầu vào. | Để trống phiên bản, giá, khu vực, trả trước hoặc kỳ hạn; truy cập cả đường dẫn sạch và đường dẫn có query thiếu trường. | Không có kết quả sai/NaN; chỉ hiện kết quả khi đủ dữ liệu; giải thích trường còn thiếu. | P2 | Chưa chạy |
| TC-029 | Đổi khu vực cập nhật phí lăn bánh. | Cùng một giá/phiên bản, chọn lần lượt Kon Tum, Gia Lai, Quảng Ngãi; lưu ảnh từng kết quả. | Các khoản thay đổi theo đúng bảng phí được duyệt; giá xe/gốc không đổi ngoài dự kiến. | P1 | Bị chặn — cần bảng phí chính thức |
| TC-030 | Đổi phiên bản cập nhật giá và khoản vay. | Chọn hai phiên bản có giá QA khác nhau, giữ nguyên trả trước/kỳ hạn/khu vực. | Giá, tiền trả trước, gốc, khoản vay và tổng cập nhật theo phiên bản đã chọn; không giữ dữ liệu cũ trong UI. | P1 | Chưa chạy |
| TC-031 | Nhãn ước tính và diễn giải kết quả. | Mở cả hai máy tính ở trạng thái đã có kết quả. | Có thông báo “ước tính/tham khảo”; các khoản thực tế phụ thuộc điều kiện và cấu hình được giải thích theo nội dung được duyệt. | P2 | Chưa chạy |

### E. Đăng nhập và phân quyền quản trị

**Điều kiện bắt buộc:** tài khoản QA riêng cho admin/quản lý/marketing/sale; ma trận quyền đã được chủ dự án duyệt; tài khoản OTP/MFA staging; quy định lockout và hết phiên. Không dùng tài khoản thật và không thử vượt ngưỡng sai nếu chưa duyệt.

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-032 | Đăng nhập đúng thông tin và hoàn tất MFA. | Mở `/admin`; nhập thông tin tài khoản QA admin; nhập OTP/MFA hiện hành. | Vào dashboard đúng vai trò; MFA bắt buộc nếu policy yêu cầu; không lộ secret/OTP trong URL hoặc log phía client. | P1 | Bị chặn — chưa có URL/tài khoản |
| TC-033 | Mật khẩu sai. | Nhập email tài khoản QA cùng mật khẩu sai một lần; không lặp quá giới hạn đã duyệt. | Không đăng nhập; thông báo chung không tiết lộ email có tồn tại hay không; không lộ stack trace. | P1 | Bị chặn — chưa có tài khoản |
| TC-034 | OTP sai/hết hạn/tái sử dụng. | Với tài khoản MFA QA và OTP thử được cấp, lần lượt nhập OTP sai, OTP hết hạn, rồi thử dùng lại OTP đã dùng. | Từ chối đăng nhập theo policy; session không được xác thực; lỗi không làm lộ mã bí mật. | P1 | Bị chặn — cần MFA test an toàn |
| TC-035 | Giới hạn đăng nhập sai/khóa tạm. | Xác nhận ngưỡng với chủ dự án; thực hiện số lần sai tối đa đã duyệt; kiểm tra thời gian chờ và thông báo. | Rate limit/khóa tạm đúng policy; không tiếp tục brute force; tài khoản QA được mở lại theo quy trình. | P1 | Bị chặn — cần ngưỡng được duyệt |
| TC-036 | Đăng xuất kết thúc phiên. | Đăng nhập tài khoản QA; logout; dùng Back/reload và URL dashboard trực tiếp. | Session bị thu hồi; trang được bảo vệ yêu cầu đăng nhập lại; không xem lại dữ liệu CRM sau logout. | P1 | Bị chặn — chưa có tài khoản |
| TC-037 | Hết phiên không hoạt động. | Đăng nhập; chờ đúng timeout đã cấu hình; truy cập lại lead/dashboard. | Bị yêu cầu xác thực lại theo chính sách; thao tác/form nhạy cảm không tiếp tục bằng session hết hạn. | P1 | Bị chặn — cần timeout/policy |
| TC-038 | Menu và URL trực tiếp theo vai trò. | Với từng vai trò, ghi menu được thấy; nhập trực tiếp URL các tài nguyên không được cấp quyền. | Chỉ truy cập tác vụ được ma trận quyền duyệt; truy cập trái quyền bị 403/ẩn theo chính sách, không chỉ ẩn menu. | P1 | Bị chặn — cần ma trận quyền |
| TC-039 | IDOR: đổi ID lead. | Dùng lead QA thuộc tài khoản/nhân viên khác; thay ID trên URL/detail/action/API bằng ID đó. | Không đọc/sửa lead ngoài phạm vi; phản hồi không xác nhận dữ liệu người khác nếu policy yêu cầu che tồn tại; không ghi thay đổi trái phép. | P1 | Bị chặn — cần quy tắc sở hữu lead |
| TC-040 | Sale chỉ xem phạm vi lead được phép. | Đăng nhập hai tài khoản sale QA khác nhau; tạo/được gán lead riêng; tìm kiếm và mở danh sách/chi tiết. | Mỗi sale chỉ thấy phạm vi lead theo quy định được duyệt; không rò tên, số điện thoại, ghi chú hoặc số lượng lead ngoài quyền. | P1 | Bị chặn — cần xác nhận sale có giới hạn sở hữu |
| TC-041 | Gán nhân viên và cập nhật trạng thái theo vai trò. | Thử mở chức năng gán/chuyển trạng thái bằng từng vai trò; thực hiện thay đổi trên lead QA được phép. | Vai trò được phép thao tác thành công và timeline ghi đúng; vai trò không được phép bị từ chối; không thay đổi lead khác. | P1 | Bị chặn — cần ma trận quyền |
| TC-042 | Xuất CSV có kiểm soát quyền. | Với từng vai trò, thử menu và URL export; export tập lead QA được phép; mở CSV bằng ứng dụng hỗ trợ UTF-8. | Chỉ vai trò và phạm vi được duyệt export; cột/record đúng; tiếng Việt có dấu đọc được; không chứa lead trái quyền. | P1 | Bị chặn — cần chính sách export |

### C. Danh mục, chi tiết xe và so sánh

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-043 | Lọc danh mục theo kiểu dáng, khoảng giá, số chỗ, nhiên liệu. | Chọn từng bộ lọc riêng rồi kết hợp; kiểm tra URL/trạng thái kết quả; xóa bộ lọc. | Chỉ xe phù hợp xuất hiện; giá trị lọc được giữ đúng khi tải lại/phân trang; xóa lọc trả về danh sách đầy đủ. | P1 | Chưa chạy |
| TC-044 | Sắp xếp, số lượng và phân trang. | Chọn các lựa chọn sắp xếp được hiển thị; kiểm tra số kết quả, trang tiếp theo/cuối. | Thứ tự, số lượng và phân trang nhất quán với dữ liệu được công bố; không lặp/mất xe giữa trang. | P2 | Chưa chạy |
| TC-045 | Bộ lọc không có kết quả và tham số sai. | Chọn tổ hợp không có xe; thử min price lớn hơn max price và category không hợp lệ trong URL. | Trạng thái rỗng hướng dẫn rõ; tham số sai được validate/loại bỏ an toàn, không lỗi 500. | P2 | Chưa chạy |
| TC-046 | So sánh 2–3 xe và khác biệt. | Tìm công cụ so sánh từ điều hướng; chọn hai rồi ba xe/phiên bản; so sánh thông số và khác biệt. | Bảng hiển thị đúng các lựa chọn và chỉ đánh dấu khác biệt thực tế. Nếu không tìm thấy công cụ, ghi nhận đường dẫn/bằng chứng và xin xác nhận phạm vi trước khi lập bug. | P1 | Cần xác nhận — chưa thấy route so sánh trong danh sách route đã kiểm tra |
| TC-047 | Phiên bản/màu cập nhật ảnh, giá và thông số. | Mở chi tiết xe có ít nhất hai phiên bản/màu QA; đổi lựa chọn; so sánh với dữ liệu quản trị được duyệt. | Ảnh/alt, giá và thông số đồng bộ đúng phiên bản/màu; trạng thái không bị trộn từ lựa chọn trước. | P1 | Chưa chạy |

### D. Khuyến mãi

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-048 | Ưu đãi đang hiệu lực hiển thị đúng. | Dùng promotion QA đã publish, đang trong thời gian hiệu lực; mở trang chủ, danh sách ưu đãi và xe áp dụng. | Chỉ nội dung/điều kiện đã duyệt hiển thị; ngày bắt đầu/hết hạn đọc rõ; liên kết đúng. | P1 | Chưa chạy |
| TC-049 | Ưu đãi chưa tới ngày/hết hạn/unpublished không hiển thị công khai. | Chuẩn bị ba trạng thái QA có ngày hiệu lực đã duyệt; kiểm tra trang chủ, trang xe và sitemap nếu áp dụng. | Ưu đãi ngoài hiệu lực hoặc chưa publish không xuất hiện như ưu đãi đang chạy; dữ liệu quản trị vẫn còn nguyên. | P1 | Chưa chạy |
| TC-050 | Điều kiện xe/phiên bản và ngày theo múi giờ Việt Nam. | Chọn promotion áp dụng cho một xe/phiên bản; kiểm tra xe khác và thời điểm sát nửa đêm/ngày hết hạn với cấu hình múi giờ đã xác nhận. | Chỉ đối tượng đủ điều kiện được hiển thị; ngày không lệch do timezone. Không đánh lỗi trước khi chủ dự án xác nhận quy ước biên ngày/múi giờ. | P1 | Cần xác nhận — cần quy tắc áp dụng và timezone chuẩn |

### F. Quản lý lead

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-051 | Tìm kiếm/lọc lead theo dữ liệu được phép xem. | Đăng nhập vai trò có quyền; tìm theo tên/số test và lọc trạng thái/loại yêu cầu; đối chiếu danh sách. | Kết quả khớp bộ lọc, chỉ chứa lead trong phạm vi quyền; số điện thoại không lộ ở vai trò không được phép. | P1 | Bị chặn — cần tài khoản/ma trận quyền |
| TC-052 | Chuyển trạng thái hợp lệ/không hợp lệ. | Với lead QA, chuyển theo quy trình được duyệt; thử giá trị trạng thái không được cung cấp qua giao diện/URL. | Trạng thái hợp lệ cập nhật và ghi nhận lịch sử theo đặc tả; trạng thái sai bị từ chối; không sửa lead ngoài quyền. | P1 | Bị chặn — cần workflow và vai trò được phép |
| TC-053 | Gán lead đơn/hàng loạt và xác nhận nhân viên. | Gán lead QA cho sale được phép; nếu có thao tác hàng loạt, chọn một tập nhỏ QA rồi gán; đăng nhập tài khoản sale nhận. | Chỉ nhân viên đủ điều kiện được chọn; lead được gán đúng; thay đổi hiển thị trong CRM/timeline. | P1 | Cần xác nhận — giao diện gán hàng loạt chưa được xác minh |
| TC-054 | Ghi chú, lịch hẹn, timeline và cảnh báo trùng. | Thêm ghi chú QA có dấu/ký tự đặc biệt; nhập lịch hẹn; mở lại chi tiết; tạo yêu cầu lặp theo chính sách được duyệt. | Ghi chú/lịch hẹn gắn đúng lead và người thao tác; timeline theo dữ liệu thực; cảnh báo trùng theo quy tắc được chủ dự án xác nhận. | P1 | Cần xác nhận — cần định nghĩa timeline, lịch hẹn và trùng |
| TC-055 | CSV đúng cột, mã hóa và phạm vi. | Export tập lead QA bằng vai trò được cho phép; mở file UTF-8 trong bảng tính; kiểm tra tên có dấu, headers và records. | Headers/dữ liệu đúng đặc tả; tiếng Việt không lỗi mã hóa; không có formula injection khi mở CSV; không chứa lead ngoài quyền. | P1 | Bị chặn — cần chính sách quyền/phạm vi export |
| TC-056 | Gọi/Zalo một chạm trên mobile. | Trên thiết bị QA 390px, mở lead được gán; chạm nút gọi/Zalo; kiểm tra URL scheme/ứng dụng đích mà không gửi tin tới khách thật. | `tel:` dùng số đúng; liên kết Zalo mở đúng đích; nút có nhãn/focus và không dùng dữ liệu giả. | P2 | Chưa chạy |

### G. Quản lý xe, khuyến mãi và trình dựng trang

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-057 | Tạo/sửa/publish xe và phiên bản QA. | Với tài khoản marketing/admin được phép, tạo dữ liệu QA tối thiểu; lưu nháp; publish theo quyền; xem website công khai. | Validation rõ; dữ liệu công khai chỉ xuất hiện sau publish; không làm lộ dữ liệu nháp; thao tác được ghi audit nếu có đặc tả. | P1 | Bị chặn — cần tài khoản và quy trình dọn dữ liệu |
| TC-058 | Ảnh upload: MIME, kích thước và định dạng. | Upload ảnh QA hợp lệ; thử file không phải ảnh đổi đuôi thành `.jpg`, `.php`/`.html`, và file vượt giới hạn staging cho phép. | Chỉ loại/kích thước được phép được chấp nhận; file không thực thi; lỗi không làm lộ path nội bộ. Không dùng file có mã độc. | P1 | Chưa chạy |
| TC-059 | Lịch sử giá và thay đổi cấu hình xe. | Cập nhật giá của phiên bản QA theo quy trình; xem lịch sử giá trước/sau và website. | Giá mới/sự kiện thay đổi hiển thị đúng; lịch sử có người/thời điểm/giá cũ-mới theo đặc tả. Nếu chưa có tính năng, xin xác nhận phạm vi trước khi lập bug. | P1 | Cần xác nhận — yêu cầu có lịch sử giá nhưng chưa xác minh trên staging |
| TC-060 | Trình dựng khối, thứ tự, nháp và preview responsive. | Tạo trang QA; thêm/sắp xếp/xóa một khối; lưu nháp; xem preview ở Mobile/Desktop; không publish. | Thứ tự/nội dung khớp preview; nháp không xuất hiện public; thiếu trường không làm vỡ trang. | P1 | Bị chặn — cần tài khoản và nội dung QA |
| TC-061 | Publish, cache và khôi phục phiên bản. | Publish thay đổi QA; kiểm tra website sau purge/TTL cache được duyệt; khôi phục revision trước. | Bản publish đúng và cập nhật trong thời gian cache quy định; rollback khôi phục bản cũ; có dấu vết phiên bản. | P1 | Bị chặn — cần tài khoản/quy trình publish an toàn |

### H. Giao diện và tương thích

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-062 | Kiểm tra bố cục ở 390/768/1360px trên các trang chính. | Mở trang chủ, danh mục, chi tiết, form và landing; đo overflow/chồng lấn ở từng viewport; chụp ảnh. | Không tràn ngang ngoài thành phần cuộn có chủ đích; nội dung/nút/ảnh không chồng hoặc bị cắt. | P1 | Chưa chạy |
| TC-063 | Kích thước vùng chạm và thanh CTA mobile. | Ở 390px đo các nút chính và thanh Gọi/Zalo/Báo giá; kiểm tra target tối thiểu 44×44px. | Vùng chạm đạt tối thiểu 44px; Gọi/Zalo chỉ hoạt động khi có liên hệ hợp lệ, nếu placeholder thì trạng thái được giải thích chứ không giả vờ gọi. | P1 | Chưa chạy |
| TC-064 | Menu mobile và trạng thái tương tác. | Mở/đóng menu bằng touch và bàn phím; thử hover/focus/disabled/loading/error/empty nơi có thể. | Menu mở/đóng ổn định, trạng thái dễ nhận biết; focus không mất; không có submit lặp do nút loading. | P2 | Chưa chạy |
| TC-065 | Link gãy, ảnh thiếu và trang lỗi. | Duyệt link nội bộ chính; kiểm tra network ảnh; truy cập slug không tồn tại và route public sai. | Link/ảnh hợp lệ; 404 có nội dung hướng dẫn; không lộ exception/path/stack trace ở lỗi server. | P2 | Chưa chạy |
| TC-066 | Nội dung tiếng Việt và ảnh. | Kiểm tra tiêu đề/dài, ký tự tiếng Việt, placeholder, ảnh có tỷ lệ đa dạng trên màn hình nhỏ/lớn. | Chữ không mất dấu; ảnh không méo và có cách cắt hợp lý; placeholder không bị hiểu là dữ liệu thật. | P2 | Chưa chạy |

### I. Truy cập (accessibility)

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-067 | Điều hướng bàn phím và focus. | Không dùng chuột; lần Tab/Shift+Tab qua header, menu, form, modal, slider; dùng Enter/Esc khi phù hợp. | Thứ tự focus logic; focus nhìn thấy; không trap ngoài modal; Esc đóng thành phần có thể đóng; bỏ qua tới nội dung hoạt động. | P1 | Chưa chạy |
| TC-068 | Nhãn form, alt ảnh, lỗi và thông báo. | Dùng accessibility tree/screen reader; kiểm tra input, ảnh, validation, trạng thái tải/thành công. | Trường có label/accessible name; ảnh thông tin có alt phù hợp (ảnh trang trí alt rỗng); thông báo lỗi được liên kết/đọc được. | P1 | Chưa chạy |
| TC-069 | Tương phản và hero slider. | Đo contrast cho text/nút thường và focus; thao tác slider bằng bàn phím; tìm nút pause/resume và kiểm tra reduced motion. | Nội dung đạt WCAG AA áp dụng; slider có điều khiển pause và tên truy cập; animation tôn trọng cài đặt giảm chuyển động. | P2 | Chưa chạy |

### J. SEO và theo dõi

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-070 | SEO metadata theo trang. | Kiểm tra home, danh mục, xe, landing và 404; xem title, description, canonical, Open Graph/share image. | Metadata duy nhất/phù hợp URL public; canonical không trỏ sai staging/production; trang lỗi có quy tắc index rõ. | P2 | Chưa chạy |
| TC-071 | Sitemap, robots và trang quản trị. | Tải `/sitemap.xml`, `/robots.txt`; thử crawl/login/admin khi chưa đăng nhập; kiểm tra robots và `noindex`/response. | Sitemap chỉ liệt kê URL public canonical; robots đúng; admin không được index và không lộ nội dung khi chưa xác thực. | P1 | Chưa chạy |
| TC-072 | Meta/GA4/GTM chỉ gửi sự kiện được phép và đúng một lần. | Dùng browser network/Pixel Helper/GA DebugView trong môi trường test đã cấu hình; thử page view và lead thành công với consent phù hợp. | Event/ID và event_id dedup khớp cấu hình; event Lead chỉ một lần; không gửi PII không được phép. | P1 | Bị chặn — cần pixel/test property và consent policy |
| TC-073 | Không gửi event khi không consent, lỗi trang hoặc form invalid. | Tắt marketing consent; tải trang; gửi form thiếu trường; mở route lỗi QA; quan sát request analytics/Meta. | Không phát sinh marketing event khi chưa đồng ý hoặc form không thành công theo policy; không gọi endpoint thật ngoài staging. | P1 | Bị chặn — cần analytics sink/test property |

### K. Hiệu năng cảm nhận

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-074 | Đo Lighthouse mobile trên home, xe và landing. | Chạy Lighthouse với cùng cấu hình/device/network; ghi LCP, CLS, INP và report ID cho từng trang. | Ghi số đo thực; đối chiếu mục tiêu LCP ≤2.5s, CLS ≤0.1, INP ≤200ms; không suy đoán khi metric không có. | P2 | Bị chặn — cần URL staging và công cụ Lighthouse |
| TC-075 | Dùng được trên mạng chậm. | Giả lập throttling 3G theo preset đã ghi; mở trang và submit lead QA một lần; quan sát loading/retry. | Nội dung chính đọc được, form không mất dữ liệu, có phản hồi chờ/lỗi hợp lý; không tạo request/lead trùng khi mạng chập chờn. | P1 | Chưa chạy |

### L. Bảo mật nhìn từ ngoài (hộp đen an toàn)

| Mã | Mục tiêu / điều kiện trước | Các bước | Kết quả mong đợi | Ưu tiên | Kết quả |
|---|---|---|---|---|---|
| TC-076 | Kiểm tra XSS an toàn ở các form public và nội dung hiển thị. | Dùng chuỗi vô hại nhận biết như `<b>QA</b>` và ký tự đặc biệt trong trường cho phép; kiểm tra lại giao diện và HTML. | Nội dung được escape/sanitize đúng ngữ cảnh; không thực thi script/HTML chủ động. Không dùng payload khai thác hoặc lưu nội dung nguy hiểm. | P1 | Chưa chạy |
| TC-077 | Tài nguyên nhạy cảm không công khai. | Gửi GET đơn lẻ tới `/.env`, `/storage`, tên backup/log đã biết trong staging; không dò quét đường dẫn hàng loạt. | Không trả secret, backup, log hoặc file nội bộ; response 403/404 phù hợp; không để lộ absolute path. | P1 | Chưa chạy |
| TC-078 | Upload sai loại/quá dung lượng. | Thực hiện các trường hợp TC-058 qua UI upload staging với file vô hại và kích thước được duyệt. | File không hợp lệ bị từ chối; nội dung upload không thực thi; file hợp lệ được đổi tên/lưu theo chính sách. | P1 | Chưa chạy |
| TC-079 | Replay/thay đổi tham số và tách biệt hai tài khoản. | Phát lại một request QA đã gửi một lần; thay ID đối tượng sang fixture khác; truy cập bằng tài khoản hai vai trò khác nhau. | CSRF/authorization/idempotency theo đặc tả; không tạo tác động ngoài ý muốn hoặc đọc/sửa dữ liệu vượt quyền. Chỉ request ít lần, không brute-force. | P1 | Bị chặn — cần tài khoản, quyền và quy tắc replay |

## 3. Phiếu lỗi và báo cáo thực thi

- **Phiếu lỗi:** chưa có. Chưa chạy staging; không tạo bug giả định. Khi phát hiện, tạo **một phiếu cho một lỗi**, gồm môi trường/build, bước tái hiện, thực tế, mong đợi, tần suất, bằng chứng đã che dữ liệu nhạy cảm, ca liên quan và mức độ.
- **Tóm tắt 5 dòng:**
  1. Tổng số ca: **79**.
  2. Đã chạy: **0**; đạt: **0**.
  3. Không đạt: **0** (chưa thực thi, không đồng nghĩa không có lỗi).
  4. Bị chặn: **24**; cần xác nhận: **5**.
  5. Chưa chạy: **50**; lỗi đã tái hiện: **0** — chưa thể thống kê mức độ lỗi.
- **Lỗi mở:** 0 lỗi đã tái hiện. Số 0 này không có nghĩa các luồng đã đạt.
- **Khuyến nghị phát hành:** **Chưa đủ cơ sở đánh giá** vì chưa có staging/tài khoản và chưa thực thi; không kết luận “Có thể ra mắt”.
- **Hồi quy:** sau mỗi bản sửa, chạy lại ca lỗi, toàn bộ ca P1 và các ca cùng mô-đun; ghi build trước/sau và bằng chứng.

### Mẫu phiếu lỗi dùng khi thực thi

- **Mã lỗi / tiêu đề:**
- **Mô-đun / trang:**
- **Môi trường:** URL staging, build, trình duyệt, thiết bị, viewport, vai trò (không ghi mật khẩu/OTP).
- **Mức nghiêm trọng / ưu tiên:**
- **Điều kiện trước:**
- **Các bước tái hiện:**
- **Kết quả thực tế:**
- **Kết quả mong đợi:**
- **Tần suất:** luôn luôn hoặc x/10 lần.
- **Bằng chứng:** ảnh/video, console/network/log đã che PII/token, mã phản hồi.
- **Ca kiểm thử liên quan:**
- **Ghi chú / gợi ý nguyên nhân (không bắt buộc):**

## 4. Rủi ro còn lại và câu hỏi cho chủ dự án

1. Cung cấp URL staging HTTPS, mã build và xác nhận môi trường không trỏ đến production; cấp tài khoản QA riêng cho 4 vai trò qua kho bí mật an toàn, kèm MFA/OTP sandbox nếu có.
2. Xin ma trận quyền đã duyệt: quyền CRUD/menu, xem/gán lead, đổi trạng thái, ghi chú, export CSV; sale xem lead được gán hay toàn bộ; quy trình timeout, lockout và mở khóa?
3. Quy tắc chống trùng/gửi lặp số điện thoại là gì: cửa sổ thời gian, gộp hay từ chối, cách xử lý khi khác xe/loại yêu cầu?
4. Cung cấp dữ liệu chuẩn đã duyệt cho giá/phiên bản, lãi suất/kỳ hạn, công thức trả góp và cách làm tròn; bảng phí từng tỉnh, ngày hiệu lực và múi giờ?
5. Có notification sink email/Telegram, số điện thoại/xe QA và quy tắc dọn dữ liệu test không? Cho phép các side effect staging nào (mô phỏng mất mạng, thử lockout) và giới hạn bao nhiêu?
