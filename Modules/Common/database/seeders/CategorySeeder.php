<?php

namespace Modules\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Common\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // ===============================
            // Teknologi & Digital
            // ===============================
            ['name' => 'Teknologi Informasi', 'value' => 'information-technology'],
            ['name' => 'Pengembangan Perangkat Lunak', 'value' => 'software-development'],
            ['name' => 'Pengembangan Web', 'value' => 'web-development'],
            ['name' => 'Pengembangan Aplikasi Mobile', 'value' => 'mobile-development'],
            ['name' => 'Jaringan dan Infrastruktur', 'value' => 'network-infrastructure'],
            ['name' => 'Administrasi Sistem', 'value' => 'system-administration'],
            ['name' => 'Keamanan Informasi', 'value' => 'information-security'],
            ['name' => 'Forensik Digital', 'value' => 'digital-forensics'],
            ['name' => 'Data dan Analitik', 'value' => 'data-analytics'],
            ['name' => 'Data Science', 'value' => 'data-science'],
            ['name' => 'Cloud Computing', 'value' => 'cloud-computing'],
            ['name' => 'DevOps', 'value' => 'devops'],
            ['name' => 'Internet of Things', 'value' => 'iot'],
            ['name' => 'Kecerdasan Artifisial', 'value' => 'artificial-intelligence'],
            ['name' => 'Blockchain', 'value' => 'blockchain'],
            ['name' => 'Game Development', 'value' => 'game-development'],

            // ===============================
            // Manajemen & Bisnis
            // ===============================
            ['name' => 'Manajemen Proyek', 'value' => 'project-management'],
            ['name' => 'Manajemen Operasional', 'value' => 'operations-management'],
            ['name' => 'Manajemen Risiko', 'value' => 'risk-management'],
            ['name' => 'Manajemen Strategis', 'value' => 'strategic-management'],
            ['name' => 'Kewirausahaan', 'value' => 'entrepreneurship'],
            ['name' => 'Bisnis Digital', 'value' => 'digital-business'],
            ['name' => 'Pemasaran', 'value' => 'marketing'],
            ['name' => 'Pemasaran Digital', 'value' => 'digital-marketing'],
            ['name' => 'Penjualan', 'value' => 'sales'],
            ['name' => 'Keuangan', 'value' => 'finance'],
            ['name' => 'Akuntansi', 'value' => 'accounting'],
            ['name' => 'Perpajakan', 'value' => 'taxation'],
            ['name' => 'Pengadaan Barang dan Jasa', 'value' => 'procurement'],

            // ===============================
            // SDM & Profesional
            // ===============================
            ['name' => 'Sumber Daya Manusia', 'value' => 'human-resources'],
            ['name' => 'Administrasi Perkantoran', 'value' => 'office-administration'],
            ['name' => 'Layanan Pelanggan', 'value' => 'customer-service'],
            ['name' => 'Kepemimpinan', 'value' => 'leadership'],
            ['name' => 'Komunikasi Profesional', 'value' => 'professional-communication'],
            ['name' => 'Pelatihan dan Pengembangan', 'value' => 'training-development'],
            ['name' => 'Coaching dan Mentoring', 'value' => 'coaching-mentoring'],
            ['name' => 'Etika Profesi', 'value' => 'professional-ethics'],
            ['name' => 'Produktivitas Kerja', 'value' => 'work-productivity'],

            // ===============================
            // Bahasa & Literasi
            // ===============================
            ['name' => 'Bahasa Inggris', 'value' => 'english-language'],
            ['name' => 'Bahasa Jepang', 'value' => 'japanese-language'],
            ['name' => 'Bahasa Mandarin', 'value' => 'mandarin-language'],
            ['name' => 'Bahasa Korea', 'value' => 'korean-language'],
            ['name' => 'Bahasa Asing Lainnya', 'value' => 'foreign-language'],
            ['name' => 'Literasi Digital', 'value' => 'digital-literacy'],
            ['name' => 'Literasi Informasi', 'value' => 'information-literacy'],

            // ===============================
            // Desain & Kreatif
            // ===============================
            ['name' => 'Desain', 'value' => 'design'],
            ['name' => 'Desain Grafis', 'value' => 'graphic-design'],
            ['name' => 'UI UX Design', 'value' => 'ui-ux-design'],
            ['name' => 'Desain Produk', 'value' => 'product-design'],
            ['name' => 'Fotografi', 'value' => 'photography'],
            ['name' => 'Videografi', 'value' => 'videography'],
            ['name' => 'Produksi Multimedia', 'value' => 'multimedia-production'],
            ['name' => 'Animasi', 'value' => 'animation'],

            // ===============================
            // Industri & Teknikal
            // ===============================
            ['name' => 'Manufaktur', 'value' => 'manufacturing'],
            ['name' => 'Industri Otomotif', 'value' => 'automotive'],
            ['name' => 'Logistik dan Rantai Pasok', 'value' => 'supply-chain'],
            ['name' => 'Transportasi', 'value' => 'transportation'],
            ['name' => 'Kemaritiman', 'value' => 'maritime'],
            ['name' => 'Penerbangan', 'value' => 'aviation'],
            ['name' => 'Energi dan Kelistrikan', 'value' => 'energy-electrical'],
            ['name' => 'Migas', 'value' => 'oil-gas'],
            ['name' => 'Konstruksi', 'value' => 'construction'],
            ['name' => 'Keselamatan dan Kesehatan Kerja', 'value' => 'occupational-health-safety'],
            ['name' => 'Lingkungan Hidup', 'value' => 'environmental-management'],

            // ===============================
            // Pariwisata & Jasa
            // ===============================
            ['name' => 'Pariwisata', 'value' => 'tourism'],
            ['name' => 'Perhotelan', 'value' => 'hospitality'],
            ['name' => 'Kuliner dan Tata Boga', 'value' => 'culinary'],
            ['name' => 'Event Management', 'value' => 'event-management'],

            // ===============================
            // Pendidikan & Sertifikasi
            // ===============================
            ['name' => 'Pendidikan dan Pelatihan', 'value' => 'education-training'],
            ['name' => 'Asesmen Kompetensi', 'value' => 'competency-assessment'],
            ['name' => 'Metodologi Pelatihan', 'value' => 'training-methodology'],
            ['name' => 'Penyusunan Soal Uji Kompetensi', 'value' => 'assessment-instrument'],
            ['name' => 'Validasi dan Verifikasi Asesmen', 'value' => 'assessment-validation'],

            // ===============================
            // Kepatuhan & Mutu
            // ===============================
            ['name' => 'Standar dan Mutu', 'value' => 'quality-management'],
            ['name' => 'Audit dan Kepatuhan', 'value' => 'audit-compliance'],
            ['name' => 'Manajemen Mutu ISO', 'value' => 'iso-quality'],
            ['name' => 'Regulasi dan Hukum Bisnis', 'value' => 'business-law'],
        ];

        foreach ($categories as $cat) {
            Category::query()->firstOrCreate(
                ['value' => $cat['value']],
                [
                    'name' => $cat['name'],
                    'status' => 'active',
                ]
            );
        }
    }
}
