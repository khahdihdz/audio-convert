# Game Share (PHP + Bootstrap)

Trang web chia sẻ game có quản trị: bài viết/game, link download, ảnh chụp màn hình, nền tảng, nhà phát hành và năm phát hành. Lưu trữ dữ liệu bằng SQLite, giao diện Bootstrap 5.

## Yêu cầu
- PHP 8.0+ (bật `pdo_sqlite`)
- Tiện ích mở rộng: `sqlite3`, `fileinfo`
- Quyền ghi thư mục: `data/`, `uploads/`

## Cài đặt nhanh (Ubuntu/Debian)
```bash
sudo apt update && sudo apt install -y php-cli php-sqlite3 php-fileinfo
```

Arch/Manjaro:
```bash
sudo pacman -S php php-sqlite
```

Alpine:
```bash
sudo apk add php81 php81-sqlite3 php81-fileinfo
```

macOS (Homebrew):
```bash
brew install php
```

## Khởi tạo CSDL
```bash
php /workspace/init_db.php
```
- Tài khoản admin mặc định: `admin / admin123`

## Chạy máy chủ cục bộ
```bash
php -S 0.0.0.0:8000 -t /workspace
```
- Mở: `http://localhost:8000`
- Admin: `http://localhost:8000/admin/`

## Cấu trúc
- `config.php`: cấu hình chung, kết nối DB, helper auth & upload
- `init_db.php`: khởi tạo SQLite
- `partials/header.php`, `partials/footer.php`: giao diện chung
- `index.php`, `view.php`: trang công khai
- `login.php`, `logout.php`: xác thực
- `admin/`: quản trị
  - `index.php`: danh sách game, link thao tác
  - `new_game.php`: thêm game (cover, screenshots, link)
  - `edit_game.php`: sửa game, quản lý ảnh/link
  - `delete_game.php`: xác nhận và xóa game
  - `remove_screenshot.php`, `remove_download.php`: xóa từng mục
- `uploads/covers`, `uploads/screenshots`: chứa ảnh tải lên
- `data/app.db`: file CSDL

## Tùy chỉnh
- Nếu website chạy dưới sub-path (vd: `/myapp/`), chỉnh `BASE_URL` trong `config.php` cho đúng.
- Có thể đổi mật khẩu admin bằng cách cập nhật bảng `users` hoặc thêm chức năng trong admin.

## Bảo mật
- Đã dùng CSRF token cho form POST
- Escape đầu ra với `esc()`
- Xóa file trên đĩa khi xóa game/ảnh

## Ghi chú
- Đây là bản tối giản, có thể mở rộng: phân trang, phân loại, nhiều người dùng, tag, v.v.
