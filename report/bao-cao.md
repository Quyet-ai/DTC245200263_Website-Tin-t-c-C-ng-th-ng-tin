# BÁO CÁO ĐỀ TÀI — ĐỀ 19

## Website Tin tức / Cổng thông tin triển khai bằng Docker Compose

**Sinh viên:** Hứa Văn Quyết  
**Mã sinh viên:** DTC245200263  
**Học phần:** Triển khai và Quản trị Hệ thống Phần mềm  
**Trường:** Đại học Công nghệ Thông tin và Truyền thông Thái Nguyên (ICTU)  
**Năm học:** 2024–2028

> Bản thảo 10 trang theo bố cục báo cáo. Thay các phần `[CHÈN ẢNH ...]` bằng ảnh chụp màn hình sau khi tự chạy và xác nhận hệ thống. Không ghi kết quả là thành công nếu chưa kiểm tra thực tế.

---

# Trang 2 — Tóm tắt và mục tiêu

## Tóm tắt

Đề tài xây dựng môi trường triển khai cổng thông tin tin tức bằng Docker Compose. WordPress cung cấp ứng dụng quản lý bài viết và danh mục; MySQL lưu dữ liệu; phpMyAdmin hỗ trợ quản trị dữ liệu qua giao diện web. Nginx tiếp nhận HTTP/HTTPS và chuyển tiếp yêu cầu đến WordPress hoặc phpMyAdmin. Prometheus thu thập metrics từ cAdvisor, Nginx exporter và MySQL exporter. Grafana hiển thị dashboard đã provision. Loki và Promtail tập trung log Nginx để truy vấn bằng LogQL.

Các cấu hình được chia theo dịch vụ và lưu trong repository. Mật khẩu triển khai cục bộ đặt trong `.env`; file này và khóa riêng TLS phải được loại khỏi Git. Môi trường dùng chứng chỉ tự ký nên chỉ phù hợp minh họa trên localhost.

## Mục tiêu

1. Khởi chạy ứng dụng, cơ sở dữ liệu và công cụ quản trị bằng Compose.
2. Đặt Nginx làm điểm truy cập web, có HTTPS và security headers.
3. Thu thập metrics container, web server và database, trình bày trên Grafana.
4. Thu thập log Nginx bằng Promtail, lưu và truy vấn qua Loki.
5. Áp dụng phân vùng mạng, tài khoản DB có quyền giới hạn, non-root ở dịch vụ hỗ trợ và quản lý bí mật ngoài Git.

## Phạm vi

Ứng dụng sử dụng WordPress làm hệ quản trị nội dung, kèm theme tin tức riêng trong `wordpress/themes/news-portal`. Giao diện có trang chủ bài nổi bật, danh sách tin mới, chuyên mục và trang chi tiết bài viết. Các bài viết mẫu trong database giúp minh họa giao diện; quản trị viên có thể biên tập hoặc thay thế chúng từ trang quản trị WordPress.

---

# Trang 3 — Kiến trúc tổng thể

```text
Người dùng ── HTTPS :8443 ──> Nginx
                                  ├──> WordPress ──> MySQL
                                  └──> phpMyAdmin ──> MySQL

cAdvisor ──────────────┐
Nginx exporter ─────────┼──> Prometheus ──> Grafana
MySQL exporter ────────┘                    └──> Loki datasource

Nginx log volume ──> Promtail ──> Loki
```

Compose tạo ba bridge network. `frontend-net` kết nối Nginx với ứng dụng và phpMyAdmin. `backend-net` được đánh dấu internal để MySQL chỉ nhận kết nối trong mạng backend. `monitoring-net` kết nối Prometheus, Grafana, Loki, Promtail và các exporter. Exporter cần đồng thời kết nối mạng monitoring và dịch vụ cần đọc metrics.

Các volume lưu database, tệp WordPress, dữ liệu Prometheus/Grafana/Loki, log Nginx và dữ liệu trạng thái của Promtail. `docker compose down` giữ lại dữ liệu; `docker compose down -v` xóa các volume và chỉ dùng khi chấp nhận khởi tạo lại toàn bộ.

