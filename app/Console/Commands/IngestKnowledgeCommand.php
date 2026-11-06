<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IngestKnowledgeCommand extends Command
{
    /**
     * Nama dan signature perintah console.
     *
     * @var string
     */
    protected $signature = 'ternakverse:ingest-knowledge 
                            {path? : Path to document or directory} 
                            {--category=veteriner : Document category} 
                            {--source=dinas : Issuing authority} 
                            {--seed-defaults : Seed default Banyumas veterinary guidelines}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Ingest and chunk official veterinary guidelines and regulations into the knowledge vector store';

    /**
     * Daftar separator hierarkis untuk pemotongan teks rekursif.
     *
     * @var list<string>
     */
    protected array $separators = [
        "\n\n",
        "\n",
        'Definisi:',
        'Gejala:',
        'Pengendalian:',
        'SOP:',
        '. ',
    ];

    /**
     * Eksekusi perintah console.
     */
    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('  TERNAKVERSE: Ingestion Engine & RAG Knowledge Base Chunker');
        $this->info('========================================================================');

        if (! Schema::hasTable('knowledge_documents') || ! Schema::hasTable('knowledge_chunks')) {
            $this->error('[GAGAL] Tabel `knowledge_documents` atau `knowledge_chunks` belum tersedia.');
            $this->line('Silakan jalankan migrasi database terlebih dahulu: php artisan migrate');

            return Command::FAILURE;
        }

        $path = $this->argument('path');
        $seedDefaults = (bool) $this->option('seed-defaults');
        $category = (string) ($this->option('category') ?: 'veteriner');
        $source = (string) ($this->option('source') ?: 'dinas');

        $docCount = KnowledgeDocument::count();

        // Kondisi 1: Seed default guidelines jika diminta atau jika path kosong dan basis data masih kosong
        if ($seedDefaults || ($path === null && $docCount === 0)) {
            $this->info("\n[PROSES] Menjalankan seeding 5 Dokumen Inti Pedoman Veteriner & Regulasi Banyumas...");

            return $this->seedDefaultDocuments();
        }

        // Kondisi 2: Jika path tidak diisi dan database sudah terisi dokumen
        if ($path === null) {
            $this->warn("\n[INFO] Basis data pengetahuan sudah memiliki {$docCount} dokumen.");
            $this->line("Gunakan opsi '--seed-defaults' untuk menyegarkan 5 pedoman standar Banyumas,");
            $this->line("atau tentukan argumen path: 'php artisan ternakverse:ingest-knowledge storage/docs/pedoman.md'");

            return Command::SUCCESS;
        }

        // Kondisi 3: Ingest dari file atau direktori lokal yang ditentukan pengguna
        return $this->ingestFromPath((string) $path, $category, $source);
    }

    /**
     * Melakukan seeding 5 dokumen resmi otoritatif Banyumas.
     */
    protected function seedDefaultDocuments(): int
    {
        $documents = $this->getDefaultSeedDocuments();
        $summaryTable = [];
        $totalChunksInserted = 0;

        foreach ($documents as $docData) {
            $result = $this->storeDocumentWithChunks($docData);
            $totalChunksInserted += $result['chunks_count'];

            $summaryTable[] = [
                $result['id'],
                Str::limit($result['title'], 45),
                $result['category'],
                $result['chunks_count'],
                $result['total_tokens'],
            ];
        }

        $this->info("\n[SELESAI] Berhasil memproses 5 dokumen pedoman resmi Banyumas:");
        $this->table(
            ['ID', 'Judul Dokumen', 'Kategori', 'Jumlah Chunk', 'Total Token Est.'],
            $summaryTable
        );

        $this->info("\nTotal potongan vektor (chunks) terindeks: {$totalChunksInserted} chunks.");

        return Command::SUCCESS;
    }

    /**
     * Ingest dokumen dari file tunggal atau direktori.
     */
    protected function ingestFromPath(string $path, string $category, string $source): int
    {
        $realPath = base_path($path);
        if (! file_exists($realPath) && ! file_exists($path)) {
            $this->error("\n[ERROR] Path tidak ditemukan: {$path}");

            return Command::FAILURE;
        }

        $targetPath = file_exists($realPath) ? $realPath : $path;
        $filesToProcess = [];

        if (is_dir($targetPath)) {
            $dirFiles = glob($targetPath.DIRECTORY_SEPARATOR.'*.{md,txt,json}', GLOB_BRACE) ?: [];
            $filesToProcess = $dirFiles;
            $this->info("Menemukan ".count($filesToProcess)." file dokumen di direktori: {$targetPath}");
        } else {
            $filesToProcess = [$targetPath];
        }

        if (empty($filesToProcess)) {
            $this->warn("Tidak ada file teks (*.md, *.txt, *.json) yang dapat diproses di: {$targetPath}");

            return Command::SUCCESS;
        }

        $summaryTable = [];
        $totalChunks = 0;

        foreach ($filesToProcess as $filePath) {
            $rawContent = file_get_contents($filePath);
            if ($rawContent === false || trim($rawContent) === '') {
                $this->warn("Melewati file kosong: {$filePath}");
                continue;
            }

            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $title = Str::title(str_replace(['_', '-'], ' ', $filename));
            $slug = Str::slug($filename);

            $docData = [
                'title' => $title,
                'slug' => $slug,
                'source_type' => $category === 'regulasi' ? 'regulasi' : 'pedoman_klinis',
                'author' => $source === 'dinas' ? 'Diskanak Banyumas' : 'Kementan RI',
                'file_path' => $filePath,
                'published_year' => (int) date('Y'),
                'content' => $rawContent,
                'metadata' => [
                    'kategori' => $category,
                    'tingkat_otoritas' => $source,
                    'file_source' => basename($filePath),
                ],
            ];

            $result = $this->storeDocumentWithChunks($docData);
            $totalChunks += $result['chunks_count'];

            $summaryTable[] = [
                $result['id'],
                Str::limit($result['title'], 45),
                $category,
                $result['chunks_count'],
                $result['total_tokens'],
            ];
        }

        $this->table(
            ['ID', 'Judul Dokumen', 'Kategori', 'Jumlah Chunk', 'Total Token Est.'],
            $summaryTable
        );

        $this->info("\n[SELESAI] Berhasil memproses ".count($summaryTable)." dokumen ({$totalChunks} chunks).");

        return Command::SUCCESS;
    }

    /**
     * Menyimpan KnowledgeDocument beserta KnowledgeChunks ke database secara atomik.
     *
     * @param array{
     *     title: string,
     *     slug: string,
     *     source_type: string,
     *     author: string,
     *     file_path: string,
     *     published_year: int,
     *     content: string,
     *     metadata?: array<string, mixed>
     * } $docData
     * @return array{id: int, title: string, category: string, chunks_count: int, total_tokens: int}
     */
    protected function storeDocumentWithChunks(array $docData): array
    {
        $fileHash = hash('sha256', $docData['content']);
        $chunks = $this->chunkText($docData['content']);
        $totalTokens = 0;

        /** @var KnowledgeDocument $document */
        $document = DB::transaction(function () use ($docData, $fileHash, $chunks, &$totalTokens) {
            $doc = KnowledgeDocument::updateOrCreate(
                ['slug' => $docData['slug']],
                [
                    'title' => $docData['title'],
                    'source_type' => $docData['source_type'],
                    'author' => $docData['author'],
                    'file_path' => $docData['file_path'],
                    'file_hash' => $fileHash,
                    'is_active' => true,
                    'published_year' => $docData['published_year'],
                ]
            );

            // Bersihkan chunk lama agar operasi idempotent
            $doc->chunks()->delete();

            $embeddingService = app(\App\Services\Chatbot\EmbeddingService::class);
            $totalCount = count($chunks);
            foreach ($chunks as $index => $chunkText) {
                $tokenCount = $this->estimateTokenCount($chunkText);
                $totalTokens += $tokenCount;

                $chunkMeta = array_merge($docData['metadata'] ?? [], [
                    'chunk_index' => $index,
                    'total_chunks' => $totalCount,
                    'judul_dokumen' => $doc->title,
                    'sumber' => $doc->author,
                    'published_year' => $doc->published_year,
                ]);

                $doc->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $chunkText,
                    'embedding' => $embeddingService->generateEmbedding($chunkText),
                    'token_count' => $tokenCount,
                    'metadata' => $chunkMeta,
                ]);
            }

            return $doc;
        });

        return [
            'id' => (int) $document->id,
            'title' => (string) $document->title,
            'category' => (string) ($docData['metadata']['kategori'] ?? 'veteriner'),
            'chunks_count' => count($chunks),
            'total_tokens' => $totalTokens,
        ];
    }

    /**
     * Membagi teks menjadi beberapa potongan (chunks) dengan batasan panjang karakter ~2000-4000 karakter
     * (ekivalen 512 - 1024 token) dan overlap semantik ~15% menggunakan urutan separator hierarkis:
     * ["\n\n", "\n", "Definisi:", "Gejala:", "Pengendalian:", "SOP:", ". "]
     *
     * @param string $text
     * @param int $maxChars
     * @param int $overlapChars
     * @param list<string>|null $separators
     * @return list<string>
     */
    public function chunkText(
        string $text,
        int $maxChars = 3200,
        int $overlapChars = 480,
        ?array $separators = null
    ): array {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        $activeSeparators = $separators ?? $this->separators;

        // Cari pemisah pertama yang ditemukan di dalam teks
        $chosenSeparator = null;
        $separatorIndex = -1;
        foreach ($activeSeparators as $idx => $sep) {
            if (str_contains($text, $sep)) {
                $chosenSeparator = $sep;
                $separatorIndex = $idx;
                break;
            }
        }

        // Jika tidak ada pemisah hierarkis yang cocok, gunakan fallback pemisah spasi
        if ($chosenSeparator === null) {
            return $this->splitFallback($text, $maxChars, $overlapChars);
        }

        // Sub-separator untuk pemecahan part yang masih terlalu besar
        $subSeparators = array_slice($activeSeparators, $separatorIndex + 1);

        // Khusus separator kata kunci, pertahankan kata kuncinya di awal bagian
        $isKeyword = in_array($chosenSeparator, ['Definisi:', 'Gejala:', 'Pengendalian:', 'SOP:'], true);
        if ($isKeyword) {
            $rawParts = preg_split('/(?=' . preg_quote($chosenSeparator, '/') . ')/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            $parts = $rawParts ?: [$text];
        } else {
            $parts = explode($chosenSeparator, $text);
        }

        $chunks = [];
        $currentChunk = '';

        foreach ($parts as $part) {
            $trimmedPart = trim($part);
            if ($trimmedPart === '') {
                continue;
            }

            // Jika satu part tunggal melebihi batas maxChars, panggil rekursif dengan sub-separator
            if (mb_strlen($trimmedPart) > $maxChars) {
                if ($currentChunk !== '') {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }

                $subChunks = $this->chunkText($trimmedPart, $maxChars, $overlapChars, $subSeparators);
                foreach ($subChunks as $subChunk) {
                    if ($subChunk !== '') {
                        $chunks[] = $subChunk;
                    }
                }
                continue;
            }

            $joiner = $isKeyword ? "\n\n" : $chosenSeparator;
            $candidate = ($currentChunk === '') ? $trimmedPart : $currentChunk . $joiner . $trimmedPart;

            if (mb_strlen($candidate) <= $maxChars) {
                $currentChunk = $candidate;
            } else {
                if ($currentChunk !== '') {
                    $chunks[] = trim($currentChunk);

                    // Ambil overlap ~15% dari akhir chunk sebelumnya
                    $overlap = $this->extractOverlap($currentChunk, $overlapChars);
                    $currentChunk = ($overlap !== '') ? $overlap . "\n\n" . $trimmedPart : $trimmedPart;
                } else {
                    $currentChunk = $trimmedPart;
                }
            }
        }

        if ($currentChunk !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Fallback pemotongan berbasis batas kata ketika tidak ada separator hierarkis yang cocok.
     *
     * @return list<string>
     */
    protected function splitFallback(string $text, int $maxChars, int $overlapChars): array
    {
        $chunks = [];
        $len = mb_strlen($text);
        $start = 0;

        while ($start < $len) {
            $slice = mb_substr($text, $start, $maxChars);
            if ($start + $maxChars < $len) {
                $lastSpace = mb_strrpos($slice, ' ');
                if ($lastSpace !== false && $lastSpace > (int) ($maxChars * 0.7)) {
                    $slice = mb_substr($slice, 0, $lastSpace);
                }
            }

            $chunks[] = trim($slice);
            $step = max(1, mb_strlen($slice) - $overlapChars);
            $start += $step;
        }

        return $chunks;
    }

    /**
     * Mengambil potongan akhir teks sebesar ~15% dengan memotong pada batas kalimat atau baris.
     */
    protected function extractOverlap(string $text, int $overlapChars): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) <= $overlapChars) {
            return $trimmed;
        }

        $tail = mb_substr($trimmed, -$overlapChars);

        // Cari batas baris baru terdekat
        $breakPos = mb_strpos($tail, "\n");
        if ($breakPos === false || $breakPos > 150) {
            // Cari batas kalimat (. )
            $dotPos = mb_strpos($tail, '. ');
            if ($dotPos !== false && $dotPos < 150) {
                $breakPos = $dotPos + 2;
            }
        } else {
            $breakPos += 1;
        }

        if ($breakPos === false || $breakPos > 150) {
            $spacePos = mb_strpos($tail, ' ');
            if ($spacePos !== false && $spacePos < 150) {
                $breakPos = $spacePos + 1;
            }
        }

        if ($breakPos !== false && $breakPos < mb_strlen($tail)) {
            return trim(mb_substr($tail, $breakPos));
        }

        return trim($tail);
    }

    /**
     * Menghitung estimasi token berdasarkan karakteristik teks bahasa Indonesia dan istilah medis veteriner.
     */
    public function estimateTokenCount(string $text): int
    {
        $charCount = mb_strlen($text);
        $wordCount = str_word_count(strip_tags($text));

        // Estimasi terbobot: 1 token ~ 3.5 - 4 karakter atau 1.3 kata
        return (int) max(ceil($wordCount * 1.3), ceil($charCount / 4), 1);
    }

    /**
     * Mengembalikan 5 dokumen otoritatif pedoman veteriner dan regulasi Banyumas.
     *
     * @return list<array{
     *     title: string,
     *     slug: string,
     *     source_type: string,
     *     author: string,
     *     file_path: string,
     *     published_year: int,
     *     content: string,
     *     metadata: array<string, mixed>
     * }>
     */
    protected function getDefaultSeedDocuments(): array
    {
        return [
            // Dokumen 1: PMK Banyumas 2024
            [
                'title' => 'Pedoman Pengendalian dan Penanggulangan PMK Banyumas 2024',
                'slug' => 'pedoman-pmk-banyumas-2024',
                'source_type' => 'pedoman_klinis',
                'author' => 'Diskanak Banyumas',
                'file_path' => 'knowledge/pmk_banyumas_2024.md',
                'published_year' => 2024,
                'metadata' => [
                    'kategori' => 'pmk',
                    'tingkat_otoritas' => 'Dinas Perikanan dan Peternakan Kabupaten Banyumas & Kementan RI',
                    'spesies_terkait' => ['sapi potong', 'sapi perah', 'kerbau', 'kambing', 'domba', 'babi'],
                    'tingkat_risiko' => 'DARURAT (Kritis)',
                ],
                'content' => <<<MARKDOWN
# Pedoman Pengendalian dan Penanggulangan Penyakit Mulut dan Kuku (PMK) Kabupaten Banyumas 2024

Definisi: Penyakit Mulut dan Kuku (PMK) atau Foot and Mouth Disease (FMD) adalah penyakit hewan menular akut yang menyerang hewan berkuku genap/belah (artiodactyla) seperti sapi potong, sapi perah, kerbau, kambing, domba, dan babi. Agen penyebab adalah Aphthovirus dari famili Picornaviridae yang memiliki daya tular sangat tinggi melalui kontak langsung, aerosol pernapasan jarak jauh, pakan/air minum terkontaminasi, serta perantara manusia dan kendaraan angkut ternak.

Gejala: Gejala pathognomonik klinis PMK stadium akut yang wajib diwaspadai:
1. Demam tinggi mendadak (febris) mencapai >40°C hingga 41.5°C, hewan terlihat menggigil dan lesu.
2. Hipersalivasi berbusa (drooling/ptyalism): air liur keluar menggantung berlebihan dan berbusa dari rongga mulut akibat peradangan hebat.
3. Lepuh (vesikel) dan erosi pada mukosa rongga mulut: terdapat lepuh pada lidah, gusi, bibir bagian dalam, bantalan gigi (dental pad), dan moncong yang cepat pecah meninggalkan luka erosi kemerahan yang sangat perih, menyebabkan hewan anoreksia total (mogok makan).
4. Lepuh pada sela kuku (interdigital pouch) dan batas koroner (coronary band) teracak kuku yang menyebabkan kepincangan akut berat (lameness), hewan sering mengentak-entakkan kaki, lebih memilih berbaring, hingga terjadinya pengelupasan atau teracak copot (exungulasi).
5. Pada sapi perah betina terjadi penurunan produksi susu drastis (hingga 80-100%) serta lepuh pada puting ambing yang dapat memicu mastitis kronis.

SOP: Prosedur Isolasi Kandang Ketat dan Larangan Mutasi:
1. Ternak yang menunjukkan gejala PMK wajib segera diisolasi secara ketat pada kandang karantina terpisah berjarak minimal 500 meter dari kelompok ternak sehat.
2. DILARANG KERAS melakukan mutasi, pemindahan, penjualan, atau pengangkutan hewan sakit keluar kandang maupun antar desa/kecamatan di wilayah Kabupaten Banyumas.
3. Pembatasan akses lalu lintas manusia: hanya petugas medis atau satu orang perawat kandang bertugas yang boleh memasuki kandang isolasi dengan mengenakan APD lengkap (boots karet, masker, sarung tangan, wearpack).
4. Seluruh peralatan kandang (tempat pakan, sekop, selang, ember) pada area isolasi ditandai khusus dan dilarang dipindahkan ke kandang hewan sehat.

Pengendalian: Dekontaminasi, Desinfeksi, dan Pengendalian Lingkungan:
1. Desinfeksi kandang dan lingkungan sekitar wajib dilakukan setiap hari menggunakan larutan desinfektan bersertifikasi:
   - Larutan Formalin 1% (10 ml formalin 37% per 1 liter air) untuk lantai dan dinding semen.
   - Larutan Asam Sitrat (Citric Acid) 0.2% (2 gram per 1 liter air) atau Asam Asetat/Cuka 2% yang sangat efektif melumpuhkan Aphthovirus pada peralatan dan pakaian.
   - Natrium Karbonat (Soda Abu) 4% atau Sodium Hipoklorit (Pemutih klorin) 2-3%.
2. Ban kendaraan pengangkut dan alas kaki wajib disemprot desinfektan sebelum memasuki atau meninggalkan area peternakan.
3. Kotoran ternak, sisa pakan, dan alas jerami dari kandang tertular wajib dikumpulkan dan ditimbun di lubang tertutup untuk proses pengomposan panas (minimal 60°C) atau dibakar.

Tata Laksana Terapi Suportif dan Kontak Darurat Poskeswan Banyumas:
Pengobatan pada ternak tertular bersifat suportif simtomatik untuk meredakan demam, mengurangi nyeri kuku/mulut, dan mencegah infeksi sekunder bakteri:
- Pembersihan luka mukosa mulut dengan larutan garam dapur hangat atau larutan asam sitrat encer.
- Pembersihan luka sela kuku dengan larutan antiseptik povidone iodine 10% dilanjutkan penyemprotan oxytetracycline spray (CTC/OTC spray).
- Pemberian antipiretik/analgesik injeksi (seperti Paracetamol atau Meloxicam) serta injeksi multivitamin dosis tinggi (Vitamin A, D, E, B-kompleks) dan ATP.
- Injeksi antibiotik broad-spectrum (penisilin-streptomisin atau oksitetrasiklin) oleh dokter hewan berwenang jika timbul komplikasi infeksi bakteri pada kuku.

Kontak Darurat Pusat Kesehatan Hewan (Poskeswan) Diskanak Banyumas (Layanan 24 Jam):
- Poskeswan Purwokerto: Hotline 0812-3456-7890 / (0281) 635123 (Wilayah layanan: Purwokerto Utara, Purwokerto Selatan, Purwokerto Barat, Purwokerto Timur, Baturraden, Kedungbanteng).
- Poskeswan Sokaraja: Hotline 0812-3456-7891 (Wilayah layanan: Sokaraja, Kembaran, Kalibagor, Patikraja).
- Poskeswan Ajibarang: Hotline 0812-3456-7892 (Wilayah layanan: Ajibarang, Pekuncen, Cilongok, Karanglewas).
- Poskeswan Wangon: Hotline 0812-3456-7893 (Wilayah layanan: Wangon, Jatilawang, Rawalo, Purwojati, Gumelar, Lumbir).
- Poskeswan Tambak: Hotline 0812-3456-7894 (Wilayah layanan: Tambak, Sumpiuh, Kemranjen, Somagede).
Pelaporan wajib dilakukan dalam tempo maksimal 1x24 jam sejak ditemukannya indikasi gejala klinis.
MARKDOWN
            ],

            // Dokumen 2: LSD Ternak Sapi
            [
                'title' => 'Pedoman Kesiapsiagaan Lumpy Skin Disease (LSD) Ternak Sapi',
                'slug' => 'pedoman-lsd-ternak-sapi',
                'source_type' => 'pedoman_klinis',
                'author' => 'Kementan RI & Diskanak Banyumas',
                'file_path' => 'knowledge/pedoman_lsd_sapi.md',
                'published_year' => 2024,
                'metadata' => [
                    'kategori' => 'lsd',
                    'tingkat_otoritas' => 'Kementan RI & Dinas Perikanan dan Peternakan Kabupaten Banyumas',
                    'spesies_terkait' => ['sapi potong', 'sapi perah', 'kerbau'],
                    'tingkat_risiko' => 'WASPADA (Moderat)',
                ],
                'content' => <<<MARKDOWN
# Pedoman Kesiapsiagaan dan Penanganan Lumpy Skin Disease (LSD) Ternak Sapi

Definisi: Lumpy Skin Disease (LSD) atau penyakit cacar benjol sapi adalah penyakit menular viral pada sapi dan kerbau yang disebabkan oleh Lumpy Skin Disease Virus (LSDV), anggota genus Capripoxvirus dalam famili Poxviridae. Karakteristik utama penyakit ini adalah munculnya nodul kulit khas di seluruh tubuh yang dapat menimbulkan kerusakan permanen pada kulit ternak, kehilangan berat badan parah, kemandulan temporer, dan penurunan produksi peternakan secara masif.

Gejala: Gejala klinis pathognomonik LSD yang wajib diidentifikasi di lapangan:
1. Nodul atau benjolan sirkuler berbatas tegas pada kulit: nodul berdiameter 1 hingga 7 cm muncul secara mendadak pada dermis dan subkutis, tersebar di seluruh permukaan tubuh terutama pada leher, kepala, ambing, skrotum, perineum, dan keempat tungkai kaki.
2. Demam tinggi persisten: suhu rektal melonjak mencapai 40°C hingga 41.5°C sebelum atau bersamaan dengan timbulnya nodul kulit.
3. Pembesaran limfonodus regional: limfonodus superfisial terutama limfonodus preskapularis dan prefemoralis membengkak hingga beberapa kali ukuran normal dan teraba keras saat dipalpasi.
4. Edema subkutan berat: terjadi penimbunan cairan edema pada tungkai bawah kaki, daerah dada, dan gelambir (dewlap), menyebabkan sapi pincang dan kaku bergerak.
5. Lesi nekrotik ("Sit-Fast"): bagian tengah nodul kulit mengalami nekrosis iskemik, mengeras dan mongering, lalu terlepas meninggalkan luka berlubang dalam yang sangat rentan mengalami infestasi belatung lalat (myiasis) dan abses bakteri sekunder.
6. Leleran hidung dan mata seropurulen hingga mukopurulen, hipersalivasi, erosi pada selaput lendir saluran napas dan cerna atas, serta depresi dan kekurusan progresif.

SOP: Pengendalian Vektor Serangga Penghisap Darah:
1. LSD ditularkan secara mekanis utama oleh vektor serangga hematofagus (penghisap darah), meliputi lalat kandang (Stomoxys calcitrans), lalat pikat (Tabanus spp.), lalat tanduk (Haematobia irritans), nyamuk (Culex spp., Aedes aegypti), serta caplak keras (Rhipicephalus appendiculatus).
2. SOP sanitasi vektor kandang peternakan rakyat Banyumas:
   - Pemasangan kasa insektisida atau kelambu kawat halus pada ventilasi kandang.
   - Pemasangan perangkap lalat mekanis/lem (fly trap) atau lampu perangkap serangga ultraviolet di sekeliling perimeter kandang.
   - Penyemprotan insektisida residual kandang (golongan piretroid sintetik seperti Cypermethrin atau Deltamethrin) pada dinding luar kandang setiap 2 minggu sekali.
   - Pemusnahan genangan air kotor, parit tergenang, dan timbunan kotoran basah yang menjadi sarang perkembangbiakan jentik nyamuk dan larva lalat.

Pengendalian: Vaksinasi dan Penanganan Medis Kuratif:
1. Vaksinasi Homolog Capripoxvirus:
   - Vaksinasi menggunakan homologous Live Attenuated Neethling strain merupakan metode proteksi paling efektif untuk membentuk kekebalan imunitas kawanan (herd immunity).
   - Seluruh sapi sehat berusia di atas 3 bulan di wilayah terancam atau zona kuning/merah wajib menerima vaksinasi tahunan sesuai alokasi vaksin Diskanak Banyumas.
2. Penanganan Ternak Tertular:
   - Karantina dan isolasi ternak tertular dari kelompok sapi sehat untuk meminimalkan paparan vektor serangga.
   - Perawatan luka topikal: semprotkan obat anti-larvasida (misal Gusanex spray atau larutan Cypermethrin encer) dan salep antiseptik pada nodul yang pecah guna mencegah myiasis.
   - Terapi simtomatik: injeksi antipiretik/analgesik (Ketoprofen atau Flunixin Meglumine) untuk menekan demam dan nyeri.
   - Injeksi antihistamin guna meredakan reaksi radang kulit dan edema.
   - Injeksi multivitamin ADE konsentrasi tinggi untuk mempercepat epitelisasi regenerasi jaringan kulit yang rusak.
MARKDOWN
            ],

            // Dokumen 3: SOP Biosekuriti Kandang
            [
                'title' => 'Standar Operasional Prosedur (SOP) Biosekuriti Kandang Peternakan Rakyat Banyumas',
                'slug' => 'sop-biosekuriti-kandang-banyumas',
                'source_type' => 'sop',
                'author' => 'Diskanak Banyumas',
                'file_path' => 'knowledge/sop_biosekuriti_kandang.md',
                'published_year' => 2023,
                'metadata' => [
                    'kategori' => 'biosecurity',
                    'tingkat_otoritas' => 'Dinas Perikanan dan Peternakan Kabupaten Banyumas',
                    'spesies_terkait' => ['sapi potong', 'sapi perah', 'kambing', 'domba', 'unggas'],
                    'tingkat_risiko' => 'PENCEGAHAN & SANITASI',
                ],
                'content' => <<<MARKDOWN
# Standar Operasional Prosedur (SOP) Biosekuriti Kandang Peternakan Rakyat Banyumas

Definisi: Biosekuriti kandang adalah serangkaian tindakan operasional, struktural, dan manajemen kebersihan yang dirancang untuk mencegah masuknya agen penyakit infeksius ke dalam kompleks peternakan (bio-eksklusi), mencegah penularan antarkandang di dalam peternakan (bio-manajemen), serta menahan keluarnya bibit penyakit ke lingkungan luar peternakan (bio-kontainmen). Tiga pilar utama biosekuriti adalah Isolasi, Sanitasi/Pembersihan, dan Desinfeksi.

SOP: Prosedur Dipping Alas Kaki dan Sanitasi Pintu Masuk:
1. Setiap pintu masuk kandang peternakan rakyat wajib memiliki bak celup kaki (footbath / dipping) beralas spons atau karpet karet berpori.
2. Bak dipping diisi larutan desinfektan aktif yang terbukti efektif melumpuhkan virus dan bakteri, antara lain:
   - Larutan Klorin aktif 0.5% (Sodium Hipoklorit encer).
   - Larutan Benzalkonium Klorida (BKC) 1% - 2%.
   - Larutan Asam Sitrat 0.2% atau larutan Formalin 1%.
3. Setiap peternak, anak kandang, paramedik, dan tamu wajib mencelupkan sepatu bot karet ke dalam bak dipping selama minimal 10-15 detik sebelum melangkah masuk dan saat keluar dari pintu kandang.
4. Cairan desinfektan pada bak footbath wajib diganti secara rutin minimal 2 sampai 3 hari sekali, atau segera diganti bila larutan telah keruh akibat lumpur atau kotoran feses.

SOP: Prosedur Karantina Ternak Baru (Masa Isolasi 14 Hari):
1. Setiap ternak baru yang didatangkan dari pasar hewan, luar kecamatan, atau peternakan lain WAJIB diisolasi di kandang karantina khusus selama minimal 14 hari berturut-turut sebelum digabungkan dengan ternak lama.
2. Kandang karantina harus berjarak fisik minimal 15-20 meter dari kandang utama ternak resident, berventilasi lancar, dan memiliki saluran pembuangan limbah terpisah.
3. Selama masa karantina 14 hari:
   - Dilakukan pengecekan suhu rektal harian pagi dan sore.
   - Pemeriksaan kondisi mukosa mulut, kuku, permukaan kulit, dan nafsu makan.
   - Pemberian pakan dan air minum dilakukan setelah seluruh ternak di kandang sehat selesai dilayani guna mencegah kontaminasi silang.
   - Lapor kepada petugas teknis peternakan/Poskeswan setempat untuk verifikasi status kesehatan dan pelaksanaan vaksinasi sebelum penggabungan kawanan.

Pengendalian: Pembatasan Tamu, Kendaraan Pedagang, dan Pengelolaan Limbah Feses:
1. Pembatasan Pengunjung dan Pedagang Ternak (Belantik):
   - Pedagang ternak luar atau tamu dilarang keras memasuki area kandang pemeliharaan utama tanpa izin dan tanpa melewati prosedur dekontaminasi.
   - Dilarang membawa pakaian, karung rumput bekas, atau tali kekang dari pasar hewan langsung ke dalam kandang ternak resident.
2. Dekontaminasi Kendaraan:
   - Kendaraan truk atau pikap pengangkut ternak, pakan, atau rumput wajib disemprot desinfektan pada bagian ban, spakbor, dan bak muatan di area perimeter luar sebelum diperkenankan bongkar muat.
3. Pembersihan Kotoran dan Higiene Harian:
   - Feses, kotoran, dan sisa pakan wajib dibersihkan secara teratur minimal 2 kali sehari (pagi dan sore).
   - Lantai kandang disiram bersih dan dikeringkan secara optimal untuk mencegah kelembaban tinggi yang disukai spora jamur dan larva parasit.
   - Kotoran feses dikumpulkan pada bak penampungan kompos tertutup berjarak minimal 20 meter dari kandang dan sumur air minum peternakan.
MARKDOWN
            ],

            // Dokumen 4: Perda Banyumas No 7 Tahun 2023
            [
                'title' => 'Peraturan Daerah Kabupaten Banyumas Nomor 7 Tahun 2023 tentang Penyelenggaraan Peternakan dan Kesehatan Hewan',
                'slug' => 'perda-banyumas-nomor-7-tahun-2023',
                'source_type' => 'regulasi',
                'author' => 'Pemerintah Kabupaten Banyumas',
                'file_path' => 'knowledge/perda_banyumas_no_7_2023.md',
                'published_year' => 2023,
                'metadata' => [
                    'kategori' => 'regulasi',
                    'tingkat_otoritas' => 'Pemerintah Daerah Kabupaten Banyumas & DPRD Kabupaten Banyumas',
                    'spesies_terkait' => ['semua komoditas ternak'],
                    'tingkat_risiko' => 'REGULASI & KEWAJIBAN HUKUM',
                ],
                'content' => <<<MARKDOWN
# Peraturan Daerah Kabupaten Banyumas Nomor 7 Tahun 2023 tentang Penyelenggaraan Peternakan dan Kesehatan Hewan

Definisi: Peraturan Daerah Kabupaten Banyumas Nomor 7 Tahun 2023 merupakan landasan hukum resmi penyelenggaraan urusan peternakan, perbibitan, pakan, kesehatan hewan, kesehatan masyarakat veteriner (Kesmavet), dan perlindungan kesejahteraan hewan (animal welfare) di seluruh wilayah administratif Kabupaten Banyumas yang terdiri dari 27 Kecamatan dan 331 Desa/Kelurahan.

SOP: Kewajiban Surat Keterangan Kesehatan Hewan (SKKH):
1. Pasal 28 ayat (1): Setiap orang atau badan usaha yang melalulintaskan hewan ternak (sapi potong, sapi perah, kerbau, kambing, domba, babi, dan unggas) ke luar daerah, masuk ke wilayah Kabupaten Banyumas, maupun antar-pasar hewan di dalam Kabupaten Banyumas WAJIB dilengkapi Surat Keterangan Kesehatan Hewan (SKKH) yang sah.
2. Penerbitan SKKH hanya dapat dilakukan oleh Dokter Hewan Berwenang / Medik Veteriner yang ditunjuk secara resmi oleh Kepala Dinas Perikanan dan Peternakan Kabupaten Banyumas setelah dilakukan pemeriksaan fisik klinis langsung pada hewan.
3. Masa berlaku SKKH antarwilayah adalah 1 x 24 jam untuk pengiriman hewan potong langsung ke RPH, atau maksimal 3 hari kalender untuk keperluan pemeliharaan/bibit sejak tanggal diterbitkan.
4. Pengelola pasar hewan (Pasar Hewan Ajibarang, Sokaraja, dan pasar hewan lainnya di Banyumas) dilarang mengizinkan hewan ternak memasuki area tambat pasar tanpa verifikasi fisik dokumen SKKH asli oleh petugas pos jaga terpadu.

Pengendalian: Kewajiban Pelaporan Kasus 1x24 Jam dan Pengawasan Peredaran:
1. Pelaporan Penyakit Hewan Menular Strategis (PHMS):
   - Pasal 35 ayat (2): Peternak, pemilik hewan, dokter hewan praktek mandiri, dan aparat desa WAJIB melaporkan dugaan kejadian penyakit menular strategis (seperti PMK, LSD, Anthrax, Rabies, Brucellosis, Avian Influenza) kepada Diskanak Banyumas atau Poskeswan terdekat dalam waktu paling lambat 1 x 24 jam sejak ditemukannya tanda klinis awal.
   - Pelanggaran terhadap kewajiban pelaporan yang menyebabkan meluasnya wabah penyakit dapat dikenai sanksi administratif hingga sanksi pidana.
2. Pengawasan Peredaran Daging dan Karkas:
   - Daging segar atau beku yang diedarkan di pasar tradisional dan pasar modern Banyumas wajib berasal dari Rumah Potong Hewan (RPH) resmi yang mengantongi Nomor Kontrol Veteriner (NKV) dan sertifikat Halal BPJPH.
   - Larangan keras mengedarkan daging gelonggongan, daging bangkai (tiren), atau daging yang terkontaminasi bahan kimia berbahaya.
3. Perlindungan Sapi Betina Produktif:
   - Dilarang menyembelih sapi potong atau sapi perah betina produktif, kecuali dinyatakan majir/mandul secara tertulis oleh Medik Veteriner atau dalam keadaan darurat karena kecelakaan fatal.

Sanksi Administratif dan Ketentuan Pidana:
Setiap orang atau badan hukum yang melanggar ketentuan peredaran ternak tanpa SKKH, pemalsuan dokumen veteriner, atau penjualan hewan tertular wabah dapat dikenakan sanksi bertingkat:
- Peringatan tertulis pertama dan kedua.
- Penghentian sementara operasional kegiatan usaha peternakan atau penutupan lapak pasar hewan.
- Pencabutan izin usaha peternakan.
- Sanksi pidana kurungan paling lama 6 (enam) bulan atau pidana denda maksimal Rp 50.000.000 (lima puluh juta rupiah) sebagaimana diatur dalam ketentuan perundang-undangan peternakan nasional.
MARKDOWN
            ],

            // Dokumen 5: Juknis RPH dan Daging ASUH
            [
                'title' => 'Petunjuk Teknis Rumah Potong Hewan (RPH) dan Penjaminan Daging ASUH (Aman, Sehat, Utuh, Halal)',
                'slug' => 'juknis-rph-penjaminan-daging-asuh',
                'source_type' => 'pedoman_klinis',
                'author' => 'Diskanak Banyumas & BPJPH',
                'file_path' => 'knowledge/juknis_rph_daging_asuh.md',
                'published_year' => 2023,
                'metadata' => [
                    'kategori' => 'rph_asuh',
                    'tingkat_otoritas' => 'Diskanak Banyumas & Badan Penyelenggara Jaminan Produk Halal (BPJPH)',
                    'spesies_terkait' => ['sapi potong', 'kerbau', 'kambing', 'domba', 'unggas'],
                    'tingkat_risiko' => 'KESMAVET & PENJAMINAN HALAL',
                ],
                'content' => <<<MARKDOWN
# Petunjuk Teknis Rumah Potong Hewan (RPH) dan Penjaminan Daging ASUH (Aman, Sehat, Utuh, Halal)

Definisi: Daging ASUH adalah standar kualitas daging konsumsi masyarakat yang memenuhi kriteria:
1. Aman: Tidak mengandung bahaya biologis (bakteri patogen Salmonella, E. coli, Bacillus anthracis), residu kimia antibiotik/pestisida, hormon, serta benda fisik asing.
2. Sehat: Berasal dari hewan potong yang sehat, bergizi baik, disembelih secara higienis, dan layak dikonsumsi manusia (edible).
3. Utuh: Tidak dicampur dengan daging spesies lain yang dilarang atau lebih rendah kualitasnya, serta tidak mengalami proses penipuan volume (bebas rekayasa gelonggongan air).
4. Halal: Disembelih sesuai syariat Islam oleh Juru Sembelih Halal (Juleha) yang telah tersertifikasi Badan Penyelenggara Jaminan Produk Halal (BPJPH) dan Majelis Ulama Indonesia.

SOP: Standarisasi Nomor Kontrol Veteriner (NKV) dan Sertifikasi Halal BPJPH:
1. Setiap unit Rumah Potong Hewan Ruminansia (RPH-R) Pemerintah Kabupaten Banyumas (seperti RPH Kutasari, RPH Ajibarang) maupun RPH Unggas (RPH-U) swasta wajib memenuhi persyaratan teknis higiene sanitasi dan mengantongi sertifikat Nomor Kontrol Veteriner (NKV) minimal Level II dari Otoritas Veteriner Provinsi Jawa Tengah.
2. Standardisasi fasilitas meliputi pemisahan tegas zona bersih (clean zone: eviserasi, penimbangan karkas, chilling room) dan zona kotor (dirty zone: kandang penampung, stunning area, bleeding trench, scalding/skinning).
3. Seluruh proses penyembelihan wajib dipimpin oleh Juleha bersertifikat BNSP/BPJPH dengan memperhatikan kaidah kesejahteraan hewan (Animal Welfare): hewan tidak boleh dianiaya, menggunakan pisau sembelih yang sangat tajam tanpa gerigi dengan panjang bilah minimal 1.5 kali diameter leher hewan, dan memutus tiga saluran vital (trakea, esofagus, serta vena jugularis dan arteri karotis di kedua sisi leher) dalam sekali ayunan tanpa mengangkat pisau.

SOP: Prosedur Pemeriksaan Antemortem (Sebelum Pemotongan):
1. Pemeriksaan antemortem dilaksanakan oleh Medik Veteriner atau Paramedik Veteriner terlatih maksimal 24 jam sebelum proses pemotongan di kandang peristirahatan RPH.
2. Persyaratan peristirahatan ternak:
   - Hewan potong wajib diistirahatkan minimal 12 jam (dan maksimal 24 jam) di kandang penampungan RPH guna memulihkan cadangan glikogen otot sebelum pemotongan demi mutu rigor mortis dan pH daging ideal (5.4 - 5.8).
   - Hewan diberi akses air minum segar tanpa batas (ad libitum) tetapi dipuasakan dari pakan padat.
3. Kriteria kelolosan antemortem:
   - Bebas gejala klinis penyakit zoonosis atau penyakit menular eksotik (PMK, LSD, Anthrax, TBC).
   - Suhu tubuh rektal normal (38.0°C - 39.3°C untuk sapi dewasa).
   - Sapi betina diperiksa kebuntingannya; jika terbukti bunting atau betina bertanduk produktif, pemotongan DITOLAK KECUALI ada surat rekomendasi khusus afkir dari dinas.
   - Hewan yang cedera fisik atau fraktur ekstremitas dapat dilakukan pemotongan darurat bersyarat di bawah pengawasan medik veteriner.

Pengendalian: Prosedur Pemeriksaan Postmortem dan Penjaminan Mutu Karkas:
1. Pemeriksaan postmortem dilakukan segera setelah proses pengeluaran isi rongga dada dan perut (eviserasi) oleh petugas pemeriksa daging resmi:
   - Hati (Hepar): Pemeriksaan visual dan insisi saluran empedu untuk mendeteksi keberadaan cacing hati (Fasciola hepatica / Fasciola gigantica). Hati yang terinfestasi cacing parah dinyatakan tidak layak (condemned) dan dimusnahkan.
   - Paru-paru: Palpasi dan insisi lobus paru untuk memeriksa tuberkulosis noduler, pneumonia purulen, atau aspirasi darah.
   - Jantung dan Limpa: Memastikan tidak ada pembengkakan limpa akut hemoragik (tanda patognomonik antraks) serta memeriksa otot jantung dari sistiserkosis (Cysticercus bovis).
   - Limfonodus Karkas: Insisi limfonodus preskapularis, mediastinalis, dan retromamaria untuk memastikan tidak ada abses atau peradangan sistemik.
2. Penandaan Cap/Stempel Karkas:
   - Karkas yang dinyatakan sehat dan layak pangan diberi cap/stempel resmi daging RPH berwarna ungu food-grade Diskanak Banyumas yang memuat nomor registrasi dan tanggal pemotongan.
   - Daging karkas segera dimasukkan ke ruang pendingin (chilling room) bersuhu 0°C - 4°C untuk menjaga rantai dingin (cold chain) sebelum didistribusikan ke pasar rakyat dan konsumen.
MARKDOWN
            ],
        ];
    }
}
