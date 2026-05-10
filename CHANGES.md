# Ringkasan Perubahan - UTS IAE Microservices Asinkron

Dokumen ini merangkum apa saja yang diubah/diperbaiki dari kondisi awal repo
agar memenuhi tugas: **microservices Docker + komunikasi asinkron + Kubernetes**.

---

## A. Bug yang ditemukan & diperbaiki

### 1. Order-service tidak benar-benar asinkron
**Sebelum:** `POST /api/orders` melakukan `Http::get()` ke user-service & product-service
secara **sinkron** lalu langsung membuat record order. Job `ProcessOrder` ada
tapi tidak pernah di-`dispatch`.
**Sesudah:** route mendispatch job ke Redis Queue, balas `202 Accepted` dengan
`order_id` & `status=PENDING`. Worker yang memproses validasi async.
**File:** `order-service/routes/api.php`, `order-service/app/Jobs/ProcessOrder.php`

### 2. ProcessOrder.php memakai hostname container yang salah
**Sebelum:** `http://user_service:8000` (memakai underscore = nama container).
Service name docker-compose adalah `user-service` (dengan tanda hubung).
**Sesudah:** dipakai service name yang benar `http://user-service:8000`.

### 3. Migration `orders` tidak punya kolom `total_price`
**Sebelum:** Model `Order` punya `total_price` di `$fillable` & job menulis
`total_price` -> tapi kolomnya tidak ada di migration -> error SQL.
**Sesudah:** ditambah kolom `total_price decimal(15,2) default 0` ke migration.
**File:** `order-service/database/migrations/2026_04_29_151011_create_orders_table.php`

### 4. ProcessOrder.php membuat order tanpa `order_id` (kolom unique)
**Sebelum:** `Order::create([...])` di job tidak menyetel `order_id` yang
unique-required -> migration error.
**Sesudah:** order_id dibuat di route POST, job hanya mengupdate order yang
sudah ada (PENDING -> SUCCESS/FAILED).

### 5. `DB_CONNECTION` tidak diset -> Laravel jatuh ke SQLite
**Sebelum:** `config/database.php` default `sqlite`. docker-compose hanya
menyetel `DB_HOST`, `DB_DATABASE`, dst, **tanpa** `DB_CONNECTION=mysql`.
Hasilnya semua service memakai sqlite (tidak terlihat ke MySQL).
**Sesudah:** ditambah `DB_CONNECTION: mysql` lewat YAML anchor `&laravel-env`
yang dipakai semua service.
**File:** `docker-compose.yml`

### 6. `APP_KEY` tidak ada
**Sebelum:** Laravel butuh `APP_KEY`. Tidak ada `.env` (dijaga oleh
`.gitignore`) dan tidak ada env var di compose -> exception saat boot.
**Sesudah:** `APP_KEY` diset lewat env var di compose & K8s Secret.

### 7. `mysqladmin ping` gagal karena TLS
**Sebelum:** entrypoint di Dockerfile melakukan `until mysqladmin ping ...`,
tapi `default-mysql-client` di Debian = MariaDB client yang menolak self-signed
cert MySQL 8 -> tunggu loop tak pernah keluar.
**Sesudah:** ditambah flag `--skip-ssl`.

### 8. `pecl install redis` tidak reliable di build
**Sebelum:** `RUN pecl install redis` kadang gagal (`No releases available`).
**Sesudah:** diganti `mlocati/docker-php-extension-installer` yang stabil.

### 9. Storage/cache directories tidak dibuat di image
**Sebelum:** `chmod -R 775 storage bootstrap/cache` jalan, tapi sub-folder
`storage/framework/{cache,sessions,views}` & `storage/logs` belum tentu ada.
**Sesudah:** `mkdir -p` ditambahkan sebelum chmod.

### 10. Seeder tidak idempoten (gagal saat re-run)
**Sebelum:** `User::insert([id=>1])` & `User::create(['email'=>...])` -> error
duplicate key saat container restart.
**Sesudah:** semua seeder pakai `updateOrCreate` -> aman dijalankan berkali-kali.
**File:** `*/database/seeders/DatabaseSeeder.php`

