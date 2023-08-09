Project: Hệ thống quản lý thi của trường Đại học Khoa học Tự nhiên - ĐHQG HN

<Ảnh Bài toán nghiệp vụ>
<Ảnh Thiết kế hệ thống>

# Triển khai

## Yêu cầu
    - Docker
## Các bước:
1. Clone project
2. Có thể bỏ comment trong file `docker-compose.yml` để tạo map volume cho các container, đổi tài khoản mặc định của minio
3. Chỉnh sửa file nginx để đổi các url, thêm ssl
4. Chạy lệnh `docker-compose up -d` để khởi tạo các container
5. Truy cập vào minio với đường dẫn ở file nginx để tạo bucket, access key
6. Config các file env trong các module
7. Chạy lại lệnh `docker-compose up -d` để các container nhận config mới