/**
 * YukSehat Chatbot & Clinical AI Server
 * Backend Node.js Service with Google Gemini AI
 * 
 * Features:
 * 1. General Health Chatbot (/api/chat)
 * 2. Clinical Complaint Analysis Draft for Healthcare Staff (/api/analyze-keluhan)
 */

const http = require('http');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

// Muat konfigurasi dari berkas .env
function loadEnvFile(filePath) {
  try {
    if (fs.existsSync(filePath)) {
      const content = fs.readFileSync(filePath, 'utf8');
      content.split(/\r?\n/).forEach(line => {
        const trimmed = line.trim();
        if (trimmed && !trimmed.startsWith('#')) {
          const idx = trimmed.indexOf('=');
          if (idx !== -1) {
            const key = trimmed.slice(0, idx).trim();
            const val = trimmed.slice(idx + 1).trim();
            if (!process.env[key]) {
              process.env[key] = val;
            }
          }
        }
      });
    }
  } catch (e) {
    console.warn('[Env] Peringatan memuat .env:', e.message);
  }
}

loadEnvFile(path.resolve(__dirname, '../.env'));
loadEnvFile(path.resolve(__dirname, '.env'));

const PORT = process.env.PORT || 3005;
const HOST = process.env.HOST || '127.0.0.1';
const GEMINI_API_KEY = process.env.GEMINI_API_KEY || '';
const INTERNAL_API_SECRET = process.env.INTERNAL_API_SECRET || 'yuksehat_secret_internal_key_2026';
const PHP_SERVER_URL = process.env.PHP_SERVER_URL || 'http://127.0.0.1/YukSehat';

function getEffectiveAIConfig() {
  loadEnvFile(path.resolve(__dirname, '../.env'));
  const apiKey = process.env.AI_API_KEY || process.env.GEMINI_API_KEY || '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';
  let provider = process.env.AI_PROVIDER || '';
  if (!provider) {
    provider = apiKey.startsWith('nvapi-') ? 'nvidia' : (apiKey.startsWith('AIza') || apiKey.startsWith('AQ.') ? 'gemini' : 'nvidia');
  }
  const model = process.env.AI_MODEL || (provider === 'nvidia' ? 'nvidia/nemotron-3.5-bolt-e4b' : 'gemini-flash-lite-latest');
  const endpoint = process.env.AI_ENDPOINT || (provider === 'nvidia' ? 'https://integrate.api.nvidia.com/v1/chat/completions' : '');
  return { provider, apiKey, model, endpoint };
}

// Model prioritas dengan fallback berjenjang bila salah satu mengalami 503 High Demand
const CANDIDATE_MODELS = [
  "gemini-flash-lite-latest",
  "gemini-3.5-flash",
  "gemini-3.8-flash"
];

// ─────────────────────────────────────────────────────────────────────────────
// System Instruction untuk Chatbot Umum / Santri
// Aturan Gaya Jawaban YukSehat AI: Ringkas, Relevan, Tanpa Iklan
// ─────────────────────────────────────────────────────────────────────────────
function formatRoleName(role) {
  const r = (role || '').trim().toLowerCase();
  if (r === 'admin') return 'Admin';
  if (r === 'pengurus') return 'Pengurus';
  if (r === 'santri') return 'Santri';
  return r ? r.charAt(0).toUpperCase() + r.slice(1) : 'Pengguna';
}