**[CHÈN HÌNH 3.1 — Sơ đồ kiến trúc vẽ lại hoặc ảnh danh sách dịch vụ Compose]**

## Thành phần

| Dịch vụ | Vai trò |
|---|---|
| `nginx` | HTTPS, security headers, reverse proxy, ghi access/error log |
| `app` | WordPress và PHP/Apache |
| `mysql` | Lưu dữ liệu website |
| `phpmyadmin` | Giao diện quản lý MySQL |
| `prometheus` | Scrape và lưu metrics |
| `cadvisor` | Metrics tài nguyên container |
| `nginx-exporter` | Metrics trạng thái kết nối Nginx |
| `mysqld-exporter` | Metrics MySQL bằng tài khoản `metrics` |
| `grafana` | Dashboard metrics và Explore log |
| `loki`, `promtail` | Lưu trữ và chuyển log tập trung |

---

# Trang 4 — Triển khai ứng dụng và cơ sở dữ liệu

WordPress kết nối tới MySQL qua tên dịch vụ Compose `mysql:3306`. Các biến kết nối được cung cấp từ `.env`, không ghi mật khẩu trực tiếp trong cấu hình công khai. MySQL khởi tạo database và user ứng dụng ở lần chạy đầu tiên. Dữ liệu được lưu trong volume `db_data`; nội dung WordPress nằm trong `wp_data`.

phpMyAdmin được gắn vào backend để kết nối database và frontend để nhận request từ Nginx. Không publish cổng MySQL hoặc phpMyAdmin trực tiếp ra host. Người dùng vào phpMyAdmin qua đường dẫn `/pma/` của Nginx và đăng nhập bằng tài khoản database phù hợp.

## Quy trình triển khai

1. Cài Docker Desktop và Compose v2.
2. Tạo `.env` từ `.env.example`, thay các mật khẩu mẫu bằng giá trị riêng và đủ dài.
3. Tạo chứng chỉ localhost và private key mới.
4. Chạy `docker compose up -d`, chờ container khởi động, sau đó chạy `docker compose ps`.
5. Mở website, hoàn tất màn hình thiết lập WordPress và tạo tài khoản quản trị mạnh.
6. Đăng nhập phpMyAdmin, xác nhận database và các bảng WordPress đã được tạo.

**[CHÈN HÌNH 4.1 — Website sau khi cài đặt WordPress]**

**[CHÈN HÌNH 4.2 — phpMyAdmin hiển thị database/bảng WordPress; che thông tin nhạy cảm]**

> Cần ghi lại trạng thái thực tế của lần chạy: thời gian khởi động, các dịch vụ healthy/running và lỗi nếu có. Không sử dụng ảnh minh họa lấy từ nguồn khác làm bằng chứng hệ thống.

---

# Trang 5 — Reverse proxy, HTTPS và bảo mật web

Nginx publish cổng 8080 cho HTTP và 8443 cho HTTPS. HTTP được chuyển hướng sang HTTPS. Nginx proxy `/` đến WordPress và `/pma/` đến phpMyAdmin, đồng thời truyền các header `Host`, `X-Real-IP`, `X-Forwarded-For` và `X-Forwarded-Proto` để backend nhận biết giao thức và địa chỉ yêu cầu.

Chứng chỉ được tự ký với tên `localhost`, dùng TLS 1.2/1.3. Trình duyệt sẽ báo không tin cậy vì chứng chỉ không do CA công cộng cấp; đây là hành vi dự kiến của demo cục bộ. Nginx thêm `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` và tắt thông tin phiên bản server.

Nginx có endpoint `stub_status` phục vụ exporter. Endpoint giới hạn theo subnet Docker và không được dùng như trang web công khai.

## Cách xác nhận

Mở `https://localhost:8443`, chấp nhận ngoại lệ chứng chỉ localhost. Trong DevTools hoặc bằng `curl.exe -k -I https://localhost:8443`, kiểm tra status và các security headers. Mở `https://localhost:8443/pma/` để xác nhận route phpMyAdmin.

**[CHÈN HÌNH 5.1 — Website truy cập qua HTTPS và cảnh báo chứng chỉ tự ký]**

**[CHÈN HÌNH 5.2 — Response headers có security headers]**

