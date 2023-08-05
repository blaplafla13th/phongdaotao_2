Project: Hệ thống quản lý thi của trường Đại học Khoa học Tự nhiên - ĐHQG HN

<Ảnh Bài toán nghiệp vụ>
<Ảnh Thiết kế hệ thống>

# Triển khai

## Yêu cầu
    - Docker
## Các bước:
1. Clone project
2. Có thể bỏ comment trong file `docker-compose.yml` để tạo map volume cho các container, đổi tài khoản mặc định của minio
3. Chạy lệnh `docker-compose up -d` với các container: `minio` và `redis`
4. Chạy lệnh `docker-compose up -d` với các container: `userdb`. Chờ cho đến khi các container chạy xong.
5. Config mail server cho các container nhóm api: `user-api` ở file .env.example
    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=smtp.office365.com
    MAIL_PORT=587
    MAIL_USERNAME=test@example.com
    MAIL_PASSWORD=test@example
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS=test@example.com
    MAIL_FROM_NAME="${APP_NAME}"
    ```
6. Chạy lệnh `docker-compose up -d` với container: `user-api`. Chờ cho đến khi các container chạy xong.
7. Mở file `nginx.conf` sửa server name tương ứng cùng ssl đã có. Đặt các file ssl vào folder `cert`
8. Chạy lệnh `docker-compose up -d` với các container: `server`. Chờ cho đến khi các container chạy xong.
9. Mở Minio thiết lập bucket và sửa file `.env` trong user-module