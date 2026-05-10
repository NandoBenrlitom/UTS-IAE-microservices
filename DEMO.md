# Skrip Demo Video - UTS IAE Microservices Asinkron

Durasi target: **5-8 menit**. Rekam dengan OBS / Zoom / screen-recorder. 
Sebelum rekam, pastikan **Docker Desktop sudah running** dan port 8001/8002/8003/3307/6379 di host kosong.

---

## 0. Setup awal (sebelum rekam)

```powershell
# Pastikan tidak ada container UTS dari sesi sebelumnya
docker compose down -v
```

Yang dibutuhkan saat rekam:

- **Postman** - untuk hit API (lebih enak dilihat di video daripada curl)
- **2 terminal/tab PowerShell** di folder `c:\Users\naufa\UTS-IAE-microservices`:
  - **Terminal A** - menjalankan `docker compose` & perintah ad-hoc
  - **Terminal B** - streaming log queue-worker (bukti async)

### Setup Postman Collection (sekali saja, sebelum rekam)

1. Buka Postman → **New** → **Collection** → namakan **"UTS IAE Microservices"**.
2. Di collection itu, klik **Variables** dan tambahkan:

   | Variable | Initial value | Current value |
   |----------|---------------|---------------|
   | `userBase` | `http://localhost:8001` | `http://localhost:8001` |
   | `productBase` | `http://localhost:8002` | `http://localhost:8002` |
   | `orderBase` | `http://localhost:8003` | `http://localhost:8003` |
   | `lastOrderId` | (kosong) | (kosong) |

3. Tambahkan **7 request** berikut (klik **Add request** di collection):

   **Request 1 - GET User**
   - Method: `GET`
   - URL: `{{userBase}}/api/users/1`

   **Request 2 - GET Product**
   - Method: `GET`
   - URL: `{{productBase}}/api/products/101`

   **Request 3 - POST Order (Valid)**
   - Method: `POST`
   - URL: `{{orderBase}}/api/orders`
   - Header: `Content-Type: application/json`
   - Body → **raw** → **JSON**:
     ```json
     {
       "user_id": 1,
       "product_id": 101
     }
     ```
   - Tab **Tests** - tempel script ini supaya `order_id` otomatis tersimpan:
     ```javascript
     const res = pm.response.json();
     pm.collectionVariables.set("lastOrderId", res.order_id);
     pm.test("Status 202 Accepted", () => pm.response.to.have.status(202));
     pm.test("Status PENDING", () => pm.expect(res.status).to.eql("PENDING"));
     ```

   **Request 4 - GET Order Status (polling)**
   - Method: `GET`
   - URL: `{{orderBase}}/api/orders/{{lastOrderId}}`

   **Request 5 - POST Order (Invalid - untuk test FAILED)**
   - Method: `POST`
   - URL: `{{orderBase}}/api/orders`
   - Header: `Content-Type: application/json`
   - Body → raw → JSON:
     ```json
     {
       "user_id": 999,
       "product_id": 999
     }
     ```
   - Tab **Tests**:
     ```javascript
     pm.collectionVariables.set("lastOrderId", pm.response.json().order_id);
     ```

   **Request 6 - GET All Orders**
   - Method: `GET`
   - URL: `{{orderBase}}/api/orders`

   **Request 7 - GET User History (consume order-service)**
   - Method: `GET`
   - URL: `{{userBase}}/api/users/1/history`

4. **Save** semua request. Sekarang siap untuk rekam.

---

## 1. Intro (~30 detik)

Yang diucapkan:
> "Halo, kami kelompok ... mata kuliah Enterprise Application Integration. 
> Tugas kami: implementasi microservices ber-Docker dengan komunikasi asinkron 
> menggunakan Laravel Queue + Redis, plus deployment Kubernetes sebagai challenge."

Tampilkan: file `note.txt` (anggota + NIM) atau slide pembuka.

---

## 2. Tour repo (~30 detik)