---

# Trang 6 — Prometheus và Grafana

Prometheus scrape định kỳ các job `prometheus`, `cadvisor-containers`, `mysql-database` và `nginx-web`. cAdvisor cung cấp thông tin tài nguyên container. Nginx exporter đọc `stub_status`. MySQL exporter kết nối bằng user `metrics`, được tạo khi MySQL khởi tạo lần đầu và chỉ có quyền cần cho việc đọc trạng thái/metrics.

Grafana được cấu hình sẵn datasource Prometheus và Loki. Dashboard `News Portal - System Overview` hiển thị xu hướng CPU và bộ nhớ container, tình trạng exporter MySQL, số kết nối Nginx và số thread MySQL đang kết nối. Panel cần thời gian để Prometheus scrape dữ liệu; nếu trống, xem Targets và log exporter trước khi kết luận dashboard lỗi.

## Cách xác nhận

Trong Prometheus chọn **Status → Targets** và xác nhận các job ở trạng thái `UP`. Trong Grafana mở **Dashboards → News portal → News Portal - System Overview**. Tải lại trang web để tạo hoạt động, sau đó quan sát metrics thay đổi.

**[CHÈN HÌNH 6.1 — Prometheus Targets, thấy các job ở trạng thái UP]**

**[CHÈN HÌNH 6.2 — Grafana dashboard có dữ liệu container, Nginx và MySQL]**

---

# Trang 7 — Loki, Promtail và truy vấn LogQL

Nginx ghi access log và error log vào named volume `nginx_logs`. Promtail mount cùng volume ở chế độ chỉ đọc, theo dõi các file log và đẩy bản ghi tới Loki qua mạng Compose. Cách dùng named volume tránh phụ thuộc đường dẫn `/var/log/nginx` của hệ điều hành host và phù hợp Docker Desktop.

Trong Grafana Explore, chọn Loki và khoảng thời gian gần đây. Các truy vấn mẫu:

```logql
{job="nginx"}
```

```logql
{job="nginx"} |= " 404 "
```

```logql
sum by (service) (rate({job="nginx"} [5m]))
```

Truy cập website bình thường để sinh request log, sau đó vào một đường dẫn không tồn tại như `/not-found-demo` để tạo status 404. Truy vấn thứ hai lọc các dòng có mã 404; truy vấn thứ ba tính tốc độ log theo service trong cửa sổ năm phút.

**[CHÈN HÌNH 7.1 — Explore với truy vấn `{job="nginx"}` và kết quả]**

**[CHÈN HÌNH 7.2 — Truy vấn lọc 404 hoặc biểu đồ rate]**

---

# Trang 8 — Hardening

Các biện pháp đã được cấu hình:

1. Tách frontend, backend và monitoring; backend đánh dấu internal. Database không publish cổng ra host.
2. WordPress dùng user ứng dụng riêng; MySQL exporter sử dụng user `metrics` với quyền giới hạn thay vì root.
3. Prometheus và Grafana chạy bằng UID không phải root. Các container phù hợp được đặt `no-new-privileges`.
4. Nginx chỉ mở các cổng web cần cho demo, dùng HTTPS/TLS 1.2+, security headers và ẩn phiên bản.
5. phpMyAdmin chỉ đi qua reverse proxy. Log được chia sẻ bằng volume, không mount thư mục hệ thống host vào Promtail.
6. Mật khẩu được giữ trong `.env`; `.env` và private key nằm trong `.gitignore`. Chỉ `.env.example` với giá trị minh họa được đưa lên Git.

cAdvisor cần quyền cao và mount thông tin host để quan sát tài nguyên container; đây là ngoại lệ quyền cần thiết cho mục tiêu giám sát trong bài demo. Chứng chỉ tự ký và các dashboard không có đăng nhập bổ sung cho Prometheus/Loki chỉ phù hợp môi trường localhost, không triển khai trực tiếp ra Internet.

## Kiểm tra

Kiểm tra Compose không publish cổng 3306; user exporter không phải root; file `.env` và `*.key` không nằm trong danh sách file chuẩn bị commit. Kiểm tra Nginx response headers và truy cập phpMyAdmin qua HTTPS.

