# PANDUAN DEPLOYMENT & OPERASIONAL PRODUKSI
# AI CHATBOT VETERINER & GEOSPASIAL TERNAKVERSE
**Kabupaten Banyumas — Dinas Perikanan dan Peternakan**
*Manual Operasional, Konfigurasi Server, Migrasi Basis Data Vektor & Manajemen Kegagalan*

---

| Parameter Dokumen | Rincian Teknis |
| :--- | :--- |
| **Aplikasi / Modul** | Modul AI Chatbot TernakVerse (TernakVerse AI Geospatial Assistant) |
| **Target Lingkungan** | Server Produksi Linux Ubuntu 22.04 / 24.04 LTS, Docker Container, & Windows Server |
| **Basis Runtime** | PHP 8.2+ (CLI & FPM), Composer 2.7+, Node.js 20+ LTS, npm 10+ |
| **Engine Basis Data** | PostgreSQL 16+ dengan Ekstensi PostGIS 3.4+ dan pgvector 0.7+ |
| **Penyimpanan Cache & Antrean** | Redis 7+ Server (phpredis extension) |
| **Web Server / Reverse Proxy** | Nginx 1.24+ (Konfigurasi Khusus Server-Sent Events / SSE Buffering) |
| **Status Dokumen** | **PRODUCTION DEPLOYMENT RUNBOOK** |
| **Versi Panduan** | 1.0.0-PROD |
| **Tanggal Terbit** | November 2026 |

---

## DAFTAR ISI