Buka VS Code, tunjuk:
- `docker-compose.yml` - definisi 6 service (mysql, redis, 3 service Laravel, 1 worker)
- `order-service/routes/api.php` - endpoint POST yang dispatch ke queue
- `order-service/app/Jobs/ProcessOrder.php` - job consumer
- `k8s/` - manifest Kubernetes

Yang diucapkan:
> "Ada 3 microservice Laravel: user, product, order. Order-service menerima 
> request lalu mendorong job ke Redis. Queue-worker memproses async di latar 
> belakang."

---

## 3. Build & jalankan stack Docker (~1 menit)

**Terminal A:**

```powershell
docker compose up -d --build
```

Tunggu sampai semua container `Started`. Lalu:

```powershell
docker compose ps
```

Tunjuk: 6 container running, `uts_mysql` healthy.

> "Satu perintah: `docker compose up -d --build`. Docker membangun 3 image 
> Laravel, menjalankan MySQL + Redis, menjalankan migration & seeder otomatis."

---

## 4. Smoke test sinkron - Postman (~45 detik)

Buka **Postman**, pilih collection **"UTS IAE Microservices"**.

1. Jalankan **Request 1 - GET User** (`GET {{userBase}}/api/users/1`)
   - Tunjuk response: `{"id":1,"name":"Budi Santoso","email":"budi@telkom.edu"}`
   - Tunjuk **Status: 200 OK** dan **time** (biasanya <100 ms).

2. Jalankan **Request 2 - GET Product** (`GET {{productBase}}/api/products/101`)
   - Tunjuk response: Laptop EAI Pro, price 15.000.000.

> "User dan Product service merespons sinkron. Data sudah di-seed otomatis 
> ketika container start. Kedua service punya database terpisah - 
> `uts_user` dan `uts_product`."

---

## 5. Inti tugas: komunikasi asinkron - Postman + Worker Log (~2 menit)

**Atur layar split:** Postman di kiri, Terminal B (log worker) di kanan, supaya 
penonton melihat **request masuk → worker memproses** secara real-time.

**Terminal B** - hidupkan log worker:

```powershell
docker compose logs -f queue-worker
```

### 5a. POST order valid (Postman)

Di Postman, buka **Request 3 - POST Order (Valid)**:
- Tunjukkan dulu **Body** (`{"user_id":1,"product_id":101}`).
- Klik **Send**.

Tunjuk hasilnya:
- **Status: 202 Accepted** (bukan 200/201)
- Response body:
  ```json
  {
    "message": "Order diterima, sedang diproses secara asinkron",
    "order_id": "ORD-1778...-...",
    "status": "PENDING"
  }
  ```
- Response **time < 100ms** (super cepat karena tidak menunggu validasi).
- Tab **Tests** → centang hijau ("Status 202 Accepted", "Status PENDING").

> "Perhatikan: order-service balas **202 Accepted dalam puluhan milidetik** 
> dengan status PENDING. Order-service TIDAK memanggil user-service atau 
> product-service di sini. Dia cuma simpan order dan dorong job ke Redis."

### 5b. Worker mengeksekusi job (Terminal B)

Pindah fokus ke **Terminal B**, tunjuk log worker yang baru muncul:
```
App\Jobs\ProcessOrder ............ RUNNING
[INFO] ProcessOrder: mulai memproses order ORD-...
[INFO] ProcessOrder: order ORD-... SUCCESS
App\Jobs\ProcessOrder ............ 2s DONE
```

> "Container terpisah - queue-worker - menarik job dari Redis. Dia yang 
> memvalidasi user ke port 8001 dan ambil harga product dari port 8002, 
> lalu update status order ke SUCCESS. Semua di latar belakang."

### 5c. Polling status (Postman)

Kembali ke Postman, jalankan **Request 4 - GET Order Status**:
- URL otomatis pakai `{{lastOrderId}}` yang tadi disimpan oleh Tests script.
- Klik **Send**.

