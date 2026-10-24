# BÀI TẬP LỚN: TRIỂN KHAI VÀ QUẢN TRỊ HỆ THỐNG PHẦN MỀM

## ĐỀ TÀI 19: WEBSITE TIN TỨC / CỔNG THÔNG TIN (WORDPRESS)

- **Sinh viên thực hiện:** Hứa Văn Quyết
- **Mã số sinh viên (MSSV):** DTC245200263
- **Lớp:** CNTT K23
- **Khoa:** Công nghệ Thông tin - Trường Đại học Công nghệ Thông tin & Truyền thông (ICTU)
- **Repository GitHub:** [https://github.com/Quyet-ai/DTC245200263_Website-Tin-t-c-C-ng-th-ng-tin](https://github.com/Quyet-ai/DTC245200263_Website-Tin-t-c-C-ng-th-ng-tin)

---

## 1. TỔNG QUAN KIẾN TRÚC HỆ THỐNG

Hệ thống Cổng thông tin tin tức được triển khai theo mô hình kiến trúc vi dịch vụ (Microservices Architecture) đóng gói hoàn toàn trong Docker Containers, phân tách ranh giới an ninh qua 3 phân vùng mạng biệt lập:

```text
                               [ Người Dùng Client ]
                                         │
                              HTTPS :8443 (HTTP :8080 301)
                                         ▼
                            ┌────────────────────────┐
                            │   Nginx Reverse Proxy  │ (frontend-net)
                            └────────────┬───────────┘
                                         │
                    ┌────────────────────┴───────────────────┐
                    │                                        │
             / (WordPress)                              /pma/ (phpMyAdmin)
                    ▼                                        ▼
          ┌───────────────────┐                    ┌───────────────────┐
          │  news_app (WP 6)  │                    │  news_phpmyadmin  │
          └─────────┬─────────┘                    └─────────┬─────────┘
                    │                                        │
                    └────────────────────┬───────────────────┘
                                         │ (backend-net: internal=true)
                                         ▼
                               ┌───────────────────┐
                               │ news_mysql (v8.0) │
                               └─────────┬─────────┘
                                         │
                    ┌────────────────────┼───────────────────┐
                    │ (monitoring-net)   │                   │
                    ▼                    ▼                   ▼
          ┌───────────────────┐┌───────────────────┐┌───────────────────┐
          │   news_cadvisor   ││news_mysql_exporter││news_nginx_exporter│
          └─────────┬─────────┘└─────────┬─────────┘└─────────┬─────────┘
                    │                    │                    │
                    └────────────────────┼────────────────────┘
                                         ▼
                               ┌───────────────────┐
                               │  news_prometheus  │ (:9090)
                               └─────────┬─────────┘
                                         │
          ┌───────────────────┐          │
          │     news_loki     │◄─────────┼───────────────────┐
          └─────────▲─────────┘          │                   │
                    │                    ▼                   ▼
          ┌─────────┴─────────┐┌───────────────────┐┌───────────────────┐
          │   news_promtail   ││   news_grafana    ││  audit_hardening  │
          └───────────────────┘└───────────────────┘└───────────────────┘
```

---

## 2. DANH SÁCH 11 DỊCH VỤ DOCKER COMPOSE

| STT | Tên Container | Image & Phiên bản | Vai trò & Chức năng trong hệ thống | Phân vùng mạng |
| :---: | :--- | :--- | :--- | :--- |
| **1** | `news_nginx` | `nginx:1.27-alpine` | Reverse Proxy tiếp nhận HTTPS :8443, áp dụng Security Headers, ẩn version | `frontend-net` |
| **2** | `news_app` | `wordpress:6-php8.2-apache` | Ứng dụng Cổng thông tin tin tức, theme tùy biến `news-portal` | `frontend-net`, `backend-net` |
| **3** | `news_mysql` | `mysql:8.0` | Hệ quản trị CSDL quan hệ lưu trữ bài viết, người dùng, metadata | `backend-net` (internal) |
| **4** | `news_phpmyadmin` | `phpmyadmin/phpmyadmin:5` | Công cụ giao diện web quản trị cơ sở dữ liệu MySQL qua `/pma/` | `frontend-net`, `backend-net` |
| **5** | `news_cadvisor` | `gcr.io/cadvisor/cadvisor:v0.49.1` | Thu thập chỉ số tài nguyên phần cứng (CPU, RAM, Network I/O) 11 container | `monitoring-net` |
| **6** | `news_nginx_exporter` | `nginx/nginx-prometheus-exporter:1.5.3` | Cào metrics Nginx qua endpoint `/nginx_status` (kết nối, tần suất request) | `frontend-net`, `monitoring-net` |
| **7** | `news_mysql_exporter` | `prom/mysqld-exporter:v0.15.0` | Cào metrics hiệu năng MySQL (QPS, Threads, Buffer Pool) qua user `metrics` | `backend-net`, `monitoring-net` |
| **8** | `news_prometheus` | `prom/prometheus:v2.53.0` | Máy chủ lưu trữ chuỗi thời gian TSDB, định kỳ 15s scrape metrics 4 jobs | `monitoring-net` |
| **9** | `news_grafana` | `grafana/grafana:11.1.0` | Trực quan hóa dữ liệu giám sát, tự động nạp dashboard và datasources | `monitoring-net` |
| **10** | `news_loki` | `grafana/loki:2.9.8` | Hệ thống lưu trữ, đánh chỉ mục nhật ký log tập trung theo thời gian | `monitoring-net` |
| **11** | `news_promtail` | `grafana/promtail:3.5.8` | Thu thập file log Nginx từ volume `nginx_logs` đẩy về Loki server | `monitoring-net` |

---

## 3. HƯỚNG DẪN CHẠY ĐÚNG THỨ TỰ

### 3.1. Yêu cầu hệ thống
- **Hệ điều hành:** Windows 10/11 (chạy Docker Desktop với WSL2) hoặc Linux Ubuntu 22.04 / 24.04 LTS.
- **Bộ nhớ RAM:** Tối thiểu 4 GB RAM (khuyến nghị 8 GB RAM).
- **Dung lượng ổ cứng:** Tối thiểu 15 GB trống.
- **Đã cài đặt:** Docker Desktop / Docker Engine v25+, Docker Compose v2, OpenSSL, cURL, Git.

### 3.2. Trình tự các bước triển khai chuẩn hóa
Triển khai hệ thống theo trình tự chuẩn hóa các bước dưới đây:

```bash
# Bước 1: Sao chép mã nguồn dự án từ GitHub
git clone https://github.com/Quyet-ai/DTC245200263_Website-Tin-t-c-C-ng-th-ng-tin.git

# Bước 2: Di chuyển vào thư mục dự án
cd DTC245200263_Website-Tin-t-c-C-ng-th-ng-tin/news-portal

# Bước 3: Tạo file cấu hình biến môi trường và điền mật khẩu
cp .env.example .env
# Chỉnh sửa cấu hình các giá trị mật khẩu bí mật an toàn

# Bước 4: Tự động khởi tạo chứng chỉ SSL tự ký cho Nginx HTTPS
docker run --rm -v "${PWD}/nginx/certs:/certs" alpine/openssl:3.3.2 req -x509 -nodes -days 365 \
  -newkey rsa:2048 -keyout /certs/selfsigned.key -out /certs/selfsigned.crt -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

# Bước 5: Khởi chạy toàn bộ 11 container dịch vụ bằng Docker Compose
docker compose up -d

# Bước 6: Kiểm tra trạng thái toàn bộ 11 container hoạt động bình thường
docker compose ps

# Bước 7: Chạy kịch bản kiểm tra thực nghiệm tự động các tiêu chí Hardening
python audit_hardening.py
```

---

## 4. BẢNG DANH MỤC CỔNG VÀ DỊCH VỤ

| Dịch vụ | Địa chỉ truy cập | Giao thức / Port | Thông tin đăng nhập mặc định |
| :--- | :--- | :--- | :--- |
| **Website Tin tức** | [https://localhost:8443](https://localhost:8443) | HTTPS (Port 443 -> 8443) | Quản trị: `admin` / `Quyet@2006` |
| **HTTP Chuyển hướng** | [http://localhost:8080](http://localhost:8080) | HTTP 301 -> HTTPS 8443 | Tự động chuyển tiếp an toàn |
| **phpMyAdmin** | [https://localhost:8443/pma/](https://localhost:8443/pma/) | HTTPS Proxy qua Nginx | Tài khoản MySQL trong file `.env` |
| **Grafana Dashboard** | [http://localhost:3000](http://localhost:3000) | HTTP (Port 3000) | `admin` / Xem mật khẩu trong `.env` |
| **Prometheus Web UI** | [http://localhost:9090](http://localhost:9090) | HTTP (Port 9090) | Môi trường kiểm thử không yêu cầu mật khẩu |
| **Loki Log API** | `http://localhost:3100` | HTTP (Port 3100 nội bộ) | Kết nối qua Grafana Explore datasource |

---

## 5. THIẾT LẬP GIÁM SÁT PROMETHEUS & GRAFANA

- **Prometheus Targets:** Truy cập `http://localhost:9090/targets` kiểm tra đủ 4/4 Job ở trạng thái **UP**:
  1. `prometheus`: Tự giám sát máy chủ Prometheus (`localhost:9090`).
  2. `cadvisor-containers`: Thu thập chỉ số tài nguyên phần cứng container (`cadvisor:8080`).
  3. `mysql-database`: Thu thập hiệu năng chuyên sâu cơ sở dữ liệu (`mysqld-exporter:9104`).
  4. `nginx-web`: Thu thập lưu lượng và kết nối máy chủ web (`nginx-exporter:9113`).
- **Grafana Dashboards:** Tự động nạp tại đường dẫn: `http://localhost:3000/d/news-portal-overview` (*News Portal - System Overview*) hiển thị biểu đồ CPU, RAM, trạng thái Exporter, số kết nối hoạt động và số luồng CSDL.

---

## 6. HỆ THỐNG LOG TẬP TRUNG LOKI & PROMTAIL

Promtail đọc trực tiếp các tệp nhật ký `access.log` và `error.log` từ Named Volume `nginx_logs` tại `/var/log/news-nginx/*.log` và đẩy về Loki server.

Quản trị viên thực hiện 3 câu truy vấn LogQL chuẩn trong Grafana Explore:
1. **Lấy toàn bộ nhật ký truy cập Nginx thời gian thực:**
   ```logql
   {job="nginx"}
   ```
2. **Lọc chính xác các bản ghi có mã lỗi 404 Not Found:**
   ```logql
   {job="nginx"} |= " 404 "
   ```
3. **Tính toán tần suất request/giây vẽ đồ thị phân bổ tải:**
   ```logql
   sum by (service) (rate({job="nginx"} [5m]))
   ```

---

## 7. CÁC BIỆN PHÁP BẢO MẬT HARDENING ĐÃ ÁP DỤNG

1. **Phân tách mạng nội bộ 3 tầng (Network Isolation):** Mạng `backend-net` được gắn cờ `internal: true`. MySQL không map cổng ra máy host, chỉ giao tiếp nội bộ với WordPress và phpMyAdmin.
2. **Giới hạn đặc quyền tiến trình (Non-root Containers & No-New-Privileges):** Prometheus chạy UID `65534`, Grafana chạy UID `472`, Promtail chạy UID `65534`, Loki chạy UID `10001`. Khai báo `security_opt: [no-new-privileges:true]` trên toàn bộ containers ngăn chặn leo thang đặc quyền.
3. **Đặc quyền tối thiểu CSDL (Least Privilege DB):** Tài khoản `news_author` chỉ có quyền thao tác trên database `news_portal`. Exporter dùng riêng tài khoản `metrics` chỉ có quyền đọc thống kê (`PROCESS, REPLICATION CLIENT, SELECT`), tuyệt đối không dùng root.
4. **Gia cố bảo mật Web Server Nginx:** Bật `server_tokens off` ẩn phiên bản Nginx; bổ sung 4 Security Headers: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`; khóa endpoint `/nginx_status` chỉ cho nội bộ.
5. **Quản lý bí mật an toàn (Secrets Management):** Tách biệt toàn bộ mật khẩu vào file `.env`; cấu hình `.gitignore` loại trừ hoàn toàn `.env` và các khóa riêng tư TLS (`*.key`) khỏi Git.

---

## 8. KIỂM THỬ VÀ ĐÁNH GIÁ THỰC NGHIỆM

Hệ thống tích hợp sẵn kịch bản kiểm thử tự động `audit_hardening.py` để quét và in ra bằng chứng an toàn thông tin:
```powershell
python audit_hardening.py
```
Kết quả kiểm tra xác nhận 100% các tiêu chí an toàn, cách ly mạng, đặc quyền non-root và bảo vệ tệp tin nhạy cảm đều đạt chuẩn xuất sắc.

---

## 9. CẤU TRÚC THƯ MỤC DỰ ÁN

```text
news-portal/
├── .env.example                   # Tệp mẫu biến môi trường (không chứa secret)
├── .gitignore                     # Danh sách tệp loại trừ khỏi Git (.env, *.key)
├── docker-compose.yml             # Tệp cấu hình Docker Compose chính (11 services, 3 networks)
├── README.md                      # Hướng dẫn chi tiết hệ thống
├── audit_hardening.py             # Script kiểm tra thực nghiệm tự động an toàn hệ thống
├── check-hardening.ps1            # Kịch bản kiểm tra an ninh PowerShell
├── nginx/
│   ├── certs/                     # Thư mục chứng chỉ SSL tự ký (selfsigned.crt, selfsigned.key)
│   └── nginx.conf                 # Cấu hình Nginx Reverse Proxy, HTTPS và Security Headers
├── mysql/init/
│   └── 01-create-metrics-user.sh  # Khởi tạo cơ sở dữ liệu và cấp quyền metrics user
├── prometheus/
│   └── prometheus.yml             # Cấu hình thu thập metrics 4 Scrape Jobs
├── grafana/
│   ├── dashboards/news-portal.json # Dashboard JSON giám sát toàn diện hệ thống
│   └── provisioning/              # Cấu hình tự động nạp Datasources và Dashboards
├── loki/loki-config.yml           # Cấu hình lưu trữ và phân vùng log Loki
├── promtail/promtail-config.yml   # Cấu hình Promtail Log Collector từ Nginx volume
├── report/                        # Thư mục chứa sơ đồ kiến trúc và các hình ảnh minh chứng
└── wordpress/themes/news-portal/  # Giao diện Website Tin tức tùy biến (Header, Single, Archive...)
```