### 11. `version: '3.8'` deprecated warning
Dihapus dari `docker-compose.yml`.

---

## B. Fitur yang ditambahkan

### 1. Komunikasi asinkron Producer-Consumer (inti tugas)
- `order-service` jadi **producer** Redis Queue.
- `queue-worker` (kontainer terpisah) jadi **consumer**.
- Job `ProcessOrder` melakukan validasi user/product async + update status order.
- Endpoint baru `GET /api/orders/{order_id}` untuk polling status.

### 2. Validasi input
`POST /api/orders` mem-validate `user_id` & `product_id` integer required.

### 3. Job retries & failure handler
`ProcessOrder` punya `$tries=3`, `$backoff=5`, dan method `failed()` yang
menandai order sebagai FAILED kalau worker tetap gagal.

### 4. Healthcheck Redis & retry MySQL
Healthcheck Redis ditambahkan di compose. Order-service & queue-worker
men-tunggu MySQL sebelum boot.

### 5. Kubernetes manifests (challenge)
Folder baru `k8s/`:
- `00-namespace.yaml` - namespace `uts-iae`
- `01-config.yaml` - ConfigMap (env non-rahasia) + Secret (`APP_KEY`, password) + ConfigMap `mysql-init`
- `02-mysql.yaml` - Deployment + PVC (1Gi) + Service
- `03-redis.yaml` - Deployment + Service
- `04-user-service.yaml`, `05-product-service.yaml`, `06-order-service.yaml` -
  Deployment 2 replika + Service + (di order) `queue-worker` Deployment terpisah
- `07-ingress.yaml` - Ingress path-based (`/user/*`, `/product/*`, `/order/*`)
- `k8s/README.md` - cara apply di minikube

### 6. Dokumentasi
- `README.md` ditulis ulang (UTF-8, ringkas, ada diagram, daftar endpoint, alur async).
- `CHANGES.md` (file ini) - audit trail perubahan.
- `note.txt` - identitas kelompok + placeholder link video.

---

## C. File yang ditambah / dimodifikasi

```
docker-compose.yml                                              [MODIFIED]
README.md                                                       [REWRITTEN]
note.txt                                                        [NEW]
CHANGES.md                                                      [NEW]

user-service/Dockerfile                                         [MODIFIED]
user-service/database/seeders/DatabaseSeeder.php                [MODIFIED]

product-service/Dockerfile                                      [MODIFIED]
product-service/database/seeders/DatabaseSeeder.php             [MODIFIED]

order-service/Dockerfile                                        [MODIFIED]
order-service/routes/api.php                                    [MODIFIED]
order-service/app/Jobs/ProcessOrder.php                         [MODIFIED]
order-service/database/migrations/2026_04_29_151011_create_orders_table.php  [MODIFIED]
order-service/database/seeders/DatabaseSeeder.php               [MODIFIED]

k8s/00-namespace.yaml                                           [NEW]
k8s/01-config.yaml                                              [NEW]
k8s/02-mysql.yaml                                               [NEW]
k8s/03-redis.yaml                                               [NEW]
k8s/04-user-service.yaml                                        [NEW]
k8s/05-product-service.yaml                                     [NEW]
k8s/06-order-service.yaml                                       [NEW]
k8s/07-ingress.yaml                                             [NEW]
k8s/README.md                                                   [NEW]
```

---

## D. Hasil verifikasi end-to-end

Diuji dengan `docker compose up -d --build`:

| Tes | Hasil |
|-----|-------|
| `GET /api/users/1` | OK -> `{"id":1,"name":"Budi Santoso",...}` |
| `GET /api/products/101` | OK -> `{"id":101,"price":"15000000.00",...}` |
| `POST /api/orders` user/product valid | 202 -> `status=PENDING` -> ~2s kemudian SUCCESS dengan `total_price=15000000` |
| `POST /api/orders` user/product tidak valid | 202 -> `status=PENDING` -> ~2s kemudian FAILED |
| `docker compose logs queue-worker` | terlihat job `App\Jobs\ProcessOrder` RUNNING -> DONE 2s |
| `docker compose config --quiet` | exit 0 (compose syntax valid) |