Tunjuk response:
```json
{
  "order_id": "ORD-...",
  "user_id": 1,
  "product_id": 101,
  "total_price": "15000000.00",
  "status": "SUCCESS",
  "created_at": "...",
  "updated_at": "..."
}
```

> "Status sudah berubah dari PENDING jadi **SUCCESS**, dan `total_price` 
> terisi dari product-service. Inilah bukti komunikasi async benar-benar 
> berjalan: client dapat ID instant, lalu polling untuk hasil akhir."

---

## 6. Negative case - Postman (~45 detik)

Di Postman, jalankan **Request 5 - POST Order (Invalid)**:
- Body sudah berisi `{"user_id":999,"product_id":999}`.
- Klik **Send**.
- Response tetap **202 PENDING** (order-service tidak tahu data invalid - 
  itu tugas worker).

Tunggu ~3 detik, lalu jalankan **Request 4 - GET Order Status** (otomatis 
pakai order_id yang baru).

Tunjuk:
```json
{
  "status": "FAILED",
  "total_price": "0.00",
  ...
}
```

Pindah ke **Terminal B** - log worker menunjukkan:
```
[ERROR] ProcessOrder: order ORD-... FAILED - user atau product tidak valid
```

> "Worker menandai order FAILED karena user 999 dan product 999 tidak ada. 
> Client tidak perlu menebak - cukup polling endpoint status."

---

## 7. Bukti antrian benar-benar di Redis (~1 menit)

Demo paling meyakinkan: **stop worker → spam order via Postman → tunjuk antrian Redis menumpuk → start worker → antrian habis**.

### 7a. Stop worker

**Terminal A:**

```powershell
docker compose stop queue-worker
```

> "Saya matikan dulu worker-nya. Sekarang Redis akan menerima job tapi tidak 
> ada yang memproses."

### 7b. Spam 5 order dari Postman

Di Postman, **Request 3 - POST Order (Valid)** → klik **Send** sebanyak **5 kali** berturut-turut.

Setiap klik balasan tetap 202 PENDING dengan order_id berbeda - karena 
order-service tidak peduli worker hidup atau tidak, dia cuma push ke Redis.

> "Order-service tetap menerima 5 request dengan cepat. Dia tidak tahu 
> worker mati - itulah keuntungan komunikasi asinkron. Sistem tetap 
> responsif."

### 7c. Inspect Redis langsung

**Terminal A:**

```powershell
docker exec uts_redis redis-cli LLEN queues:default
```

Tunjuk hasilnya: **5** (atau lebih kalau dari demo sebelumnya).

```powershell
docker exec uts_redis redis-cli LRANGE queues:default 0 0
```

> "Lihat - 5 job antri di Redis. Ini bukti nyata Redis dipakai sebagai 
> message broker. Job-nya berisi serialized PHP class `App\\Jobs\\ProcessOrder`."

### 7d. Hidupkan worker, antrian habis

**Terminal A:**

```powershell
docker compose start queue-worker
```

Tunggu ~10 detik, lalu:

```powershell
docker compose logs --tail=20 queue-worker
docker exec uts_redis redis-cli LLEN queues:default
```

Worker log akan menunjukkan 5 job RUNNING-DONE berturutan, dan `LLEN` jadi **0**.

Kembali ke Postman, **Request 6 - GET All Orders** untuk konfirmasi 5 order baru semua sudah `SUCCESS`.

---

## 8. Challenge: Kubernetes (~1.5 menit)

> "Kami juga menyiapkan deployment Kubernetes."

Tunjuk folder `k8s/`:

```powershell
ls k8s/
```

Buka & tunjuk:
- `01-config.yaml` - ConfigMap (env) + Secret (APP_KEY)
- `02-mysql.yaml` - Deployment + PVC
- `04-user-service.yaml` - Deployment **2 replika** + Service ClusterIP