1. [Prasyarat Server & Kebutuhan Minimum](#1-prasyarat-server--kebutuhan-minimum)
   - 1.1 [Spesifikasi Perangkat Keras (Hardware Sizing)](#11-spesifikasi-perangkat-keras-hardware-sizing)
   - 1.2 [Spesifikasi Perangkat Lunak & Ekstensi PHP](#12-spesifikasi-perangkat-lunak--ekstensi-php)
2. [Instalasi & Konfigurasi Basis Data PostgreSQL + pgvector + PostGIS](#2-instalasi--konfigurasi-basis-data-postgresql--pgvector--postgis)
   - 2.1 [Instalasi pada Ubuntu / Debian (Native APT Repository)](#21-instalasi-pada-ubuntu--debian-native-apt-repository)
   - 2.2 [Instalasi Menggunakan Docker & Docker Compose](#22-instalasi-menggunakan-docker--docker-compose)
   - 2.3 [Instalasi pada Lingkungan Windows Server / Development](#23-instalasi-pada-lingkungan-windows-server--development)
   - 2.4 [Verifikasi SQL Ekstensi & Validasi Tipe Data Vektor](#24-verifikasi-sql-ekstensi--validasi-tipe-data-vektor)
3. [Referensi Konfigurasi Environment (`.env`)](#3-referensi-konfigurasi-environment-env)
   - 3.1 [Tabel Referensi Lengkap Parameter Chatbot](#31-tabel-referensi-lengkap-parameter-chatbot)
   - 3.2 [Konfigurasi Kunci API Provider AI (Gemini, OpenAI, Ollama)](#32-konfigurasi-kunci-api-provider-ai-gemini-openai-ollama)
   - 3.3 [Konfigurasi Basis Data & Ekstensi Vektor](#33-konfigurasi-basis-data--ekstensi-vektor)
4. [Migrasi Skema & Seeding Pengetahuan Vektor](#4-migrasi-skema--seeding-pengetahuan-vektor)
   - 4.1 [Menjalankan Migrasi Laravel Database](#41-menjalankan-migrasi-laravel-database)
   - 4.2 [Menjalankan CLI Ingesti Dokumen Otoritatif Banyumas](#42-menjalankan-cli-ingesti-dokumen-otoritatif-banyumas)
   - 4.3 [Verifikasi Integritas Chunk & Indeks HNSW](#43-verifikasi-integritas-chunk--indeks-hnsw)
5. [Konfigurasi Redis, Antrean (Queue) & Rate Limiting](#5-konfigurasi-redis-antrean-queue--rate-limiting)
   - 5.1 [Penyetelan Driver Redis](#51-penyetelan-driver-redis)
   - 5.2 [Konfigurasi Supervisor untuk Worker Antrean Ingesti](#52-konfigurasi-supervisor-untuk-worker-antrean-ingesti)
   - 5.3 [Penyetelan Batas Laju Permintaan (Rate Limiter)](#53-penyetelan-batas-laju-permintaan-rate-limiter)
6. [Konfigurasi Web Server Nginx untuk Streaming SSE](#6-konfigurasi-web-server-nginx-untuk-streaming-sse)
   - 6.1 [Snippet Nginx Server Block Wajib](#61-snippet-nginx-server-block-wajib)
   - 6.2 [Pencegahan Masalah Buffering Respons Streaming](#62-pencegahan-masalah-buffering-respons-streaming)
7. [Daftar Periksa Deployment Produksi (Go-Live Checklist)](#7-daftar-periksa-deployment-produksi-go-live-checklist)
   - 7.1 [Langkah-Langkah Build & Optimalisasi](#71-langkah-langkah-build--optimalisasi)
   - 7.2 [Hak Akses Direktori Storage & Log](#72-hak-akses-direktori-storage--log)
8. [Pemantauan, Troubleshooting & Graceful Degradation](#8-pemantauan-troubleshooting--graceful-degradation)
   - 8.1 [Pemantauan Log Aplikasi (`storage/logs/laravel.log`)](#81-pemantauan-log-aplikasi-storagelogslaravellog)
   - 8.2 [Diagnosis Masalah Umum & Solusi Pemecahan](#82-diagnosis-masalah-umum--solusi-pemecahan)
   - 8.3 [Mekanisme Graceful Degradation (Failover Otomatis)](#83-mekanisme-graceful-degradation-failover-otomatis)

---

## 1. PRASYARAT SERVER & KEBUTUHAN MINIMUM

### 1.1 Spesifikasi Perangkat Keras (Hardware Sizing)

Kebutuhan perangkat keras server bervariasi bergantung pada apakah model bahasa besar (LLM) di-host secara lokal via Ollama atau memanfaatkan gateway API eksternal (Google Gemini / OpenAI).

| Komponen Hardware | Opsi A: Cloud API Mode (Gemini / OpenAI) | Opsi B: Self-Hosted Local Ollama (7B/8B Q4) |
| :--- | :--- | :--- |
| **Processor (CPU)** | 4 vCPU (x86_64 / ARM64) | 8 vCPU (x86_64 dengan instruksi AVX2) |
| **Memori (RAM)** | 8 GB RAM DDR4/DDR5 | 16 GB s/d 32 GB RAM DDR4/DDR5 |
| **Penyimpanan (Disk)**| 40 GB NVMe SSD (PostGIS + Vector Chunks) | 100 GB NVMe SSD (Model Weights + Basis Data) |
| **GPU Acceleration** | Tidak Diperlukan (Zero GPU Cost) | NVIDIA GPU 8GB+ VRAM (e.g. RTX 3060/4060) disarankan |
| **Bandwidth Jaringan**| Minimal 50 Mbps Uplink / Downlink | Minimal 20 Mbps Uplink / Downlink |

### 1.2 Spesifikasi Perangkat Lunak & Ekstensi PHP

Sebelum memulai deployment, pastikan seluruh dependensi perangkat lunak berikut terpasang pada server:
- **Operating System:** Ubuntu 22.04 LTS atau Ubuntu 24.04 LTS (disarankan)
- **PHP Version:** PHP 8.2 atau PHP 8.3
- **Ekstensi PHP Wajib:**
  - `php-fpm` & `php-cli`
  - `php-pgsql` / `php-pdo-pgsql` (Koneksi ke PostgreSQL 16)
  - `php-redis` (Komunikasi performa tinggi ke Redis cache & queues)
  - `php-mbstring`, `php-xml`, `php-curl`, `php-bcmath`
  - `php-zip`, `php-intl`, `php-gd`
- **Node.js:** Versi 20.x LTS atau 22.x LTS dengan `npm` versi 10+
- **Composer:** Versi 2.7+

Perintah verifikasi versi pada CLI:
```bash
php -v
composer -V
node -v
npm -v
```

---

## 2. INSTALASI & KONFIGURASI BASIS DATA POSTGRESQL + PGVECTOR + POSTGIS

Basis data TernakVerse mengombinasikan kemampuan geospasial relasional (PostGIS) dan pencarian semantik vektor densitas tinggi (pgvector).

### 2.1 Instalasi pada Ubuntu / Debian (Native APT Repository)

Jalankan perintah berikut pada terminal Ubuntu:

```bash
# 1. Tambahkan repository resmi PostgreSQL
sudo apt install -y curl ca-certificates gnupg lsb-release
sudo install -d /etc/apt/keyrings
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /etc/apt/keyrings/postgresql.gpg
echo "deb [signed-by=/etc/apt/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list

# 2. Update repositori dan pasang PostgreSQL 16 beserta PostGIS 3 & pgvector
sudo apt update
sudo apt install -y postgresql-16 postgresql-contrib-16 postgresql-16-postgis-3 postgresql-16-pgvector

# 3. Pastikan service PostgreSQL berjalan
sudo systemctl enable postgresql
sudo systemctl start postgresql
sudo systemctl status postgresql
```

### 2.2 Instalasi Menggunakan Docker & Docker Compose

Bagi lingkungan berbasis kontainer, gunakan `docker-compose.yml` yang telah memadukan PostgreSQL 16, PostGIS 3.4, dan pgvector 0.7:

```yaml
version: '3.8'

services:
  ternakverse-db:
    image: pgvector/pgvector:pg16
    container_name: ternakverse_postgres
    restart: always
    environment:
      POSTGRES_DB: ternakverse
      POSTGRES_USER: ternakverse_admin
      POSTGRES_PASSWORD: SecretDatabasePassword2026!
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/init-db:/docker-entrypoint-initdb.d

  redis:
    image: redis:7-alpine
    container_name: ternakverse_redis
    restart: always
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
  redis_data:
```

### 2.3 Instalasi pada Lingkungan Windows Server / Development

1. Unduh dan pasang installer resmi **PostgreSQL 16** dari [EnterpriseDB](https://www.enterprisedb.com/downloads/postgres-postgresql-downloads).
2. Pasang ekstensi **PostGIS 3.4** melalui *Application Stack Builder* bawaan PostgreSQL.
3. Untuk memasang **pgvector**:
   - Unduh rilis biner pgvector untuk Windows dari [pgvector releases](https://github.com/pgvector/pgvector/releases).
   - Salin berkas `vector.dll` ke direktori `C:\Program Files\PostgreSQL\16\lib\`.
   - Salin berkas `vector.control` dan `vector--*.sql` ke `C:\Program Files\PostgreSQL\16\share\extension\`.
4. Mulai ulang (*restart*) service `postgresql-x64-16` melalui Services Manager Windows.

### 2.4 Verifikasi SQL Ekstensi & Validasi Tipe Data Vektor

Masuk ke terminal PostgreSQL menggunakan `psql` dan jalankan verifikasi:

```bash
sudo -u postgres psql
```

Eksekusi perintah SQL berikut di dalam basis data `ternakverse`:

```sql
-- Buat database jika belum ada
CREATE DATABASE ternakverse;
\c ternakverse;

-- Aktifkan ekstensi PostGIS & pgvector
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS vector;

-- Verifikasi ekstensi aktif
SELECT name, default_version, installed_version 
FROM pg_available_extensions 
WHERE name IN ('postgis', 'vector');

-- Uji coba operasi tipe data vektor (1536 dimensi)
SELECT '[0.1, 0.2, 0.3]'::vector;

-- Uji coba fungsi jarak kemiripan kosinus (<=>)
SELECT '[1, 0, 0]'::vector <=> '[0, 1, 0]'::vector AS cosine_distance;
```

*Keluaran yang diharapkan:*
`cosine_distance` bernilai `1` (tegak lurus / tidak mirip) dan `0` untuk vektor yang identik.

---

## 3. REFERENSI KONFIGURASI ENVIRONMENT (`.env`)

### 3.1 Tabel Referensi Lengkap Parameter Chatbot

Konfigurasi chatbot diatur pada file `config/chatbot.php` yang membaca variabel lingkungan dari `.env`:

| Variabel Lingkungan | Nilai Default | Opsi Nilai | Penjelasan Teknis |
| :--- | :--- | :--- | :--- |
| `CHATBOT_DEFAULT_PROVIDER` | `gemini` | `gemini`, `openai`, `ollama`, `simulation` | Provider AI utama untuk generasi percakapan |
| `CHATBOT_DEFAULT_MODEL` | `gemini-1.5-flash` | `gemini-1.5-flash`, `gpt-4o`, `llama3:8b` | Model LLM yang dituju pada provider |
| `CHATBOT_EMBEDDING_PROVIDER`| `gemini` | `gemini`, `openai`, `local_hash` | Provider penghasil embedding vektor |
| `CHATBOT_EMBEDDING_DIMENSION`| `1536` | `1536`, `768` | Dimensi kolom vektor (standar: 1536) |
| `GEMINI_API_KEY` | *(null)* | Kunci API Google AI Studio | Wajib jika provider bernilai `gemini` |
| `OPENAI_API_KEY` | *(null)* | Kunci API OpenAI Platform | Wajib jika provider bernilai `openai` |
| `OLLAMA_BASE_URL` | `http://127.0.0.1:11434`| URL Gateway HTTP Ollama | Alamat daemon Ollama lokal/intranet |
| `CHATBOT_MAX_TOOL_ITERATIONS`| `4` | Bilangan bulat 1 - 8 | Batas loop ReAct pemanggilan function tools |
| `CHATBOT_RATE_LIMIT_PUBLIC` | `20` | Bilangan bulat | Kuota request maksimal pengguna anonim |
| `CHATBOT_RATE_LIMIT_AUTH` | `60` | Bilangan bulat | Kuota request maksimal pengguna login |
| `CHATBOT_DECAY_MINUTES` | `10` | Menit | Durasi jendela reset kuota rate limiter |

### 3.2 Konfigurasi Kunci API Provider AI (Gemini, OpenAI, Ollama)

Salin baris konfigurasi berikut ke berkas `.env` server produksi:

```dotenv
# =========================================================================
# TERNAKVERSE AI CHATBOT VETERINER & GEOSPASIAL
# =========================================================================
CHATBOT_DEFAULT_PROVIDER=gemini
CHATBOT_DEFAULT_MODEL=gemini-1.5-flash
CHATBOT_EMBEDDING_PROVIDER=gemini
CHATBOT_EMBEDDING_DIMENSION=1536

# Google Gemini API Gateway (Dapatkan dari Google AI Studio)
GEMINI_API_KEY=AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# OpenAI Platform API Gateway (Opsional untuk failover)
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Self-Hosted Ollama API Endpoint (Opsional untuk kedaulatan data lokal)
OLLAMA_BASE_URL=http://127.0.0.1:11434

# Pembatasan Laju Permintaan (Anti DoS / Quota Guard)
CHATBOT_RATE_LIMIT_PUBLIC=20
CHATBOT_RATE_LIMIT_AUTH=60
CHATBOT_DECAY_MINUTES=10
```

### 3.3 Konfigurasi Basis Data & Ekstensi Vektor

Pastikan konfigurasi koneksi basis data mengarah ke driver PostgreSQL dengan kredensial yang valid:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ternakverse
DB_USERNAME=ternakverse_admin
DB_PASSWORD=SecretDatabasePassword2026!
```

---

## 4. MIGRASI SKEMA & SEEDING PENGETAHUAN VEKTOR

### 4.1 Menjalankan Migrasi Laravel Database

Jalankan perintah migrasi Laravel untuk membentuk tabel dokumen, potongan vektor, indeks HNSW, percakapan, dan riwayat pesan:

```bash
# Jalankan migrasi seluruh tabel (termasuk modul chatbot)
php artisan migrate --force
```

Tabel yang akan dibuat secara otomatis:
- `knowledge_documents`
- `knowledge_chunks` (dilengkapi kolom `embedding vector(1536)` dan indeks HNSW `idx_knowledge_chunks_embedding_hnsw`)
- `chatbot_conversations` (UUID primary key)
- `chatbot_messages`

### 4.2 Menjalankan CLI Ingesti Dokumen Otoritatif Banyumas

Setelah tabel terbentuk, jalankan perintah ingesti untuk mengisi basis pengetahuan vektor dengan 5 pedoman standar resmi Kabupaten Banyumas:

```bash
# Ingesti 5 pedoman resmi (SOP PMK, LSD, RPH NKV, Anthrax, dan Pemuliaan Ternak)
php artisan ternakverse:ingest-knowledge --seed-defaults
```

Keluaran konsol yang berhasil:
```text
========================================================================
  TERNAKVERSE: Ingestion Engine & RAG Knowledge Base Chunker
========================================================================

[PROSES] Menjalankan seeding 5 Dokumen Inti Pedoman Veteriner & Regulasi Banyumas...

[SELESAI] Berhasil memproses 5 dokumen pedoman resmi Banyumas:
+----+-----------------------------------------------+-----------+--------------+------------------+
| ID | Judul Dokumen                                 | Kategori  | Jumlah Chunk | Total Token Est. |
+----+-----------------------------------------------+-----------+--------------+------------------+
| 1  | SOP Pengendalian PMK Banyumas 2024            | veteriner | 5            | 1650             |
| 2  | Pedoman Teknis Vaksinasi & Penanganan LSD     | veteriner | 5            | 1520             |
| 3  | Standar Higiene Sanitasi RPH Banyumas         | regulasi  | 4            | 1340             |
| 4  | Protokol Mitigasi Kesiapsiagaan Anthrax Akut  | veteriner | 4            | 1280             |
| 5  | Pedoman Manajemen Populasi Ternak Ruminansia  | kebijakan | 5            | 1410             |
+----+-----------------------------------------------+-----------+--------------+------------------+

Total potongan vektor (chunks) terindeks: 23 chunks.
```

### 4.3 Verifikasi Integritas Chunk & Indeks HNSW

Verifikasi bahwa data vektor telah tersimpan dan terindeks dengan benar pada PostgreSQL:

```bash
sudo -u postgres psql -d ternakverse -c "SELECT count(*) FROM knowledge_chunks WHERE embedding IS NOT NULL;"
```

Pastikan jumlah chunk yang memiliki embedding sesuai dengan total chunk yang di-ingest.

---

## 5. KONFIGURASI REDIS, ANTREAN (QUEUE) & RATE LIMITING

### 5.1 Penyetelan Driver Redis

Untuk mendukung pemrosesan asinkron dan pembatasan laju kueri secara terpusat (*distributed rate limiting*), gunakan Redis sebagai backend cache dan antrean:

```dotenv
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5.2 Konfigurasi Supervisor untuk Worker Antrean Ingesti

Jika Anda mengunggah dokumen pedoman berukuran besar melalui portal admin di masa mendatang, proses chunking dan kalkulasi embedding dapat didelegasikan ke *background job*.

Buat berkas konfigurasi supervisor `/etc/supervisor/conf.d/ternakverse-worker.conf`:

```ini
[program:ternakverse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ternakverse/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ternakverse/storage/logs/worker.log
stopwaitsecs=3600
```

Aktifkan konfigurasi supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ternakverse-worker:*
```

### 5.3 Penyetelan Batas Laju Permintaan (Rate Limiter)

Rate limiter diimplementasikan pada file `app/Http/Requests/ChatbotChatRequest.php` atau routing middleware untuk mencegah penyalahgunaan kuota token API:
- Permintaan publik anonim dibatasi hingga 20 request per 10 menit.
- Permintaan terotentikasi dibatasi hingga 60 request per 10 menit.

---

## 6. KONFIGURASI WEB SERVER NGINX UNTUK STREAMING SSE

Server-Sent Events (SSE) memerlukan transmisi token berkelanjutan tanpa jeda (*unbuffered streaming*). Konfigurasi standar Nginx akan menahan (*buffer*) paket data respons sampai mencapai ukuran tertentu sebelum dikirimkan ke peramban, yang mengakibatkan efek "macet" pada animasi ketikan teks AI.

### 6.1 Snippet Nginx Server Block Wajib

Tambahkan konfigurasi khusus berikut pada berkas konfigurasi Nginx situs Anda (misal: `/etc/nginx/sites-available/ternakverse`):

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name ternakverse.banyumaskab.go.id;
    root /var/www/ternakverse/public;

    index index.php index.html;
    charset utf-8;

    # Penanganan endpoint streaming AI Chatbot (SSE)
    location /api/v1/chatbot/chat {
        try_files $uri $uri/ /index.php?$query_string;

        # MATIKAN BUFFERING SECARA MUTLAK UNTUK STREAMING SSE
        proxy_buffering off;
        proxy_cache off;
        proxy_read_timeout 600s;
        proxy_connect_timeout 60s;
        proxy_send_timeout 600s;
        
        # FastCGI Buffering Disabled
        fastcgi_buffering off;
        fastcgi_read_timeout 600s;
        fastcgi_keep_conn on;

        # Header Wajib
        chunked_transfer_encoding on;
        add_header X-Accel-Buffering "no";
    }

    # Blok PHP-FPM standar untuk route lainnya
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6.2 Pencegahan Masalah Buffering Respons Streaming

Pastikan direktif berikut aktif:
1. `fastcgi_buffering off;` mematikan penahanan respons antara PHP-FPM dan Nginx.
2. `proxy_buffering off;` mematikan penahanan paket jika menggunakan reverse proxy upstream.
3. Controller `ChatbotApiController.php` secara native mengirimkan header `X-Accel-Buffering: no` dan menjalankan `ob_flush()` serta `flush()` pada setiap pengiriman event.

Uji dan muat ulang konfigurasi Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## 7. DAFTAR PERIKSA DEPLOYMENT PRODUKSI (GO-LIVE CHECKLIST)

### 7.1 Langkah-Langkah Build & Optimalisasi

Jalankan urutan perintah berikut pada direktori akar aplikasi sebelum meluncurkan versi produksi:

```bash
# 1. Update kode repositori terbaru
git pull origin main

# 2. Pasang dependensi PHP produksi tanpa dev tools
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Pasang dependensi frontend dan kompilasi aset Vite
npm ci
npm run build

# 4. Buat symbolic link direktori storage publik
php artisan storage:link

# 5. Jalankan migrasi basis data
php artisan migrate --force

# 6. Pastikan dokumen vektor ter-seeding
php artisan ternakverse:ingest-knowledge --seed-defaults

# 7. Aktifkan Caching Konfigurasi, Route, dan View
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Restart queue workers dan reload PHP-FPM
sudo supervisorctl restart ternakverse-worker:*
sudo systemctl restart php8.2-fpm
```

### 7.2 Hak Akses Direktori Storage & Log

Pastikan pengguna web server (`www-data`) memiliki izin penuh pada direktori penyimpanan dan log:

```bash
sudo chown -R www-data:www-data /var/www/ternakverse/storage /var/www/ternakverse/bootstrap/cache
sudo chmod -R 775 /var/www/ternakverse/storage /var/www/ternakverse/bootstrap/cache
```

---

## 8. PEMANTAUAN, TROUBLESHOOTING & GRACEFUL DEGRADATION

### 8.1 Pemantauan Log Aplikasi (`storage/logs/laravel.log`)

Untuk memantau aktivitas percakapan, kesalahan eksekusi tools, atau pergantian provider AI secara real-time, gunakan perintah:

```bash
tail -f /var/www/ternakverse/storage/logs/laravel.log | grep -E "MultiEngineLlmDriver|AgentOrchestrator|Chatbot"
```

### 8.2 Diagnosis Masalah Umum & Solusi Pemecahan

#### Masalah 1: `type "vector" does not exist` saat migrasi database
- **Penyebab:** Ekstensi `pgvector` belum terpasang atau belum diaktifkan pada basis data PostgreSQL.
- **Solusi:** Jalankan query berikut sebagai superuser PostgreSQL:
  ```bash
  sudo -u postgres psql -d ternakverse -c "CREATE EXTENSION IF NOT EXISTS vector;"
  ```

#### Masalah 2: Teks streaming AI muncul sekaligus di akhir (tidak mengalir kata per kata)
- **Penyebab:** Nginx atau Cloudflare melakukan *buffering* pada respon HTTP chunked.
- **Solusi:**
  - Pastikan Nginx memiliki baris `fastcgi_buffering off;` dan `proxy_buffering off;`.
  - Jika server berada di balik Cloudflare CDN, buat **Page Rule** atau **Cache Rule** untuk URL `*ternakverse.banyumaskab.go.id/api/v1/chatbot/chat*` dengan opsi **Disable Cache** dan **Bypass**.

#### Masalah 3: Kuota API Gemini Habis (Error 429 Too Many Requests)
- **Penyebab:** Melebihi kuota free tier Google Gemini.
- **Solusi:**
  - Ganti nilai `CHATBOT_DEFAULT_PROVIDER=openai` pada `.env` dan jalankan `php artisan config:cache`, ATAU
  - Biarkan sistem secara otomatis mengaktifkan modul **Simulation Fallback Engine**.

#### Masalah 4: `Call to undefined function curl_init()`
- **Penyebab:** Ekstensi `php-curl` belum terpasang pada runtime server.
- **Solusi:** Pasang ekstensi dengan `sudo apt install php8.2-curl` lalu restart FPM.

### 8.3 Mekanisme Graceful Degradation (Failover Otomatis)

Sistem AI Chatbot TernakVerse dilengkapi arsitektur **Graceful Degradation** tiga lapis:

```
+----------------------------------------------------------------------------------------------------+
|                                HIRARKI FAILOVER & DEGRADASI ANGGUN                                 |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [ LAYER 1: Cloud Foundation Model ]                                                               |
|  - Google Gemini 1.5 Flash / OpenAI GPT-4o                                                         |
|  - Eksekusi RAG Vektor + Dynamic Function Calling                                                  |
|       |                                                                                            |
|       |-- Jika Timeout (> 10s), Kuota 429, atau DNS Failure                                        |
|       v                                                                                            |
|  [ LAYER 2: Intranet Self-Hosted Model ]                                                           |
|  - Ollama Daemon (Llama 3 8B / Qwen 2.5)                                                           |
|  - Menjaga kontinuitas penalaran bahasa alami di jaringan lokal Pemkab                             |
|       |                                                                                            |
|       |-- Jika Service Ollama Mati / Server Offline                                               |
|       v                                                                                            |
|  [ LAYER 3: Deterministic Simulation Fallback Engine ]                                             |
|  - Ekstraksi entitas berbasis ekspresi reguler (27 kecamatan & kata kunci penyakit)                |
|  - Eksekusi langsung ke Model Eloquent Database (100% Data Faktual Tanpa Halusinasi)               |
|  - Sintesis jawaban berbasis template Markdown klinis resmi & nomor Poskeswan                      |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

Dengan hierarki ini, portal WebGIS dan pengguna lapangan di Kabupaten Banyumas dijamin **tidak akan pernah mengalami kegagalan total (*zero downtime*)**, bahkan saat seluruh koneksi internet internasional terputus.

---
*Dokumen ini merupakan panduan operasional resmi deployment Modul AI Chatbot TernakVerse Kabupaten Banyumas.*
