# News Portal — Docker Compose

Website tin tức triển khai bằng WordPress, MySQL và phpMyAdmin; Nginx làm reverse proxy HTTPS; Prometheus/Grafana theo dõi container, Nginx và MySQL; Loki/Promtail thu thập log Nginx.

## Thông tin đề tài

- **Đề tài:** Đề 19 — Website Tin tức / Cổng thông tin
- **Sinh viên:** Hứa Văn Quyết
- **Mã sinh viên:** DTC245200263
- **Học phần:** Triển khai và Quản trị Hệ thống Phần mềm
- **Trường:** Đại học Công nghệ Thông tin và Truyền thông Thái Nguyên (ICTU)

## Kiến trúc

```text
Browser ── HTTPS :8443 ──> Nginx ──> WordPress ──> MySQL
                                └──> phpMyAdmin

Prometheus <── cAdvisor, Nginx exporter, MySQL exporter
Grafana ──> Prometheus datasource + Loki datasource
Nginx log volume ──> Promtail ──> Loki
```

Compose tách mạng `frontend-net`, `backend-net` (internal) và `monitoring-net`. MySQL không publish cổng ra máy host; website, Grafana và Prometheus chỉ bind vào loopback. phpMyAdmin chỉ truy cập qua Nginx. Grafana được nạp sẵn hai datasource và dashboard tổng quan.

WordPress dùng theme tin tức riêng tại `wordpress/themes/news-portal`, được mount chỉ đọc vào container. Theme hiển thị bài nổi bật, bài mới, chuyên mục, trang chi tiết và tìm kiếm; nội dung vẫn được biên tập trong trang quản trị WordPress. Một số bài viết mẫu tiếng Việt được thêm vào database demo.

## Yêu cầu

- Docker Desktop hoặc Docker Engine cùng Docker Compose v2 (plugin `docker compose` hoặc lệnh `docker-compose`)
- OpenSSL (hoặc Docker để chạy image OpenSSL tạo chứng chỉ)
- Cổng host 3000, 8080, 8443 và 9090 chưa bị ứng dụng khác sử dụng

## Cài đặt và chạy