Kalau **minikube** terpasang:

```powershell
minikube start
minikube docker-env | Invoke-Expression

docker build -t uts-iae/user-service:latest    ./user-service
docker build -t uts-iae/product-service:latest ./product-service
docker build -t uts-iae/order-service:latest   ./order-service

kubectl apply -f k8s/
kubectl get pods -n uts-iae
```

Tunjuk pod-pod running. Lalu:

```powershell
kubectl port-forward -n uts-iae svc/order-service 8003:8000
```

Karena port-forward tetap pakai `localhost:8003`, **collection Postman yang 
sama langsung jalan**. Jalankan **Request 3 - POST Order** untuk 
membuktikan stack jalan di Kubernetes.

> "Setiap service di-Deploy 2 replika untuk high availability. Queue-worker 
> jadi Deployment terpisah tanpa Service - karena dia consumer, bukan 
> menerima trafik HTTP."

> *Catatan: kalau minikube belum terpasang, cukup tunjukkan manifest YAML 
> dan jelaskan strukturnya. Tidak wajib live-demo.*

---

## 9. Tutup (~30 detik)

> "Yang sudah kami lakukan:
> 1. Microservices Docker - 3 service Laravel mandiri
> 2. Komunikasi asinkron - Redis Queue + Laravel Queue Worker
> 3. Challenge Kubernetes - manifest lengkap di folder k8s
> 4. Detail bug fix dan ringkasan ada di CHANGES.md
> 
> Terima kasih."

**Terminal A** - bersihkan:

```powershell
docker compose down -v
```

---

## Checklist sebelum upload video

- [ ] Postman Collection sudah di-setup (7 request + 4 variable)
- [ ] Audio kedengar (bukan diam)
- [ ] Resolusi minimal 720p
- [ ] Semua anggota disebut + NIM
- [ ] Demo POST order async terlihat di Postman (Status 202 + PENDING)
- [ ] Worker log (Terminal B) terlihat memproses job
- [ ] Polling Postman menunjukkan status berubah PENDING -> SUCCESS
- [ ] Negative case (FAILED) ditunjukkan
- [ ] Demo Redis menumpuk saat worker mati (tahap 7) - bukti async
- [ ] K8s minimal di-walkthrough manifestnya
- [ ] Upload ke YouTube (unlisted) atau GDrive (akses pakai link)
- [ ] Update `note.txt` dengan link video
- [ ] `git add note.txt && git commit -m "add: link video demo" && git push`

## Bonus: Export Postman Collection

Setelah collection dibuat, klik **... (titik tiga) → Export** untuk save sebagai 
file `.json` di folder repo (mis. `postman/UTS-IAE.postman_collection.json`). 
Commit ke git supaya rekan/dosen bisa import dan test sendiri.

---

## Tips rekam

1. **Zoom font terminal & Postman** ke ukuran yang besar - di Postman tekan `Ctrl + +` beberapa kali. Pastikan URL dan response body kebaca jelas.
2. **Layout layar saat tahap 5 (penting):** split kiri-kanan
   - **Kiri:** Postman (request + response)
   - **Kanan:** Terminal B dengan `docker compose logs -f queue-worker`
   
   Ini layout paling meyakinkan karena penonton lihat *cause and effect*: klik Send di Postman → log worker langsung muncul.
3. Sebelum rekam, jalankan sekali `docker compose up -d --build` agar **image sudah ter-cache** - rebuild di video cuma ~10 detik.
4. **Test dulu collection Postman-nya** sekali sebelum rekam, pastikan semua 7 request hijau. Jangan rekam blind.
5. Gunakan **OBS Studio** (gratis) - rekam window Postman + Terminal sekaligus dengan scene multi-source.
6. Kalau gagal di tengah, **edit potong** lebih cepat daripada rekam ulang.
7. Setelah upload YouTube, set ke **Unlisted** (bukan Private) agar dosen bisa akses lewat link.