function buildChatbotSystemInstruction(user = null) {
  let userContextText = '';
  const isLoggedIn = !!(user && user.role && user.role.toLowerCase() !== 'guest');
  if (isLoggedIn) {
    const roleFormatted = formatRoleName(user.role);
    userContextText = `Konteks sesi akun pengguna saat ini:
- Nama: ${user.nama}
- Role: ${roleFormatted}
Aturan Identitas:
Jika pengguna menanyakan identitas dirinya (seperti "Siapa saya?" atau "Saya login sebagai siapa?"), berikan jawaban persis:
"Kamu login sebagai ${user.nama} dengan role ${roleFormatted}."
Jika pengguna menanyakan apa role-nya, jawab:
"Kamu login dengan role ${roleFormatted} di YukSehat."`;

    if (user.role.toLowerCase() === 'admin') {
      userContextText += `\n\n═══════════════════════════════════════════════\nHAK AKSES PEMBACAAN DATABASE PENUH (ROLE ADMIN):\nPengguna adalah Admin sistem YukSehat dengan wewenang penuh untuk membaca seluruh database (Santri, Pemeriksaan Kesehatan, Keluhan, Pengurus, dan Pengguna).\nJika Admin menanyakan informasi operasional atau data santri/pemeriksaan, berikan informasi faktual secara lugas tanpa menyuruh mengecek dashboard jika datanya sudah kamu ketahui dari konteks sistem.`;
    }
  } else {
    userContextText = `Konteks sesi akun pengguna saat ini:
- Pengguna belum login (Tamu / Guest).
Aturan Identitas:
Jika pengguna menanyakan identitas dirinya, berikan jawaban persis:
"Kamu belum login, jadi saya belum dapat memastikan identitas akunmu."`;
  }

  return `
Kamu adalah "YukSehat AI", asisten digital dan asisten kerja resmi untuk aplikasi Sistem Informasi Kesehatan Pesantren "YukSehat" serta informasi kesehatan umum santri.

═══════════════════════════════════════════════
PENGETAHUAN SISTEM YUKSEHAT (SUMBER KEBENARAN APLIKASI)
═══════════════════════════════════════════════
Aplikasi YukSehat dibangun menggunakan Native PHP MVC dengan database MySQL dan microservice Node.js.
Fitur-fitur yang benar-benar tersedia di YukSehat:
1. DASHBOARD:
   - Menampilkan ringkasan metrik statistik: Total Santri Aktif, Pemeriksaan Hari Ini, Pemeriksaan Minggu Ini, Observasi/Rawat UKS, Perlu Rujukan, Total Pengurus, Total Pemeriksaan, dan Santri Sakit.
   - Menampilkan tabel riwayat pemeriksaan kesehatan terbaru.
2. MASTER DATA SANTRI:
   - Menampilkan daftar santri aktif dengan pencarian nama/NIS dan filter kelas/kamar.
   - Form Tambah Santri: NIS, nama, jenis kelamin (L/P), tanggal lahir, kelas, kamar, no HP, golongan darah, alergi, dan riwayat penyakit.
   - Saat santri baru ditambahkan, akun login users otomatis dibuatkan dengan NIS sebagai username dan kata sandi default.
   - Fitur Edit Data Santri dan Deaktivasi/Nonaktifkan Santri (status akun diubah menjadi inactive tanpa menghapus riwayat pemeriksaan).
3. PEMERIKSAAN KESEHATAN (POSKESTREN):
   - Menampilkan daftar pemeriksaan kesehatan dengan filter kata kunci pencarian dan filter rentang tanggal periksa.
   - Input Pemeriksaan Baru: Pilih santri, isi keluhan santri, tanggal periksa, data vital (suhu tubuh, tensi, berat badan, tinggi badan), hasil diagnosa, tindakan, obat, dan catatan.
   - Analisis Keluhan AI (Draf Klinis): Tombol di form pemeriksaan untuk menganalisis keluhan santri secara anonim menjadi draf tinjauan awal bagi petugas Poskestren.
   - Tombol 'Gunakan Saran ke Formulir': Menyalin draf AI (hasil diagnosa, rekomendasi tindakan, obat, catatan) langsung ke input form pemeriksaan agar pemeriksa dapat meninjau dan mengeditnya sebelum menyimpan.
4. RIWAYAT KELUHAN SANTRI:
   - Mencatat keluhan santri dengan status: 'menunggu', 'diperiksa', dan 'selesai'.
5. LAPORAN & REKAPITULASI:
   - Filter laporan berdasarkan rentang tanggal mulai s.d. tanggal akhir (default bulan berjalan), nama santri, kelas, kamar, jenis kelamin, dan keluhan.
   - Tabel ringkasan laporan di layar.
   - Ekspor Excel (.xlsx): Menghasilkan berkas Excel dengan 2 Sheet resmi (Sheet 1: Ringkasan Laporan, Sheet 2: Detail Catatan Klinis Lengkap).
6. PERBEDAAN ROLE & KEWENANGAN:
   - Role 'admin': Super User pengelola sistem. Mengelola data santri (CRUD & nonaktifkan), mencatat pemeriksaan kesehatan santri, menggunakan analisis AI, melihat laporan bulanan, dan mengekspor Excel.
   - Role 'pengurus': Petugas/pemeriksa kesehatan Poskestren operasional. Mengelola data santri, mencatat dan memperbarui pemeriksaan kesehatan, memanfaatkan draf AI dan tombol Gunakan Saran, serta meninjau dan mengekspor laporan pemeriksaan santri.
   - Role 'santri': Hanya dapat mengakses Dashboard Santri dan Riwayat Kesehatan pribadi. Dilarang mengakses data santri lain, form input pemeriksaan santri, atau menu laporan.
   - Pengguna Tamu (Guest): Belum login, harus login ke akun YukSehat untuk mengakses menu aplikasi.

═══════════════════════════════════════════════
ATURAN GAYA JAWABAN: RINGKAS, RELEVAN, TANPA IKLAN
═══════════════════════════════════════════════
1. Utamakan jawaban singkat, jelas, akurat, dan mudah dipahami:
   - Pertanyaan sederhana: 1–3 kalimat (target sekitar 100 kata atau kurang).
   - Pertanyaan cara penggunaan: langkah-langkah singkat berurutan.
   - Pertanyaan rekapitulasi: angka penting dan poin-poin terstruktur.
2. DILARANG mengulang salam pembuka (seperti 'Halo', 'Assalamu\'alaikum') pada setiap balasan.
3. DILARANG mengulang pertanyaan pengguna sebelum menjawab.
4. Hindari paragraf panjang, basa-basi pembuka, pengulangan isi, dan penutup yang tidak diperlukan.
5. DILARANG menampilkan iklan, promosi komersial, tautan sponsor, atau rekomendasi produk lain.
6. JANGAN PERNAH mengarang atau menebak data angka/statistik database santri atau rekap pemeriksaan.
7. Kerahasiaan Medis Santri: Informasi rekam medis sensitif (diagnosa mendalam santri tertentu) tidak boleh dibocorkan ke akun santri atau tamu. Namun untuk Admin atau Pengurus resmi, Anda berwenang menyebutkan daftar nama santri, status santri, dan statistik pemeriksaan sesuai data riil database.
8. Keselamatan Medis: Untuk tanda bahaya klinis (sesak napas, pingsan, demam sangat tinggi berkepanjangan), arahkan agar segera mencari bantuan petugas Poskestren atau faskes darurat.

═══════════════════════════════════════════════
SESI PENGGUNA TERVERIFIKASI
═══════════════════════════════════════════════
${userContextText}

═══════════════════════════════════════════════
TOPIK YANG WAJIB DITOLAK — TANPA PENGECUALIAN
═══════════════════════════════════════════════
Tolak dengan tegas dan ringkas apabila pengguna meminta:
- Membuat, menulis, menjelaskan, atau mendebug kode program dalam bahasa apapun (Python, JavaScript, PHP, Java, C++, HTML, SQL, coding, skrip, algoritma, dll.).
- Membuat website, aplikasi, game, bot, atau program selain panduan penggunaan YukSehat.
- Mengerjakan tugas sekolah/kuliah umum, menulis cerita fiksi, puisi, atau konten kreatif non-kesehatan.
- Pertanyaan umum di luar kesehatan dan aplikasi YukSehat (ekonomi, politik, matematika umum, dll.).
- Membocorkan data rahasia/database credential.

Jawaban penolakan WAJIB ringkas:
"Maaf, saya hanya membantu terkait penggunaan YukSehat dan informasi kesehatan yang relevan."
`;
}

const CHATBOT_SYSTEM_INSTRUCTION = buildChatbotSystemInstruction(null);

// System Instruction untuk Analisis Klinis Petugas Kesehatan
const CLINICAL_SYSTEM_INSTRUCTION = `
Kamu adalah Asisten Analisis Klinis untuk Petugas Poskestren (Pos Kesehatan Pesantren) YukSehat.
Tugasmu adalah menganalisis keluhan kesehatan santri dan data vital secara SANGAT RINGKAS, PADAT, dan TO THE POINT (tanpa basa-basi atau penjelasan bertele-tele).

PRINSIP KETAT:
1. Hasil analisismu adalah DRAF PEMBANTU UNTUK DITINJAU PETUGAS, BUKAN diagnosis medis final.
2. JANGAN meresepkan obat keras atau obat antibiotik otomatis. Hanya saran terapi suportif/P3K lazim.
3. JANGAN bertele-tele. Setiap bagian WAJIB MAKSIMAL 1-2 POIN SINGKAT.
4. Jangan pernah meminta atau mencatat nama atau identitas langsung santri.

FORMAT OUTPUT WAJIB (Maksimal 1-2 butir poin singkat per bagian):
### 1. Ringkasan Keluhan & Dugaan Awal
- [1-2 kemungkinan penyebab/kondisi utama secara singkat]

### 2. Informasi yang Perlu Diklarifikasi
- [1-2 pertanyaan penting untuk ditanyakan ke santri]

### 3. Hal yang Perlu Diperhatikan / Tanda Bahaya (Red Flags)
- [1-2 gejala waspada/indikator rujukan jika kondisi memburuk]

### 4. Saran Tindak Lanjut Umum & Perawatan di Poskestren
- [1-2 langkah perawatan/istirahat/P3K praktis]
`;