Mở PowerShell trong thư mục `news-portal`:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
notepad .env
```

Nếu vừa tạo file, thay các giá trị `CHANGE_ME` bằng các mật khẩu ngẫu nhiên khác nhau (dài tối thiểu 24 ký tự). Nếu đã có `.env` và database volume cũ, giữ nguyên thông tin DB đang dùng; đảm bảo bổ sung `MYSQL_EXPORTER_PASSWORD`, `GRAFANA_ADMIN_USER`, `GRAFANA_ADMIN_PASSWORD` nếu thiếu. Không commit `.env`.

Tạo chứng chỉ tự ký cho localhost. Lệnh Docker này hoạt động trên Windows/Docker Desktop và Linux:

```powershell
New-Item -ItemType Directory -Force nginx/certs | Out-Null
docker run --rm -v "${PWD}/nginx/certs:/certs" alpine/openssl:3.3.2 req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /certs/selfsigned.key -out /certs/selfsigned.crt -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
```

Khởi động các dịch vụ:

```powershell
docker-compose up -d
docker-compose ps
```

Lần khởi tạo MySQL đầu tiên chạy script tạo tài khoản `metrics` riêng cho exporter. Nếu đã có volume MySQL từ lần chạy trước, script init không chạy lại. Kết nối bằng MySQL root và chạy các lệnh SQL sau, thay `<PASSWORD>` bằng `MYSQL_EXPORTER_PASSWORD` trong `.env`:

```sql
CREATE USER IF NOT EXISTS 'metrics'@'%' IDENTIFIED BY '<PASSWORD>';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'metrics'@'%';
```

Grafana cũng lưu tài khoản trong volume riêng; đổi `GRAFANA_ADMIN_PASSWORD` trong `.env` không tự đổi mật khẩu đã lưu từ lần chạy trước. Với volume cũ, dùng mật khẩu Grafana hiện có hoặc đổi mật khẩu trong giao diện Grafana.

## Địa chỉ truy cập

| Thành phần | Địa chỉ | Đăng nhập |
|---|---|---|
| Website | <https://localhost:8443> | Thiết lập WordPress ở lần đầu |
| phpMyAdmin | <https://localhost:8443/pma/> | User/password MySQL trong `.env` |
| Grafana | <http://localhost:3000> | `GRAFANA_ADMIN_USER` / `GRAFANA_ADMIN_PASSWORD` trong `.env` |
| Prometheus | <http://localhost:9090> | Không yêu cầu đăng nhập trong môi trường demo |

Trình duyệt sẽ cảnh báo chứng chỉ tự ký; xác nhận ngoại lệ cho môi trường localhost. Cổng HTTP 8080 chuyển hướng sang HTTPS 8443.

## Kiểm tra metrics và log

Trong Prometheus, mở **Status → Targets**. Các job cần xuất hiện là `prometheus`, `cadvisor-containers`, `mysql-database` và `nginx-web`.

Trong Grafana, mở **Dashboards → News portal → News Portal - System Overview**. Datasource Prometheus và Loki được provision tự động. Đợi một vài scrape interval để các panel có dữ liệu.

Để tạo log, truy cập trang web và một đường dẫn không tồn tại, ví dụ `/not-found-demo`. Mở Grafana **Explore**, chọn Loki và chạy các truy vấn trong [`docs/LOGQL.md`](docs/LOGQL.md):

```logql
{job="nginx"}
{job="nginx"} |= " 404 "
sum by (service) (rate({job="nginx"} [5m]))
```

## Dừng và dữ liệu

```powershell
docker-compose down
```

Lệnh trên giữ lại database, WordPress, metrics và log trong named volumes. Chỉ khi chấp nhận xóa toàn bộ dữ liệu demo mới chạy `docker-compose down -v`.

## Hardening đã cấu hình

- MySQL nằm trong backend network nội bộ; không publish cổng DB ra host.
- User ứng dụng WordPress riêng; exporter dùng user `metrics` chỉ có quyền đọc/đọc trạng thái cần thiết.
- Nginx áp dụng HTTPS, TLS 1.2/1.3, security headers và ẩn phiên bản server.
- phpMyAdmin chỉ đi qua reverse proxy; Promtail đọc log qua named volume, không mount đường dẫn hệ thống host.
- Prometheus/Grafana chạy bằng UID không phải root; các dịch vụ có `no-new-privileges` khi tương thích.
- `.env` và khóa riêng TLS được loại khỏi Git bằng `.gitignore`.

## GitHub và ảnh minh chứng

Không đưa `.env`, `nginx/certs/*.key`, dữ liệu volume hoặc mật khẩu vào repository. Vì các commit cũ từng chứa `.env` và private key, **không push nguyên lịch sử hiện tại**. Tạo repository sạch/lịch sử sạch trước khi push; thay mọi mật khẩu đã từng commit và tạo khóa TLS mới.

Sau khi chạy demo, tự chụp và lưu ảnh vào `screenshots/` (ví dụ: website, phpMyAdmin, Grafana dashboard, Prometheus Targets, Loki Explore/LogQL, security headers). Trước khi commit ảnh, kiểm tra ảnh không chứa mật khẩu, token hoặc dữ liệu cá nhân ngoài thông tin cần nộp.

Bản thảo báo cáo 10 phần/trang và các vị trí cần chèn minh chứng nằm tại [`report/bao-cao.md`](report/bao-cao.md). Điền bảng kết quả bằng số liệu quan sát được, chèn ảnh chụp thật, rồi dàn trang/xuất sang DOCX hoặc PDF trước khi nộp.

## Giới hạn demo

Ứng dụng dùng WordPress làm CMS và theme tin tức tự phát triển trong repository; bài viết, chuyên mục và tài khoản quản trị được quản lý từ WordPress. Chứng chỉ là tự ký và cấu hình chỉ phù hợp demo localhost, không dùng làm cấu hình production.