**[CHÈN HÌNH 8.1 — Minh chứng network/ports hoặc danh sách container và biến đã che mật khẩu]**

---

# Trang 9 — Kết quả kiểm thử và minh chứng

Hoàn thiện bảng sau dựa trên lần chạy thực tế. Ghi `Đạt` chỉ khi đã thao tác và quan sát được kết quả.

| Hạng mục | Kết quả thực tế | Ảnh minh chứng |
|---|---|---|
| `docker-compose ps` | 11 dịch vụ đang `Up`; cAdvisor `healthy` | `screenshots/03-compose.png` |
| Website qua HTTPS | HTTP 200; security headers có mặt | `screenshots/01-website.png` |
| Kết nối MySQL/phpMyAdmin | kiểm tra mysqli trả `DB_CONNECT_OK`; phpMyAdmin HTTP 200 | `screenshots/02-phpmyadmin.png` |
| Prometheus Targets | 4/4 job `UP` (Prometheus, cAdvisor, MySQL, Nginx) | `screenshots/04-prometheus-targets.png` |
| Grafana dashboard | API xác nhận 5 dashboard; datasource được provision | `screenshots/05-grafana-dashboard.png` |
| Loki truy vấn LogQL | 3 query hoạt động; query lọc 404 trả log minh chứng | `screenshots/06-logql.png` |
| Security headers | X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy | `screenshots/07-security-headers.png` |

## Sự cố gặp phải và xử lý

Ghi lại lỗi thực tế từ `docker compose logs <service>`, nguyên nhân và cách khắc phục. Một số điểm cần lưu ý: init SQL chỉ chạy khi volume MySQL rỗng; Grafana có thể giữ mật khẩu trong volume cũ; Prometheus cần thời gian scrape; chứng chỉ tự ký sẽ sinh cảnh báo trên trình duyệt.

**[CHÈN HÌNH 9.1 — Bảng dịch vụ đang chạy hoặc log khởi động đã xử lý]**

---

# Trang 10 — Quản lý mã nguồn, kết luận và hướng phát triển

Repository cần chứa Compose, cấu hình Nginx, Prometheus, Grafana, Loki, Promtail, script init và README. Lịch sử Git cần ba commit có ý nghĩa theo thứ tự: (1) ứng dụng, database, Nginx/HTTPS; (2) Prometheus, exporters, Grafana; (3) Loki, Promtail, LogQL. Trước khi push, cần làm sạch lịch sử cũ do `.env` và private key từng được commit; `.gitignore` không tự xóa các file khỏi commit trước đó. Thay mật khẩu đã lộ và tạo khóa TLS mới.

## Kết luận

Thiết kế cung cấp một chuỗi triển khai cục bộ gồm ứng dụng, DB, reverse proxy, monitoring và centralized logging. Dashboard và truy vấn log được chuẩn bị để minh họa, còn kết quả cuối cùng cần được xác nhận qua lần chạy Docker Compose và ảnh chụp thật. Bản demo dùng WordPress CMS với giao diện tin tức riêng; chứng chỉ tự ký chỉ phù hợp cho localhost, không dùng cho production.

## Hướng phát triển

- Tùy biến giao diện WordPress, tạo danh mục và bài viết mẫu cho đúng chủ đề cổng tin tức.
- Thêm backup/restore database và retention phù hợp cho metrics/log.
- Bổ sung xác thực cho các giao diện quản trị và giới hạn truy cập bằng firewall.
- Dùng chứng chỉ CA hợp lệ, secret manager, image scan và cấu hình non-root cho ứng dụng khi tương thích.
- Bổ sung cảnh báo Prometheus/Grafana và kiểm thử khôi phục dữ liệu.

## Tài liệu tham khảo

- Docker Compose Documentation — <https://docs.docker.com/compose/>
- Prometheus Documentation — <https://prometheus.io/docs/>
- Grafana Loki Documentation — <https://grafana.com/docs/loki/latest/>
- Nginx Documentation — <https://nginx.org/en/docs/>

**[CHÈN HÌNH 10.1 — Repository GitHub sau khi lịch sử đã làm sạch, README và ba commit]**