function setCorsHeaders(res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
}

function parseBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString();
      if (body.length > 1e6) {
        req.destroy();
        reject(new Error('Payload too large'));
      }
    });
    req.on('end', () => {
      try {
        resolve(body ? JSON.parse(body) : {});
      } catch (err) {
        reject(new Error('Invalid JSON'));
      }
    });
    req.on('error', reject);
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// Pre-screening: Deteksi permintaan yang JELAS di luar topik YukSehat
// Digunakan sebelum memanggil Gemini untuk menghemat token & latensi.
// Hanya menangkap kasus yang benar-benar jelas; kasus abu-abu tetap diteruskan
// ke Gemini agar system prompt-nya yang menangani dengan presisi lebih tinggi.
// ─────────────────────────────────────────────────────────────────────────────
const OFF_TOPIC_PATTERNS = [
  // Permintaan coding eksplisit
  /\b(buat(?:kan)?|tulis(?:kan)?|buatkan|bikin|coding|kode|skrip|script|program|fungsi|function|class|algoritma|algorithm|snippet|debug|debugg(?:ing)?)\b.{0,60}\b(python|javascript|js|php|java|c\+\+|c#|ruby|go|rust|typescript|ts|html|css|sql|bash|shell|kotlin|swift)\b/i,
  /\b(python|javascript|js|php|java|c\+\+|c#|ruby|go|rust|typescript|ts|bash|kotlin|swift)\b.{0,60}\b(buat(?:kan)?|tulis(?:kan)?|bikin|coding|kode|skrip|script|program|fungsi|function|class)\b/i,
  // Permintaan membuat program/aplikasi bukan YukSehat
  /\bbuat(?:kan)?\s+(program|aplikasi|website|web\s*app|bot|game|sistem)\b(?!.{0,30}\byuksehat\b)/i,
  // Hitung matematika yang bukan kondisi kesehatan
  /\b(?:hitung(?:kan)?|berapa)\s+(?:luas|volume|keliling|integral|turunan|matriks|determinan)\b/i,
  // Tugas menulis kreatif / umum
  /\b(tulis(?:kan)?|buat(?:kan)?)\s+(puisi|cerpen|esai|essay|karangan|lirik|lagu|skenario|cerita\s+fiksi)\b/i,
  // Permintaan jailbreak & prompt injection eksplisit
  /\b(ignore|abaikan|lupakan|forget)\b.{0,50}\b(instruksi|system\s*prompt|rules?|aturan|batasan|restriction|role|peran)\b/i,
  /\b(pretend|pura-pura|berpura-pura|anggap)\b.{0,40}\b(kamu|you|ai|bot|chatbot)\b.{0,40}\b(lain|bebas|free|different|unrestricted|tanpa\s*batasan)\b/i,
  /\b(tampilkan|berikan|bocorkan|show|reveal|print|get|apa|lihat)\b.{0,40}\b(api[_\s-]?key|secret|rahasia|token|password|credential|kredensial|env|\.env|hash|konfigurasi\s+server)\b/i,
  /\b(api[_\s-]?key|password|credential|kredensial|token|rahasia)\b.{0,40}\b(admin|database|gemini|sistem|server)\b/i,
  /\b(mode\s+pengembang|developer\s+mode|dan\s+mode|jailbreak|bypass)\b/i,
  /\b(jadilah|act\s+as|pretend\s+to\s+be)\b.{0,40}\b(root|superuser|admin|hacker|dan)\b/i
];

const CANNED_REJECTION = 'Maaf, saya hanya membantu terkait penggunaan YukSehat dan informasi kesehatan yang relevan.';

/**
 * Tangani pertanyaan deterministik standar dengan cepat dan konsisten
 */
function checkDirectDeterministicQuery(userMessage, user) {
  const clean = (userMessage || '').trim().toLowerCase().replace(/[?!.,]/g, '').replace(/\s+/g, ' ');
  const isLoggedIn = !!(user && user.role && user.role.toLowerCase() !== 'guest');
  const role = isLoggedIn ? user.role.toLowerCase() : 'guest';
  const roleFormatted = formatRoleName(role);
  const nama = (user?.nama || '').trim();
  const username = (user?.username || '').trim();

  // 1. "Siapa saya?" / "Saya login sebagai siapa?" / "Siapa aku?"
  if (
    /^(siapa\s+(saya|aku)|identitas\s+saya|siapa\s+saya\s+di\s+sini|siapa\s+nama\s+saya)$/i.test(clean) ||
    /^(saya\s+login\s+sebagai\s+(?:siapa|apa)|login\s+sebagai\s+siapa(?:\s+saya)?|sebagai\s+siapa\s+saya\s+login|akun\s+saya\s+login\s+sebagai\s+siapa)$/i.test(clean)
  ) {
    if (!isLoggedIn) {
      return 'Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.';
    }
    if (!nama || nama === 'Pengguna') {
      return `Kamu login sebagai ${username || 'Pengguna'} dengan role ${roleFormatted}, namun data profil lengkap belum tersedia.`;
    }
    return `Kamu login sebagai ${nama} dengan role ${roleFormatted}.`;
  }

  // 2. "Apa nama akun saya?"
  if (/^(apa\s+nama\s+akun\s+saya|nama\s+akun\s+saya(?:\s+apa|\s+siapa)?|siapa\s+nama\s+akun\s+saya|apa\s+nama\s+saya|nama\s+saya\s+siapa)$/i.test(clean)) {
    if (!isLoggedIn) {
      return 'Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.';
    }
    if (!nama || nama === 'Pengguna') {
      return `Akun kamu terdaftar dengan username ${username || 'Pengguna'}, namun data profil lengkap belum tersedia.`;
    }
    if (username && username.toLowerCase() !== nama.toLowerCase()) {
      return `Nama akun kamu adalah ${nama} (username: ${username}).`;
    }
    return `Nama akun kamu adalah ${nama}.`;
  }

  // 3. "Apa role saya?" / "Apa peran saya?"
  if (/^(apa\s+(?:role|peran)\s+saya|(?:role|peran)\s+saya\s+apa|apa\s+(?:role|peran)\s+akun\s+saya|(?:role|peran)\s+akun\s+saya\s+apa)$/i.test(clean)) {
    if (!isLoggedIn) {
      return 'Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.';
    }
    return `Kamu login dengan role ${roleFormatted} di YukSehat.`;
  }

  // 4. "Siapa Anda?"
  if (/^(siapa\s+(anda|kamu|yuksehat\s*ai)|kamu\s+siapa|anda\s+siapa)$/i.test(clean)) {
    return 'Saya YukSehat AI, asisten digital untuk membantu penggunaan YukSehat dan memberikan informasi kesehatan umum.';
  }

  // 5. "Apa itu PHBS?"
  if (/^(apa\s+itu\s+phbs|pengertian\s+phbs|definisi\s+phbs|jelaskan\s+phbs)$/i.test(clean)) {
    return 'PHBS adalah Pola Hidup Bersih dan Sehat, seperti mencuci tangan dengan sabun, menjaga kebersihan kamar, dan menggunakan air bersih.';
  }

  // 6. "Apa saja yang bisa saya lakukan di YukSehat?" / kewenangan / hak akses
  if (
    /^(apa\s+(?:saja\s+)?yang\s+(?:bisa|dapat)\s+saya\s+lakukan(?:\s+di\s+yuksehat|\s+di\s+akun\s+ini)?|kewenangan\s+(?:akun\s+)?saya|hak\s+akses\s+(?:akun\s+)?saya|apa\s+saja\s+fitur\s+(?:akun\s+)?saya|fitur\s+apa\s+saja\s+yang\s+bisa\s+saya\s+gunakan)$/i.test(clean) ||
    /apa\s+saja\s+yang\s+bisa\s+saya\s+lakukan/i.test(clean)
  ) {
    if (role === 'admin') {
      return `Sebagai Admin di YukSehat, kamu dapat:
1. Memantau dashboard dan statistik keseluruhan sistem kesehatan.
2. Mengelola akun pengguna dan pembagian role (Admin, Pengurus, Santri).
3. Mengelola master data santri (tambah, edit, dan nonaktifkan akun).
4. Memantau antrean keluhan santri dan mencatat pemeriksaan kesehatan (termasuk draf klinis AI).
5. Mengakses laporan rekapitulasi kesehatan dan mengekspor data ke Excel (.xlsx).
6. Memantau audit log dan pengaturan sistem.`;
    } else if (role === 'pengurus') {
      return `Sebagai Pengurus di YukSehat, kamu dapat:
1. Melihat dan mengelola data santri sesuai izin Poskestren.
2. Mengelola antrean keluhan santri yang menunggu pemeriksaan.
3. Mencatat pemeriksaan kesehatan santri, tindakan medis, serta memanfaatkan saran analisis AI.
4. Melihat rekapitulasi pemeriksaan kesehatan santri.
5. Mengakses dan mengekspor laporan pemeriksaan ke Excel (.xlsx).
Catatan: Pengurus tidak berwenang mengelola akun pengguna, mengubah role, atau mengubah pengaturan sistem khusus Admin.`;
    } else if (role === 'santri') {
      return `Sebagai Santri di YukSehat, kamu dapat:
1. Mengirim keluhan kesehatan saat membutuhkan penanganan Poskestren.
2. Memantau status antrean keluhan kesehatan milikmu sendiri.
3. Melihat informasi dan riwayat pemeriksaan kesehatan pribadi di Dashboard Santri.
4. Mengakses edukasi Pola Hidup Bersih dan Sehat (PHBS) serta berkonsultasi dengan YukSehat AI.
Catatan: Demi menjaga kerahasiaan medis, kamu tidak dapat melihat data pribadi atau rekam medis santri lain.`;
    } else {
      return 'Kamu belum login, jadi saya belum dapat memastikan identitas akunmu. Silakan login sebagai Admin atau Pengurus untuk mengelola data dan pemeriksaan, atau sebagai Santri untuk mengirim keluhan dan melihat riwayat kesehatan pribadi.';
    }
  }

  // 5. "Mengapa saya tidak bisa membuka menu ini?" / akses ditolak
  if (
    /^(mengapa|kenapa)\s+(?:saya\s+)?tidak\s+bisa\s+(?:membuka|buka|akses)\s+menu/i.test(clean) ||
    /kenapa\s+menu\s+(?:ini\s+)?tidak\s+bisa\s+dibuka/i.test(clean) ||
    /akses\s+ditolak\s+di\s+menu/i.test(clean)
  ) {
    return 'Akses menu di YukSehat dibatasi oleh peran akun Anda. Menu Data Santri, Input Pemeriksaan, dan Laporan hanya dapat diakses oleh role Admin dan Pengurus. Role Santri hanya memiliki akses ke Dashboard Santri dan Riwayat Kesehatan pribadi. Jika Anda pengguna tamu, silakan login terlebih dahulu.';
  }

  // 6. "Apa fungsi tombol Gunakan Saran?"
  if (
    /(?:apa\s+)?fungsi\s+(?:tombol\s+)?gunakan\s+saran/i.test(clean) ||
    /tombol\s+gunakan\s+saran(?:\s+fungsinya\s+apa|\s+untuk\s+apa)?/i.test(clean)
  ) {
    return "Tombol 'Gunakan Saran ke Formulir' berfungsi menyalin otomatis hasil analisis klinis AI (dugaan diagnosa, rekomendasi tindakan, obat, dan catatan pemantauan) langsung ke kolom formulir pemeriksaan, agar pemeriksa dapat meninjau dan mengeditnya sebelum disimpan.";
  }

  // 7. "Bagaimana cara menambahkan data santri?"
  if (/(?:bagaimana\s+)?cara\s+(?:menambah(?:kan)?|tambah|input)\s+(?:data\s+)?santri/i.test(clean)) {
    return "Langkah menambahkan santri: buka menu Data Santri -> klik tombol 'Tambah Santri' -> lengkapi formulir (NIS, nama, jenis kelamin, kelas, kamar, kontak, dan riwayat kesehatan) -> klik 'Simpan Data'. Akun login santri otomatis dibuat dengan NIS sebagai username dan kata sandi default.";
  }

  // 8. "Bagaimana cara melakukan pemeriksaan?"
  if (
    /(?:bagaimana\s+)?cara\s+(?:melakukan|input|tambah|buat)\s+pemeriksaan/i.test(clean) ||
    /alur\s+pemeriksaan/i.test(clean)
  ) {
    return "Langkah pemeriksaan: buka menu Pemeriksaan -> klik tombol 'Tambah Pemeriksaan' -> pilih santri dan isi keluhan -> klik 'Analisis Keluhan AI' jika ingin draf saran -> lengkapi tanda vital, diagnosa, tindakan, dan obat -> klik 'Simpan Pemeriksaan'.";
  }

  // 9. "Bagaimana cara melihat laporan bulanan?"
  if (
    /(?:bagaimana\s+)?cara\s+(?:melihat|lihat|buka)\s+laporan(?:\s+bulanan)?/i.test(clean) ||
    /cara\s+lihat\s+rekapitulasi/i.test(clean)
  ) {
    return "Langkah melihat laporan bulanan: buka menu Laporan -> tentukan rentang tanggal (default awal hingga akhir bulan berjalan) -> pilih filter kelas atau kamar jika diperlukan -> klik 'Terapkan Filter'. Rekapitulasi pemeriksaan periode tersebut akan tampil pada tabel.";
  }

  // 10. "Bagaimana cara mengekspor laporan ke Excel?"
  if (
    /(?:bagaimana\s+)?cara\s+(?:ekspor|mengekspor|export|mengunduh|unduh|download|cetak)\s+laporan(?:\s+ke\s+excel)?/i.test(clean) ||
    /cara\s+(?:ekspor|mengekspor|export|download|unduh)\s+excel/i.test(clean)
  ) {
    return "Langkah ekspor Excel: buka menu Laporan -> atur filter periode dan kriteria yang diinginkan -> klik tombol hijau 'Ekspor Excel'. Berkas .xlsx otomatis terunduh berisi 2 sheet: Ringkasan Laporan dan Detail Catatan Klinis Lengkap.";
  }

  // 11. "Apa perbedaan peran admin dan pengurus?"
  if (
    /perbedaan\s+(?:peran\s+|role\s+)?admin\s+dan\s+pengurus/i.test(clean) ||
    /beda\s+admin\s+(?:sama|dan|dengan)\s+pengurus/i.test(clean)
  ) {
    return 'Admin dan Pengurus sama-sama berwenang mengelola Data Santri, Pemeriksaan (termasuk analisis AI), dan Laporan (termasuk ekspor Excel). Perbedaannya: Admin bertindak sebagai Super User sistem pengelola akun, sedangkan Pengurus adalah petugas/pemeriksa operasional Poskestren yang tercatat sebagai penanggung jawab pemeriksaan.';
  }

  // 12. "Bagaimana cara menonaktifkan santri?"
  if (/(?:bagaimana\s+)?cara\s+menonaktifkan\s+santri/i.test(clean)) {
    return "Untuk menonaktifkan santri: buka menu Data Santri -> cari santri yang dituju -> klik tombol Nonaktifkan (ikon hapus) -> konfirmasi tindakan. Status akun santri akan diubah menjadi tidak aktif tanpa menghapus riwayat pemeriksaan sebelumnya.";
  }

  return null;
}

/**
 * Validasi dan pembersihan respons:
 * - Menghilangkan salam berulang dan basa-basi pembuka jika ada
 * - Menghilangkan penutup bertele-tele yang tidak diperlukan
 * - Menjamin tidak ada iklan komersial atau promosi eksternal
 */
function validateAndCleanResponse(text) {
  if (!text) return '';
  let cleaned = text.trim();

  // Bersihkan salam pembuka berulang yang berlebihan di awal agar langsung menjawab inti pertanyaan
  cleaned = cleaned.replace(/^(Assalamu'alaikum(?:\s+Warahmatullahi\s+Wabarakatuh)?|Halo|Hai|Selamat\s+(pagi|siang|sore|malam))[\s,!.-]+/i, '');

  // Bersihkan frasa basa-basi pembuka
  cleaned = cleaned.replace(/^(Tentu(?:\s+saja)?|Baik(?:\s+saya\s+akan\s+bantu)?|Terima\s+kasih\s+atas\s+pertanyaannya)[\s,!.-]+/i, '');

  // Bersihkan pengulangan pertanyaan pengguna di awal
  cleaned = cleaned.replace(/^Mengenai\s+pertanyaan\s+Anda\s+tentang\s+[^:,\n]+[:,\n]\s*/i, '');

  // Pastikan tidak ada tautan promosi atau iklan komersial eksternal
  cleaned = cleaned.replace(/https?:\/\/(?:www\.)?(?:shopee|tokopedia|lazada|amazon|bukalapak|tiktok|instagram|facebook)\S*/gi, '');

  // Bersihkan penutup basa-basi yang tidak diperlukan di akhir balasan
  cleaned = cleaned.replace(/\n*(?:Semoga\s+membantu|Semoga\s+lekas\s+sembuh|Ada\s+hal\s+lain\s+yang\s+bisa\s+saya\s+bantu\?|Jangan\s+ragu\s+untuk\s+bertanya\s+lagi)[.!]*\s*$/i, '');

  return cleaned.trim() || text.trim();
}


/**
 * Mengembalikan true jika pesan JELAS di luar topik YukSehat/kesehatan santri.
 * False = tidak pasti / relevan → teruskan ke Gemini.
 */
function isOffTopic(message) {
  const msg = (message || '').toLowerCase();
  return OFF_TOPIC_PATTERNS.some(pattern => pattern.test(msg));
}

/**
 * Verifikasi identitas dan peran pengguna dari sumber tepercaya:
 * 1. Jika berasal dari PHP Backend internal, periksa header X-Internal-Secret.
 * 2. Jika berasal dari browser/client, wajib verifikasi HMAC signature & timestamp (anti-replay).
 * 3. Jika tidak valid, jangan pernah mempercayai role dari browser (perlakukan sebagai Guest).
 */
function verifyUserContext(req, bodyUser) {
  if (!bodyUser || typeof bodyUser !== 'object') {
    return null;
  }

  // Sumber 1: Panggilan server-ke-server terpercaya dari PHP Backend
  const incomingSecret = req.headers['x-internal-secret'];
  if (incomingSecret && incomingSecret === INTERNAL_API_SECRET) {
    if (bodyUser.nama && bodyUser.role) {
      return {
        id: bodyUser.id ? Number(bodyUser.id) : null,
        nama: String(bodyUser.nama).trim().substring(0, 50),
        username: bodyUser.username ? String(bodyUser.username).trim().substring(0, 50) : null,
        role: String(bodyUser.role).trim().substring(0, 30),
        auth_token: bodyUser.auth_token || null,
        auth_time: bodyUser.auth_time || null
      };
    }
    return null;
  }

  // Sumber 2: Panggilan langsung dari client/browser
  // JANGAN PERCAYA ROLE DARI BROWSER TANPA HMAC SIGNATURE VALID & NON-EXPIRED
  if (bodyUser.id && bodyUser.role && bodyUser.auth_token && bodyUser.auth_time) {
    const authTime = Number(bodyUser.auth_time);
    const now = Math.floor(Date.now() / 1000);

    // Replay protection window: 900 detik (15 menit)
    if (!authTime || Math.abs(now - authTime) > 900) {
      console.warn(`[Node Auth] Token kedaluwarsa untuk user id: ${bodyUser.id}`);
      return null;
    }

    const payload = `${bodyUser.id}:${bodyUser.role}:${authTime}`;
    const expectedToken = crypto.createHmac('sha256', INTERNAL_API_SECRET).update(payload).digest('hex');

    const expectedBuf = Buffer.from(expectedToken, 'utf8');
    const actualBuf = Buffer.from(String(bodyUser.auth_token), 'utf8');

    if (expectedBuf.length === actualBuf.length && crypto.timingSafeEqual(expectedBuf, actualBuf)) {
      return {
        id: Number(bodyUser.id),
        nama: String(bodyUser.nama || '').trim().substring(0, 50),
        username: bodyUser.username ? String(bodyUser.username).trim().substring(0, 50) : null,
        role: String(bodyUser.role).trim().substring(0, 30),
        auth_token: String(bodyUser.auth_token),
        auth_time: authTime
      };
    } else {
      console.warn(`[Node Auth] Invalid HMAC signature untuk user id: ${bodyUser.id}`);
      return null;
    }
  }

  // Jika tanpa token atau token tidak valid, perlakukan sebagai Guest (null)
  return null;
}

/**
 * Deteksi apakah pengguna menanyakan data aktual dari database YukSehat
 */
function isDataQuery(message, user = null) {
  const clean = (message || '').toLowerCase().replace(/[?!.,]/g, ' ').replace(/\s+/g, ' ').trim();
  const isAdmin = !!(user && user.role && user.role.toLowerCase() === 'admin');

  const patterns = [
    // 1. Konsultasi / pemeriksaan hari ini (santri unik vs total pemeriksaan)
    /(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?(?:berkonsultasi|konsultasi|periksa|diperiksa)/i,
    /(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i,
    /(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:yang\s+)?periksa/i,
    /(?:santri\s+)?(?:berkonsultasi|konsultasi)\s+hari\s+ini/i,
    /santri\s+(?:yang\s+)?periksa\s+hari\s+ini/i,
    /(?:siapa|daftar)\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i,
    /(?:ada\s+)?(?:yang\s+)?periksa\s+hari\s+ini/i,
    /pemeriksaan\s+hari\s+ini/i,
    /pemeriksaan\s+minggu\s+ini/i,
    /(?:ada\s+)?berapa\s+pemeriksaan\s+hari\s+ini/i,
    /(?:ada\s+)?berapa\s+pemeriksaan\s+minggu\s+ini/i,
    /berapa\s+(?:yang\s+)?periksa\s+minggu\s+ini/i,
    /berapa\s+(?:yang\s+)?periksa\s+bulan\s+ini/i,

    // 2. Kelas / kamar dengan santri terbanyak
    /kelas\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i,
    /santri\s+terbanyak\s+(?:ada\s+)?di\s+kelas/i,
    /kamar\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i,
    /santri\s+terbanyak\s+(?:ada\s+)?di\s+kamar/i,

    // 3. Kelengkapan data santri
    /(?:data\s+santri\s+)?(?:apa\s+(?:saja\s+)?)?(?:data\s+santri\s+)?(?:yang\s+)?(?:belum|tidak)\s+lengkap/i,
    /data\s+(?:santri\s+)?(?:yang\s+)?belum\s+lengkap/i,
    /kelengkapan\s+data\s+santri/i,
    /(?:cek|periksa)\s+kelengkapan\s+data/i,
    /santri\s+(?:data\s+)?(?:yang\s+)?belum\s+lengkap/i,

    // 4. Keluhan menunggu / antrean / santri keluhan hari ini
    /(?:keluhan|antrean|antrian)\s+(?:santri\s+)?(?:yang\s+)?(?:masih\s+)?(?:menunggu|belum\s+diperiksa)/i,
    /status\s+(?:antrean|antrian|keluhan)/i,
    /(?:ada\s+)?berapa\s+keluhan(?:\s+yang)?(?:\s+masih)?\s+menunggu/i,
    /(?:santri\s+unik\s+)?(?:yang\s+)?(?:menyampaikan\s+)?keluhan\s+hari\s+ini/i,
    /(?:ada\s+)?berapa\s+santri(?:\s+unik)?(?:\s+yang)?\s+(?:menyampaikan|mengirim|punya)\s+keluhan\s+hari\s+ini/i,
    /keluhan\s+hari\s+ini/i,

    // 5. Total / status santri
    /(?:ada\s+)?berapa\s+(?:jumlah\s+|total\s+)?santri/i,
    /total\s+santri/i,
    /jumlah\s+santri/i,
    /santri\s+terdaftar/i,
    /santri\s+(?:yang\s+)?aktif/i,
    /santri\s+(?:nonaktif|tidak\s+aktif)/i,
    // Daftar nama santri / siapa saja santri
    /(?:siapa|daftar|sebutkan|tampilkan)\s+(?:saja\s+|aja\s+)?(?:nama(?:nya)?|santri(?:nya)?|orang(?:nya)?)/i,
    /(?:nama(?:nya)?|santri(?:nya)?)\s+siapa\s+(?:saja|aja)/i,
    /daftar\s+(?:nama\s+)?santri/i,
    /nama(?:\s*-\s*nama|\s+nama)?\s+santri/i,
    /siapa\s+(?:saja\s+|aja\s+)?(?:santri\s+)?(?:yang\s+)?terdaftar/i,
    /siapa\s+santrinya/i,
    /siapa\s+namanya/i,
    /santri\s+(?:laki-laki|perempuan|putra|putri|gender|jenis\s+kelamin)/i,
    /berapa\s+(?:santri\s+)?(?:laki-laki|perempuan|putra|putri)/i,
    /santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?alergi/i,
    /santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?riwayat\s+penyakit/i,
    /daftar\s+(?:alergi|riwayat\s+penyakit)/i,

    // 6. Santri per kelas / kamar
    /santri\s+(?:berdasarkan|per)\s+kelas/i,
    /santri\s+(?:di\s+)?(?:setiap|tiap)\s+kelas/i,
    /santri\s+kelas\s+[a-z0-9\s-]+/i,
    /kelas\s+[a-z0-9\s-]+\s+(?:ada\s+)?berapa/i,
    /santri\s+(?:berdasarkan|per)\s+kamar/i,
    /santri\s+(?:di\s+)?(?:setiap|tiap)\s+kamar/i,
    /santri\s+kamar\s+[a-z0-9\s-]+/i,
    /rekap\s+(?:data\s+)?santri/i,
    /statistik\s+(?:data\s+)?santri/i,
    /rekapitulasi\s+santri/i,

    // 7. Pemeriksaan bulan ini / terakhir / total / rawat inap / rujukan
    /(?:ada\s+)?berapa\s+pemeriksaan\s+bulan\s+ini/i,
    /pemeriksaan\s+(?:kesehatan\s+)?bulan\s+ini/i,
    /kapan\s+pemeriksaan\s+(?:kesehatan\s+)?terakhir/i,
    /pemeriksaan\s+(?:kesehatan\s+)?terakhir/i,
    /tanggal\s+pemeriksaan\s+terakhir/i,
    /rekap\s+(?:data\s+)?pemeriksaan/i,
    /ringkasan\s+pemeriksaan/i,
    /statistik\s+(?:data\s+)?pemeriksaan/i,
    /rekapitulasi\s+pemeriksaan/i,
    /total\s+pemeriksaan/i,
    /jumlah\s+(?:seluruh\s+)?pemeriksaan/i,
    /(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?(?:selesai\s+)?(?:di)?catat/i,
    /(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?selesai/i,
    /santri\s+(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks)/i,
    /santri\s+(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i,
    /(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i,
    /(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks|sedang\s+di\s+uks)/i,
    /(?:siapa|daftar|sebutkan)\s+(?:saja\s+)?(?:nama\s+)?(?:santri\s+)?(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i,
    /(?:siapa|daftar|sebutkan)\s+(?:saja\s+)?(?:nama\s+)?(?:santri\s+)?(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks)/i,
    /rujukan(?:\s+ada|\s+berapa|\s+siapa)/i,
    /berapa\s+santri\s+(?:sakit|perlu\s+rujukan|rawat\s+inap)/i,
    /siapa\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?sakit/i,

    // 8. Pengurus & Users
    /(?:daftar|siapa|berapa)\s+(?:saja\s+)?(?:pengurus|petugas\s+poskestren)/i,
    /(?:daftar|berapa|total)\s+(?:user|users|pengguna|akun)/i,

    // 9. Obat
    /(?:daftar|apa\s+saja)\s+obat/i,
    /obat\s+(?:yang\s+)?(?:pernah\s+)?(?:diberikan|digunakan|dipakai)/i,

    // 10. Database summary / rekap database
    /(?:baca|rekap|ringkasan|seluruh|status|isi)\s+database/i,
    /database\s+(?:yuksehat|aplikasi)/i,

    // 11. Pencarian santri yang diizinkan (directory lookup)
    /cari\s+santri\s+[a-z0-9]/i,
    /informasi\s+santri\s+[a-z0-9]/i,
    /profil\s+santri\s+[a-z0-9]/i,

    // 12. Rekam medis santri spesifik (kerahasiaan medis individu)
    /(?:rekam\s+medis|riwayat\s+(?:penyakit|medis|kesehatan|pemeriksaan)|catatan\s+medis|data\s+medis|keluhan|diagnosa)\s+/i,
    /(?:apa\s+(?:sakit|penyakit|keluhan|diagnosa|obat)|resep\s+obat|riwayat\s+sakit)\s+/i,
    /\bobat\s+(?:untuk\s+|si\s+)?([a-z0-9]+)/i,
    /(?:rekam\s+medis|riwayat\s+penyakit|diagnosa)\s+(?:santri|anak|pasien|individu)/i
  ];

  if (patterns.some(p => p.test(clean))) {
    return true;
  }

  if (isAdmin) {
    if (
      /\b(santri|pemeriksaan|periksa|keluhan|pengurus|users?|pengguna|akun|obat|diagnosa|penyakit|alergi|kamar|kelas|database|data|rekam\s*medis|nama(?:nya)?|orang(?:nya)?|rujuk(?:an)?|uks|rawat)\b/i.test(clean) &&
      /\b(berapa|siapa|apa|mana|kapan|daftar|tampilkan|cek|rekap|ringkasan|status|lihat|cari|ada|sebutkan|kalo)\b/i.test(clean)
    ) {
      return true;
    }
  }

  return false;
}

/**
 * Panggil endpoint internal backend PHP dengan autentikasi rahasia server-ke-server
 */
async function fetchPhpInternalQuery(userMessage, user) {
  try {
    const payload = JSON.stringify({
      message: userMessage,
      user: user
    });

    const response = await fetch(`${PHP_SERVER_URL}/chatbot/internal_query`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Internal-Secret': INTERNAL_API_SECRET
      },
      body: payload,
      signal: AbortSignal.timeout(6000)
    });

    if (response.ok) {
      const data = await response.json();
      if (data && data.success && data.reply) {
        return data.reply;
      }
    } else {
      const errData = await response.json().catch(() => ({}));
      if (errData && errData.error) {
        return errData.error;
      }
      console.warn(`[Node->PHP Query] Server internal PHP merespons status ${response.status}`);
    }
  } catch (err) {
    console.error(`[Node->PHP Query Exception]`, err.message);
  }
  return 'Maaf, data santri belum dapat diakses saat ini. Silakan coba lagi nanti.';
}

// Panggil model dengan fallback otomatis ke candidate models
async function callGeminiWithFallback(contents, systemPrompt = CHATBOT_SYSTEM_INSTRUCTION) {
  const config = getEffectiveAIConfig();

  // Jika dikonfigurasi menggunakan NVIDIA NIM
  if (config.provider === 'nvidia') {
    const nvidiaUrl = config.endpoint || 'https://integrate.api.nvidia.com/v1/chat/completions';
    const nvidiaModels = [config.model, 'nvidia/nemotron-3.5-bolt-e4b', 'poolside/laguna-xs-2.1'];
    const uniqueModels = [...new Set(nvidiaModels)];

    const msgs = [{ role: 'system', content: systemPrompt }];
    for (const c of contents) {
      const role = (c.role === 'model' || c.role === 'assistant') ? 'assistant' : 'user';
      const text = c.parts?.map(p => p.text).join('') || '';
      if (text) msgs.push({ role, content: text });
    }

    for (const m of uniqueModels) {
      try {
        console.log(`[NVIDIA NIM] Mencoba model: ${m}...`);
        const res = await fetch(nvidiaUrl, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${config.apiKey}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            model: m,
            messages: msgs,
            max_tokens: 800,
            temperature: 0.6
          }),
          signal: AbortSignal.timeout(25000)
        });

        if (res.ok) {
          const data = await res.json();
          const text = data?.choices?.[0]?.message?.content;
          if (text) return { text, model: m };
        }
      } catch (e) {
        console.warn(`[NVIDIA NIM] Model ${m} kendala:`, e.message);
      }
    }
  }

  const payload = {
    contents: contents,
    systemInstruction: {
      parts: [{ text: systemPrompt }]
    },
    generationConfig: {
      temperature: 0.7,
      topP: 0.95,
      maxOutputTokens: 2048
    }
  };

  let lastError = null;

  for (const model of CANDIDATE_MODELS) {
    const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent`;
    const start = Date.now();

    try {
      console.log(`[Gemini API] Mencoba model: ${model}...`);
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'x-goog-api-key': GEMINI_API_KEY
        },
        body: JSON.stringify(payload),
        signal: AbortSignal.timeout(15000)
      });

      const elapsed = Date.now() - start;
      console.log(`[Gemini API] Model ${model} -> HTTP ${response.status} (${elapsed}ms)`);

      if (response.ok) {
        const data = await response.json();
        const text = data?.candidates?.[0]?.content?.parts?.[0]?.text;
        if (text) {
          return { text, model };
        }
      } else {
        const errData = await response.json().catch(() => ({}));
        const errMsg = errData?.error?.message || `HTTP ${response.status}`;
        console.warn(`[Gemini API] Model ${model} gagal (${errMsg}), beralih ke model berikutnya...`);
        lastError = new Error(errMsg);
      }
    } catch (err) {
      console.warn(`[Gemini API] Model ${model} exception (${err.message}), beralih ke model berikutnya...`);
      lastError = err;
    }
  }

  throw lastError || new Error('Semua model Gemini AI sedang sibuk');
}

const server = http.createServer(async (req, res) => {
  setCorsHeaders(res);

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);

  // Health check
  if (req.method === 'GET' && (url.pathname === '/' || url.pathname === '/health' || url.pathname === '/api/status')) {
    res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
    res.end(JSON.stringify({
      status: 'online',
      service: 'YukSehat Chatbot & Clinical AI Server',
      primaryModel: CANDIDATE_MODELS[0],
      models: CANDIDATE_MODELS,
      timestamp: new Date().toISOString()
    }));
    return;
  }

  // 1. Endpoint Chatbot Umum (/api/chat)
  if (req.method === 'POST' && (url.pathname === '/api/chat' || url.pathname === '/chat')) {
    try {
      const body = await parseBody(req);
      const userMessage = (body.message || '').trim();

      // Verifikasi identitas dan role dari sumber terpercaya (PHP session via secret / HMAC signature)
      const incomingSecret = req.headers['x-internal-secret'];
      const hasValidSecret = incomingSecret && incomingSecret === INTERNAL_API_SECRET;
      const user = verifyUserContext(req, body.user);

      if (!hasValidSecret && !user) {
        res.writeHead(403, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: false, error: 'Akses ditolak: Header X-Internal-Secret atau HMAC signature tidak valid' }));
        return;
      }

      if (!userMessage) {
        res.writeHead(400, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: false, error: 'Pesan tidak boleh kosong' }));
        return;
      }

      // ── 0. Cek query data database aktual (hanya admin/pengurus terautentikasi) ──
      if (isDataQuery(userMessage, user)) {
        console.log(`[Chat] Database data query: "${userMessage.substring(0, 50)}"`);
        const dataReply = await fetchPhpInternalQuery(userMessage, user);
        res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({
          success: true,
          reply: dataReply,
          model: 'database-query'
        }));
        return;
      }

      // ── 1. Cek query deterministik langsung (Siapa saya, Siapa Anda, Apa itu PHBS) ──
      const directAnswer = checkDirectDeterministicQuery(userMessage, user);
      if (directAnswer) {
        res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({
          success: true,
          reply: directAnswer,
          model: 'direct-rule'
        }));
        return;
      }

      // ── 2. Pre-screening: tolak permintaan yang jelas di luar topik ──────────
      if (isOffTopic(userMessage)) {
        console.log(`[Chat] Off-topic pre-screened: "${userMessage.substring(0, 60)}"`);
        res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: true, reply: CANNED_REJECTION, model: 'pre-screen' }));
        return;
      }

      const contents = [];
      if (Array.isArray(body.history)) {
        for (const item of body.history.slice(-8)) {
          if (item && item.role && item.text) {
            contents.push({
              role: item.role === 'model' || item.role === 'assistant' ? 'model' : 'user',
              parts: [{ text: String(item.text) }]
            });
          }
        }
      }

      const lastItem = contents[contents.length - 1];
      if (!lastItem || lastItem.role !== 'user' || lastItem.parts[0].text !== userMessage) {
        contents.push({
          role: 'user',
          parts: [{ text: userMessage }]
        });
      }

      const systemPrompt = buildChatbotSystemInstruction(user);
      console.log(`[Chat Request] Pesan: "${userMessage.substring(0, 50)}..." (User: ${user ? user.nama + '/' + user.role : 'Guest'})`);
      const { text, model } = await callGeminiWithFallback(contents, systemPrompt);

      const cleanedReply = validateAndCleanResponse(text);

      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({
        success: true,
        reply: cleanedReply,
        model: model
      }));
    } catch (error) {
      console.error('[Chat Error]:', error.message);
      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({
        success: true,
        reply: 'Maaf, layanan konsultasi AI saat ini sedang dalam rotasi kunci atau pemeliharaan. Untuk konsultasi kesehatan atau bantuan medis, silakan temui petugas kesehatan di Poskestren.',
        model: 'safe-fallback'
      }));
    }
    return;
  }

  // 2. Endpoint Analisis Keluhan Pemeriksaan Petugas (/api/analyze-keluhan)
  if (req.method === 'POST' && (url.pathname === '/api/analyze-keluhan' || url.pathname === '/analyze-keluhan')) {
    try {
      const incomingSecret = req.headers['x-internal-secret'];
      if (!incomingSecret || incomingSecret !== INTERNAL_API_SECRET) {
        res.writeHead(403, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: false, error: 'Akses ditolak: Endpoint analisis hanya untuk internal server terverifikasi' }));
        return;
      }

      const body = await parseBody(req);
      const keluhan = (body.keluhan || '').trim();
      const vitalSigns = body.vital_signs || {};
      const konteks = (body.konteks || '').trim();

      if (!keluhan) {
        res.writeHead(400, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: false, error: 'Keluhan santri tidak boleh kosong untuk dianalisis.' }));
        return;
      }

      let prompt = `Informasi Klinis Santri:\n- Keluhan Utama: ${keluhan}\n`;
      if (vitalSigns.suhu) prompt += `- Suhu Tubuh: ${vitalSigns.suhu} °C\n`;
      if (vitalSigns.tekanan_darah) prompt += `- Tekanan Darah: ${vitalSigns.tekanan_darah}\n`;
      if (vitalSigns.berat_badan) prompt += `- Berat Badan: ${vitalSigns.berat_badan} kg\n`;
      if (vitalSigns.tinggi_badan) prompt += `- Tinggi Badan: ${vitalSigns.tinggi_badan} cm\n`;
      if (konteks) prompt += `- Konteks Anonim Santri: ${konteks}\n`;
      prompt += `\nMohon susun draf tinjauan klinis awal secara sangat ringkas dan to the point (maksimal 1-2 butir poin per bagian).`;

      console.log(`[Clinical Analysis Request] Keluhan: "${keluhan.substring(0, 50)}..."`);
      const { text, model } = await callGeminiWithFallback(
        [{ parts: [{ text: prompt }] }],
        CLINICAL_SYSTEM_INSTRUCTION
      );

      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({
        success: true,
        analysis: text,
        model: model,
        disclaimer: 'Draf saran awal untuk ditinjau petugas kesehatan. Bukan diagnosis medis final atau resep obat otomatis.'
      }));
    } catch (error) {
      console.error('[Clinical Analysis Error]:', error.message);
      res.writeHead(500, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify({
        success: false,
        error: error.message || 'Gagal memproses analisis keluhan AI'
      }));
    }
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify({ error: 'Endpoint tidak ditemukan' }));
});

server.listen(PORT, HOST, () => {
  console.log(`=================================================`);
  console.log(`🚀 YukSehat Chatbot & Clinical AI Server Aktif!`);
  console.log(`📡 URL: http://${HOST}:${PORT}`);
  console.log(`🤖 Model Prioritas: ${CANDIDATE_MODELS[0]}`);
  console.log(`🛡️ Fallback Models: ${CANDIDATE_MODELS.slice(1).join(', ')}`);
  console.log(`=================================================`);
});
