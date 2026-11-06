<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\ThematicInterval;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemGovernanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi interval tematik resmi GIS, parameter tata kelola sistem, dan default whitelist IP.
     */
    public function run(): void
    {
        $adminId = User::where('role', 'superadmin')->value('id');

        // 1. Seed Thematic Intervals (Populasi, Produksi, Penyakit, Fasilitas)
        // Forest Intelligence tokens & Semantics
        $intervals = [
            // Populasi (ekor) - Forest Intelligence scale
            [
                'kategori' => 'populasi',
                'tier' => 1,
                'label' => 'Sangat Rendah',
                'min_value' => 0,
                'max_value' => 500,
                'color_hex' => '#064B50',
                'satuan' => 'ekor',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'populasi',
                'tier' => 2,
                'label' => 'Rendah',
                'min_value' => 501,
                'max_value' => 2000,
                'color_hex' => '#08736B',
                'satuan' => 'ekor',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'populasi',
                'tier' => 3,
                'label' => 'Sedang',
                'min_value' => 2001,
                'max_value' => 5000,
                'color_hex' => '#159B78',
                'satuan' => 'ekor',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'populasi',
                'tier' => 4,
                'label' => 'Tinggi',
                'min_value' => 5001,
                'max_value' => 15000,
                'color_hex' => '#36C99B',
                'satuan' => 'ekor',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'populasi',
                'tier' => 5,
                'label' => 'Sangat Tinggi',
                'min_value' => 15001,
                'max_value' => null,
                'color_hex' => '#75E6C5',
                'satuan' => 'ekor',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],

            // Produksi (kg) - Forest Intelligence scale
            [
                'kategori' => 'produksi',
                'tier' => 1,
                'label' => 'Sangat Rendah',
                'min_value' => 0,
                'max_value' => 1000,
                'color_hex' => '#064B50',
                'satuan' => 'kg',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'produksi',
                'tier' => 2,
                'label' => 'Rendah',
                'min_value' => 1001,
                'max_value' => 5000,
                'color_hex' => '#08736B',
                'satuan' => 'kg',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'produksi',
                'tier' => 3,
                'label' => 'Sedang',
                'min_value' => 5001,
                'max_value' => 20000,
                'color_hex' => '#159B78',
                'satuan' => 'kg',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'produksi',
                'tier' => 4,
                'label' => 'Tinggi',
                'min_value' => 20001,
                'max_value' => 50000,
                'color_hex' => '#36C99B',
                'satuan' => 'kg',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'produksi',
                'tier' => 5,
                'label' => 'Sangat Tinggi',
                'min_value' => 50001,
                'max_value' => null,
                'color_hex' => '#75E6C5',
                'satuan' => 'kg',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],

            // Penyakit (kasus) - Green / Yellow / Red semantics
            [
                'kategori' => 'penyakit',
                'tier' => 1,
                'label' => 'Terkendali / Bebas',
                'min_value' => 0,
                'max_value' => 0,
                'color_hex' => '#20B88A', // --color-aman
                'satuan' => 'kasus',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'penyakit',
                'tier' => 2,
                'label' => 'Rendah',
                'min_value' => 1,
                'max_value' => 5,
                'color_hex' => '#86A65A',
                'satuan' => 'kasus',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'penyakit',
                'tier' => 3,
                'label' => 'Waspada / Sedang',
                'min_value' => 6,
                'max_value' => 15,
                'color_hex' => '#E8B84C', // --color-waspada
                'satuan' => 'kasus',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'penyakit',
                'tier' => 4,
                'label' => 'Tinggi',
                'min_value' => 16,
                'max_value' => 30,
                'color_hex' => '#F07845',
                'satuan' => 'kasus',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'penyakit',
                'tier' => 5,
                'label' => 'Wabah / Bahaya',
                'min_value' => 31,
                'max_value' => null,
                'color_hex' => '#DC625F', // --color-bahaya
                'satuan' => 'kasus',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],

            // Fasilitas (unit) - Forest Intelligence scale
            [
                'kategori' => 'fasilitas',
                'tier' => 1,
                'label' => 'Sangat Rendah',
                'min_value' => 0,
                'max_value' => 1,
                'color_hex' => '#064B50',
                'satuan' => 'unit',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'fasilitas',
                'tier' => 2,
                'label' => 'Rendah',
                'min_value' => 2,
                'max_value' => 3,
                'color_hex' => '#08736B',
                'satuan' => 'unit',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'fasilitas',
                'tier' => 3,
                'label' => 'Sedang',
                'min_value' => 4,
                'max_value' => 6,
                'color_hex' => '#159B78',
                'satuan' => 'unit',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'fasilitas',
                'tier' => 4,
                'label' => 'Tinggi',
                'min_value' => 7,
                'max_value' => 10,
                'color_hex' => '#36C99B',
                'satuan' => 'unit',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
            [
                'kategori' => 'fasilitas',
                'tier' => 5,
                'label' => 'Sangat Tinggi',
                'min_value' => 11,
                'max_value' => null,
                'color_hex' => '#75E6C5',
                'satuan' => 'unit',
                'calculation_mode' => 'manual',
                'is_active' => true,
                'effective_date' => '2026-01-01',
                'updated_by' => $adminId,
            ],
        ];

        foreach ($intervals as $interval) {
            ThematicInterval::updateOrCreate(
                [
                    'kategori' => $interval['kategori'],
                    'tier' => $interval['tier'],
                ],
                $interval
            );
        }

        // 2. Seed System Settings
        $settings = [
            // Maintenance
            [
                'key' => 'maintenance.enabled',
                'value' => false,
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Status aktif pemeliharaan sistem',
                'is_encrypted' => false,
            ],
            [
                'key' => 'maintenance.message',
                'value' => 'Portal TernakVerse sedang dalam pemeliharaan berkala untuk peningkatan performa.',
                'type' => 'string',
                'group' => 'maintenance',
                'description' => 'Pesan pemberitahuan saat pemeliharaan aktif',
                'is_encrypted' => false,
            ],
            [
                'key' => 'maintenance.scheduled_end',
                'value' => null,
                'type' => 'string',
                'group' => 'maintenance',
                'description' => 'Waktu perkiraan pemeliharaan berakhir',
                'is_encrypted' => false,
            ],

            // Feature flags (General)
            [
                'key' => 'feature.ai_chatbot',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Fitur asisten cerdas AI Chatbot Tanya Ternak',
                'is_encrypted' => false,
            ],
            [
                'key' => 'feature.interactive_gis',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Fitur peta interaktif spasial WebGIS',
                'is_encrypted' => false,
            ],
            [
                'key' => 'feature.spatial_analysis',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Fitur kalkulasi dan analisis zonasi choropleth',
                'is_encrypted' => false,
            ],
            [
                'key' => 'feature.public_visualization',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Fitur visualisasi publik dan portal satu data',
                'is_encrypted' => false,
            ],
            [
                'key' => 'feature.external_api',
                'value' => true,
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Fitur akses API eksternal interoperabilitas dinas',
                'is_encrypted' => false,
            ],

            // Policy
            [
                'key' => 'policy.conversion_st_sapi',
                'value' => '1.0',
                'type' => 'string',
                'group' => 'policy',
                'description' => 'Koefisien konversi Satuan Ternak (ST) untuk Sapi',
                'is_encrypted' => false,
            ],
            [
                'key' => 'policy.conversion_st_kambing',
                'value' => '0.14',
                'type' => 'string',
                'group' => 'policy',
                'description' => 'Koefisien konversi Satuan Ternak (ST) untuk Kambing dan Domba',
                'is_encrypted' => false,
            ],
            [
                'key' => 'policy.conversion_st_unggas',
                'value' => '0.01',
                'type' => 'string',
                'group' => 'policy',
                'description' => 'Koefisien konversi Satuan Ternak (ST) untuk Unggas',
                'is_encrypted' => false,
            ],
            [
                'key' => 'policy.hmt_multiplier',
                'value' => '6.0',
                'type' => 'string',
                'group' => 'policy',
                'description' => 'Pengali kebutuhan Hijauan Makanan Ternak (HMT)',
                'is_encrypted' => false,
            ],

            // Security
            [
                'key' => 'security.force_2fa',
                'value' => false,
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Wajibkan autentikasi dua faktor untuk semua pengguna dinas',
                'is_encrypted' => false,
            ],
            [
                'key' => 'security.session_timeout_minutes',
                'value' => 120,
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Batas waktu inaktivitas sesi pengguna (menit)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'security.max_login_attempts',
                'value' => 5,
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Batas maksimal kegagalan autentikasi sebelum akun terkunci',
                'is_encrypted' => false,
            ],

            // Integration
            [
                'key' => 'integration.whatsapp_enabled',
                'value' => false,
                'type' => 'boolean',
                'group' => 'integration',
                'description' => 'Status aktifasi integrasi WhatsApp Gateway',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.whatsapp_url',
                'value' => 'https://api.fonnte.com/send',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'URL endpoint WhatsApp Gateway untuk broadcast notifikasi EWS',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.whatsapp_gateway_url',
                'value' => 'https://api.fonnte.com/send',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'URL endpoint WhatsApp Gateway (alias)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.whatsapp_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Token API otorisasi WhatsApp Gateway',
                'is_encrypted' => true,
            ],
            [
                'key' => 'integration.whatsapp_api_token',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Token API otorisasi WhatsApp Gateway (alias)',
                'is_encrypted' => true,
            ],
            [
                'key' => 'integration.whatsapp_sender',
                'value' => '081234567890',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Nomor pengirim default WhatsApp Gateway',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_host',
                'value' => 'smtp.mailtrap.io',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Host relay mail SMTP',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_port',
                'value' => 2525,
                'type' => 'integer',
                'group' => 'integration',
                'description' => 'Port relay mail SMTP',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_username',
                'value' => '',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Akun pengguna otorisasi SMTP',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_user',
                'value' => '',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Akun pengguna otorisasi SMTP (alias)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_password',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Kata sandi akun otorisasi SMTP',
                'is_encrypted' => true,
            ],
            [
                'key' => 'integration.smtp_encryption',
                'value' => 'tls',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Protokol enkripsi SMTP (tls/ssl)',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.smtp_from',
                'value' => 'diskanak@banyumaskab.go.id',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Alamat email pengirim resmi sistem',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.geoportal_url',
                'value' => 'https://geoportal.banyumaskab.go.id/geoserver/wfs',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'URL Geoportal Pemkab Banyumas',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.geoportal_token',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Token autentikasi simpul GeoPortal',
                'is_encrypted' => true,
            ],
            [
                'key' => 'integration.geoportal_layer',
                'value' => 'banyumas:batas_kecamatan',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'Layer default Web Feature Service GeoPortal',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.bps_url',
                'value' => 'https://webapi.bps.go.id/v1/api/data',
                'type' => 'string',
                'group' => 'integration',
                'description' => 'URL Web API Satu Data BPS',
                'is_encrypted' => false,
            ],
            [
                'key' => 'integration.bps_key',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Kunci API Satu Data BPS',
                'is_encrypted' => true,
            ],
            [
                'key' => 'integration.bps_api_token',
                'value' => '',
                'type' => 'encrypted',
                'group' => 'integration',
                'description' => 'Token integrasi API Satu Data BPS Banyumas (alias)',
                'is_encrypted' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::set(
                key: $setting['key'],
                value: $setting['value'],
                group: $setting['group'],
                type: $setting['type'],
                description: $setting['description'],
                isEncrypted: $setting['is_encrypted'],
                updatedBy: $adminId
            );
        }

        // 3. Seed Default Whitelist IP Access Rules
        $ipRules = [
            [
                'ip_address' => '127.0.0.1',
                'rule_type' => 'whitelist',
                'description' => 'Localhost IPv4 loopback development & worker access',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'ip_address' => '::1',
                'rule_type' => 'whitelist',
                'description' => 'Localhost IPv6 loopback development & worker access',
                'is_active' => true,
                'created_by' => $adminId,
            ],
        ];

        foreach ($ipRules as $rule) {
            DB::table('ip_access_rules')->updateOrInsert(
                ['ip_address' => $rule['ip_address']],
                [
                    'rule_type' => $rule['rule_type'],
                    'description' => $rule['description'],
                    'is_active' => $rule['is_active'],
                    'created_by' => $rule['created_by'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
