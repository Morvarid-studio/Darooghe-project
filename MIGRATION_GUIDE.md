# راهنمای Migration و Setup

## 📋 دستورات لازم

### 1️⃣ بررسی وضعیت Migration ها

```bash
# اگر از Docker استفاده می‌کنید:
docker compose exec app php artisan migrate:status

# اگر مستقیماً PHP دارید:
php artisan migrate:status
```

این دستور نشون می‌ده کدوم migration ها اجرا شدن و کدوم‌ها نه.

---

### 2️⃣ اجرای Migration های جدید

بعد از بررسی، migration های جدید رو اجرا کنید:

```bash
# اگر از Docker استفاده می‌کنید:
docker compose exec app php artisan migrate

# اگر مستقیماً PHP دارید:
php artisan migrate
```

**Migration های جدید که باید اجرا بشن:**
- `2025_12_01_000000_make_identity_document_nullable_in_information_table.php`
- `2025_12_01_000001_update_information_enum_values.php`

---

### 3️⃣ ایجاد Storage Link (برای File Upload)

برای اینکه فایل‌های آپلود شده (عکس پروفایل و رزومه) قابل دسترسی باشن:

```bash
# اگر از Docker استفاده می‌کنید:
docker compose exec app php artisan storage:link

# اگر مستقیماً PHP دارید:
php artisan storage:link
```

این دستور یک symbolic link ایجاد می‌کنه از `public/storage` به `storage/app/public`

---

### 4️⃣ بررسی Permissions (اختیاری)

مطمئن بشید پوشه storage قابل نوشتن هست:

```bash
# اگر از Docker استفاده می‌کنید:
docker compose exec app chmod -R 775 storage bootstrap/cache

# اگر مستقیماً PHP دارید (Linux/Mac):
chmod -R 775 storage bootstrap/cache
```

---

## ⚠️ نکات مهم

### اگر Migration قبلاً اجرا شده:

اگر migration `2025_11_02_110650_create_information_table` قبلاً اجرا شده، باید:

1. **Migration برای enum ها رو اجرا کنید** (برای تغییر enum ها)
2. **Migration برای identity_document رو اجرا کنید** (برای nullable کردن)

### اگر Migration اجرا نشده:

اگر migration اصلی اجرا نشده، فقط `php artisan migrate` رو بزنید و همه چیز خودکار اجرا می‌شه.

---

## 🔍 بررسی مشکلات

### اگر خطا گرفتید:

```bash
# پاک کردن cache
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear

# یا مستقیماً:
php artisan config:clear
php artisan cache:clear
```

### اگر می‌خواید migration رو rollback کنید:

```bash
# آخرین migration رو rollback کن
docker compose exec app php artisan migrate:rollback

# یا مستقیماً:
php artisan migrate:rollback
```

---

## ✅ چک لیست نهایی

- [ ] Migration ها اجرا شدن
- [ ] Storage link ایجاد شده
- [ ] Permissions درست تنظیم شده
- [ ] Cache پاک شده

---

## 🚀 بعد از Migration

بعد از اینکه همه چیز درست شد، می‌تونید:

1. تست کنید که فایل آپلود کار می‌کنه
2. بررسی کنید که enum ها درست کار می‌کنن
3. مطمئن بشید که identity_document nullable هست

