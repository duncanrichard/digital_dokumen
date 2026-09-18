from pathlib import Path
import math, re, json
from xml.sax.saxutils import escape
from reportlab.pdfgen import canvas
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.platypus import Paragraph, Table, TableStyle, Flowable
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output/pdf/Manual_Book_Sistem_Dokumen.pdf'
OUT.parent.mkdir(parents=True, exist_ok=True)
FONT = Path('C:/Windows/Fonts')
for name, filename in [('Body','arial.ttf'),('Bold','arialbd.ttf'),('Italic','ariali.ttf')]:
    pdfmetrics.registerFont(TTFont(name,str(FONT/filename)))
pdfmetrics.registerFontFamily('Body',normal='Body',bold='Bold',italic='Italic',boldItalic='Bold')
W,H=A4; M=44; CW=W-2*M
NAVY=colors.HexColor('#173E59'); BLUE=colors.HexColor('#346DA5'); TEAL=colors.HexColor('#187E87')
INK=colors.HexColor('#233344'); MUTED=colors.HexColor('#617182'); LINE=colors.HexColor('#D8E2EB')
PALE=colors.HexColor('#F2F6FA'); GOLD=colors.HexColor('#D9AC55')
styles={
 'body':ParagraphStyle('body',fontName='Body',fontSize=10,leading=14.5,textColor=INK,spaceAfter=7),
 'small':ParagraphStyle('small',fontName='Body',fontSize=8.5,leading=12,textColor=MUTED),
 'h2':ParagraphStyle('h2',fontName='Bold',fontSize=12,leading=17,textColor=BLUE),
 'cell':ParagraphStyle('cell',fontName='Body',fontSize=9,leading=12.7,textColor=INK),
 'head':ParagraphStyle('head',fontName='Bold',fontSize=9,leading=12,textColor=colors.white),
 'box':ParagraphStyle('box',fontName='Body',fontSize=9.2,leading=13.4,textColor=INK),
 'node':ParagraphStyle('node',fontName='Body',fontSize=8.5,leading=11.5,textColor=INK,alignment=1),
}
pages=[]
def page(title,subtitle='',section='PANDUAN PENGGUNA'):
    p={'title':title,'subtitle':subtitle,'section':section,'blocks':[]}; pages.append(p); return p
def para(p,s):p['blocks'].append(('p',s))
def sub(p,s):p['blocks'].append(('h',s))
def steps(p,items):
    for i,s in enumerate(items,1):p['blocks'].append(('step',(i,s)))
def bullets(p,items):
    for s in items:p['blocks'].append(('bullet',s))
def table(p,heads,rows,widths=None):p['blocks'].append(('table',(heads,rows,widths)))
def note(p,title,s,kind='info'):p['blocks'].append(('note',(title,s,kind)))
def flow(p,caption,lanes,rows,edges=None):p['blocks'].append(('flow',(caption,lanes,rows,edges)))

p=page('Manual Book\nSistem Dokumen','Panduan penggunaan dan alur pengendalian dokumen','EDISI 1.0 / SEPTEMBER 2026')
para(p,'Panduan kerja untuk pengelola dokumen, divisi, cabang/klinik, penerima dokumen, pemberi persetujuan, dan administrator.')
table(p,['CAKUPAN','HASIL YANG DITUJU'],[
 ['Kelola dokumen','Membuat, memperbarui, merevisi, dan menghubungkan dokumen.'],
 ['Distribusi & akses','Menentukan penerima dan memproses permintaan akses tambahan.'],
 ['Administrasi','Mengelola master data, pengguna, permission, dan pengaturan.']],[.31,.69])
note(p,'CARA MEMAKAI BUKU INI','Mulai dari peta menu dan pembagian tugas. Ikuti langkah pada menu yang sesuai, lalu cocokkan hasilnya dengan bagian pemeriksaan akhir. Nama tombol berbahasa Inggris dipertahankan sesuai aplikasi.')
para(p,'Disusun berdasarkan kode dan tampilan proyek <b>dokumen</b> yang ditinjau pada 17 September 2026. Struktur visual mengacu pada manual OneAsset: tabel peran, langkah bernomor, diagram alur, catatan, dan FAQ.')
note(p,'RUANG LINGKUP','Ini adalah panduan berdasarkan implementasi yang diperiksa, bukan berita acara pengujian operasional. Perbedaan konfigurasi server dapat memengaruhi hasil. Catatan verifikasi untuk admin tersedia di halaman 30.','warn')

p=page('Daftar isi','Temukan panduan menurut pekerjaan yang akan dilakukan.','NAVIGASI')
toc=[('Gambaran umum & peta menu',3),('Peran dan hak akses',4),('Alur besar pengendalian dokumen',5),('Login, dashboard & ringkasan',6),('Master jenis dokumen & klinik',7),('Master divisi, cabang & tujuan WhatsApp',8),('Upload dokumen baru',9),('Alur upload & penomoran',10),('Edit metadata, status & hapus',11),('Membuat revisi dokumen',12),('Mengganti menjadi dokumen baru',13),('Membuat turunan klinik',14),('Mengatur distribusi',15),('Alur distribusi & perubahan penerima',16),('Galeri & pencarian AI',17),('Membaca PDF & memahami akses',18),('Mengajukan akses dokumen',19),('Memproses persetujuan akses',20),('Notifikasi aplikasi & WhatsApp',21),('Mengelola pengguna & HRIS',22),('Mengelola role & permission',23),('Pengaturan watermark / DRM',24),('Document Access & Framework System',25),('Contoh penggunaan dari awal sampai akhir',26),('Penanganan masalah',27),('Pertanyaan umum / FAQ',28),('Glosarium & checklist kerja',29),('Lampiran admin: verifikasi & sumber',30)]
table(p,['BAGIAN','HAL.'],[[t,str(n)] for t,n in toc],[.90,.10])

p=page('Gambaran umum & peta menu','Satu tempat untuk mengelola identitas, versi, dan penerima dokumen.','01 / PENGENALAN')
para(p,'Sistem Dokumen menyimpan file PDF beserta jenis, divisi pemilik, tanggal, status, dan catatan. Dokumen dapat memiliki beberapa revisi, hubungan penggantian, serta turunan khusus klinik. Akses baca di galeri ditentukan oleh kepemilikan divisi, distribusi, atau persetujuan.')
table(p,['MENU','KEGUNAAN'],[
 ['Dashboard','Statistik untuk Superadmin; tampilan dokumen bagi pengguna lain sesuai akses.'],
 ['Master Data','Document Types, Divisi, dan Klinik. Siapkan sebelum membuat dokumen.'],
 ['Document Control > Lihat Dokumen','Galeri, pencarian biasa/AI, status akses, dan pembacaan PDF.'],
 ['Document Control > Ringkasan','Ringkasan dokumen aktif, distribusi, permintaan pending, dan dokumen terbaru.'],
 ['Document Control > Kelola Dokumen','Upload, daftar versi, edit metadata, revisi, penggantian, turunan, dan hapus.'],
 ['Document Control > Distribusi','Memilih divisi/cabang dan pengguna penerima per dokumen.'],
 ['Document Control > Persetujuan','Memutuskan permintaan akses dokumen dari pengguna.'],
 ['User Access','Users, Roles, dan Permission Settings.'],
 ['Settings','Watermark/DRM, Document Access, dan Framework System.']],[.43,.57])
sub(p,'Sebelum mulai')
bullets(p,['Siapkan akun aktif dan alamat aplikasi dari administrator.','Pastikan jenis dokumen, divisi, dan klinik yang diperlukan sudah dibuat serta aktif.','Siapkan PDF yang benar dan berukuran maksimal 10 MB.','Tentukan siapa yang membutuhkan akses: satu divisi, beberapa cabang, atau orang tertentu.'])
note(p,'PENTING','Hak melihat menu tidak selalu berarti boleh menambah, mengubah, atau menghapus. Gunakan akun sendiri dan periksa pilihan tindakan yang tersedia.')

p=page('Peran dan hak akses','Pembagian pekerjaan mengikuti permission yang diberikan administrator.','01 / PENGENALAN')
para(p,'<b>Superadmin</b> merupakan nama role khusus di kode aplikasi. Sebutan pengelola, approver, dan pembaca di bawah adalah fungsi kerja untuk memudahkan panduan; nama role operasional dapat berbeda di organisasi Anda.')
table(p,['FUNGSI KERJA','TANGGUNG JAWAB'],[
 ['Superadmin','Mengatur akses dan master data; memiliki pengecualian pemeriksaan permission pada banyak fitur. Tetap periksa lingkup divisi pada daftar dokumen.'],
 ['Pengelola / Legal','Menyiapkan PDF, membuat dokumen, memilih revisi atau penggantian, membuat turunan, dan mengatur penerima.'],
 ['Petugas master data','Mengelola jenis dokumen, divisi/cabang, klinik, serta data tujuan WhatsApp.'],
 ['Pemberi persetujuan / approver','Meninjau identitas pemohon dan dokumen, kemudian memilih Approve atau Reject.'],
 ['Penerima / pembaca','Mencari dan membuka dokumen yang menjadi haknya; mengajukan akses jika dokumen terkunci.'],
 ['Administrator pengguna','Membuat akun lokal atau terhubung HRIS, menentukan divisi/role, dan mengaktifkan atau menonaktifkan akun.']],[.32,.68])
sub(p,'Dua jenis izin yang berbeda')
bullets(p,['<b>Izin fitur:</b> menentukan menu dan tindakan yang bisa digunakan, misalnya tambah dokumen atau ubah watermark.','<b>Akses dokumen:</b> menentukan apakah PDF tertentu bisa dibuka. Pengguna dapat melihat kartu dokumen di galeri tetapi tetap berstatus Locked.','<b>Distribusi per orang:</b> memberi akses kepada akun yang dipilih meskipun divisinya tidak menerima distribusi.'])
note(p,'MENU ATAU TOMBOL TIDAK TERSEDIA','Hubungi administrator dengan menyebutkan menu, tindakan, dan kebutuhan kerja. Jangan menggunakan akun orang lain untuk menyimpan perubahan atau memberikan persetujuan.','warn')

p=page('Alur besar pengendalian dokumen','Dari dokumen pusat sampai dibaca penerima.','02 / ALUR UTAMA')
flow(p,'Gambar 1. Alur pengendalian dokumen', ['Pengelola / Legal','Sistem','Penerima / approver'],[
 (0,'Siapkan PDF, jenis, divisi\ndan tanggal'),(0,'Upload melalui\nKelola Dokumen'),(1,'Simpan file dan metadata;\nbentuk nomor serta R0'),(0,'Atur penerima pada\nmenu Distribusi'),(1,'Simpan hak distribusi;\nperbarui notifikasi'),(2,'Cari dokumen\ndi Lihat Dokumen'),(2,'Buka bila berhak; jika Locked,\najukan akses'),(2,'Approver memutuskan\nApprove / Reject'),(2,'Baca PDF bila akses\ntelah diberikan')])
sub(p,'Titik pemeriksaan')
bullets(p,['Sebelum upload: pastikan isi PDF final untuk versi yang akan diterbitkan.','Sesudah upload: cocokkan nomor dokumen, revisi, tanggal, dan status.','Sesudah distribusi: periksa kembali penerima tiap dokumen.','Sesudah perubahan: pastikan penerima membaca nomor dan revisi yang tepat.'])
note(p,'BATAS MAKNA PERSETUJUAN','Menu Persetujuan memproses <b>akses baca</b>. Menu ini bukan alur persetujuan substansi atau tanda tangan dokumen sebelum diterbitkan.')

p=page('Login, dashboard & ringkasan','Mulai sesi kerja dan kenali tampilan awal.','03 / MULAI MENGGUNAKAN')
sub(p,'A. Login dan logout')
steps(p,['Buka alamat Sistem Dokumen yang diberikan administrator.','Isi <b>Username</b> dan <b>Password</b>. Aktifkan Remember hanya pada perangkat yang sesuai kebijakan kantor.','Klik tombol masuk. Akun harus berstatus aktif; login yang berhasil mengarah ke Dashboard atau halaman tujuan sebelumnya.','Jika muncul 403 setelah login, minta administrator memeriksa hak Dashboard/menu tujuan.','Setelah selesai, buka menu akun dan pilih <b>Logout</b>.'])
sub(p,'B. Memahami Dashboard')
para(p,'Superadmin memperoleh statistik dokumen, jumlah aktif/tidak aktif, dokumen dan revisi dalam periode tertentu, distribusi menurut jenis/divisi, serta ringkasan permintaan akses. Pengguna lain memperoleh tampilan daftar/kartu dokumen dengan lingkup yang mengikuti pengaturan akun.')
sub(p,'C. Document Control > Ringkasan')
steps(p,['Buka <b>Document Control > Ringkasan</b>.','Lihat jumlah dokumen aktif, dokumen dalam lingkup pengguna, dokumen yang didistribusikan, dan permintaan akses pending.','Gunakan daftar dokumen terbaru atau tautan fitur untuk melanjutkan pekerjaan.'])
note(p,'MEMBACA ANGKA DENGAN BENAR','Beberapa angka ringkasan bersifat keseluruhan sistem, sementara daftar dokumen dapat dibatasi divisi. Jumlah di dua tempat tidak selalu sama.')
note(p,'LUPA PASSWORD','Hubungi administrator. Akun lokal dapat diperbarui melalui Users; akun yang terhubung HRIS perlu dikoordinasikan dengan pengelola HRIS.')

p=page('Master jenis dokumen & klinik','Siapkan kategori penomoran dan identitas klinik.','04 / MASTER DATA')
sub(p,'A. Document Types / Jenis Dokumen')
steps(p,['Buka <b>Master Data > Document Types</b>.','Klik tombol tambah. Isi <b>Kode</b>, <b>Nama</b>, deskripsi bila diperlukan, dan status aktif.','Gunakan kode singkat yang disepakati, misalnya SOP atau SK. Kode dipakai dalam nomor dokumen.','Simpan, lalu cari kembali datanya pada daftar.','Gunakan Edit untuk koreksi. Sebelum menghapus, pastikan data tidak diperlukan oleh dokumen yang sudah ada.'])
table(p,['FIELD','CONTOH / PEDOMAN'],[['Kode jenis','SOP - gunakan kode konsisten dan tidak duplikat.'],['Nama jenis','Standar Operasional Prosedur.'],['Deskripsi','Keterangan penggunaan jenis dokumen.'],['Status','Active agar tersedia pada pilihan pembuatan dokumen.']],[.28,.72])
sub(p,'B. Klinik')
steps(p,['Buka <b>Master Data > Klinik</b>, kemudian pilih <b>Add Klinik</b>.','Isi <b>Code</b> dan <b>Name</b>; lengkapi Address dan Phone bila diperlukan.','Atur Status, lalu simpan. Contoh kode: KLINIK01.','Gunakan pencarian kode/nama/telepon untuk menemukan data; gunakan Edit untuk memperbarui.'])
note(p,'KLINIK DAN CABANG DIVISI BERBEDA','Master Klinik dipakai saat membuat <b>dokumen turunan</b>. Detail/cabang pada Master Divisi dipakai untuk struktur organisasi dan pilihan distribusi. Membuat salah satunya tidak otomatis membuat yang lain.','warn')
note(p,'SEBELUM MENGHAPUS MASTER','Utamakan pemeriksaan hubungan data dan koordinasi admin. Menghapus master yang digunakan dapat berdampak pada dokumen terkait; menonaktifkan lebih sesuai bila hanya ingin menghentikan pemakaian baru.','warn')

p=page('Master divisi, cabang & WhatsApp','Atur pemilik dokumen, struktur penerima, dan kontak.','04 / MASTER DATA')
sub(p,'A. Membuat divisi utama')
steps(p,['Buka <b>Master Data > Divisi</b>, lalu klik <b>Add Divisi</b>.','Pilih <b>Jenis Office</b>: Holding atau DJC.','Isi <b>Code</b> dan <b>Name</b>; kode divisi digunakan dalam penomoran dokumen.','Pilih <b>Jenis Divisi (WA)</b>: Personal atau Group. Lengkapi kontak sesuai arahan administrator.','Isi Description bila diperlukan, tentukan Status, lalu simpan.'])
sub(p,'B. Menambah detail / cabang')
steps(p,['Temukan divisi induk, buka bagian detail, lalu pilih <b>Tambah Detail</b>.','Isi Nama Detail/cabang, jenis WA, kontak, deskripsi, dan status.','Simpan. Pastikan cabang tampil di bawah induk yang benar.','Gunakan Edit Detail untuk koreksi; periksa kembali pilihan cabang pada menu Distribusi.'])
table(p,['PILIHAN','ARTI OPERASIONAL'],[['Holding / DJC','Pengelompokan divisi pada master dan tab distribusi.'],['Personal','Pesan WhatsApp memakai nomor pada No. WhatsApp.'],['Group','Pengiriman memakai identitas grup yang dikonfigurasi admin.'],['Active / Inactive','Data aktif tersedia pada pilihan penerima/master yang relevan.']],[.30,.70])
note(p,'CATATAN KONFIGURASI GROUP','Label field saat ini tertulis <b>Fonnte API Token</b>, tetapi proses distribusi membacanya sebagai ID grup WhatsApp (berakhiran @g.us). Mintalah admin integrasi memastikan nilainya. Jangan menebak atau memasukkan kredensial ke field ini.','warn')
note(p,'PERIKSA TUJUAN','Pilih divisi dan cabang penerima secara eksplisit saat distribusi. Pemilihan induk tidak boleh dianggap otomatis mencakup seluruh cabang tanpa memeriksa tanda pilihnya.')

p=page('Upload dokumen baru','Buat identitas dan versi awal dokumen.','05 / KELOLA DOKUMEN')
para(p,'Gunakan fitur ini untuk dokumen baru yang belum memiliki identitas dalam sistem. Untuk memperbarui isi dokumen yang sama, gunakan Revisi (halaman 12).')
steps(p,['Buka <b>Document Control > Kelola Dokumen</b>.','Klik tombol tambah dokumen untuk membuka form <b>Add Document</b>.','Pilih <b>Document Type</b> dan <b>Divisi</b> pemilik.','Isi <b>Document Name</b> dan <b>Publish Date</b>.','Pada <b>Upload Document (PDF)</b>, pilih file PDF maksimal 10 MB.','Isi Catatan bila diperlukan. Gunakan ringkasan isi yang jelas agar mudah dicari.','Periksa sakelar <b>Active</b>, lalu klik <b>Simpan</b>.','Cari dokumen pada daftar. Periksa nomor, R0, tanggal, status, dan isi PDF.','Lanjutkan ke <b>Pilih Tindakan > Atur distribusi</b> untuk menambah penerima.'])
table(p,['ISIAN','KETERANGAN'],[['Document Type','Wajib; jenis dokumen yang sudah aktif pada master.'],['Divisi','Wajib; pemilik dokumen dan bagian dari nomor.'],['Document Name','Wajib; nama yang mudah dikenali, maksimal 255 karakter.'],['Publish Date','Wajib; tanggal terbit/berlaku yang juga menentukan tahun nomor.'],['PDF','Wajib; format PDF, maksimal 10 MB.'],['Catatan','Opsional; ringkasan isi atau konteks dokumen.'],['Active','Menandai status aktif dokumen.']],[.30,.70])
note(p,'HASIL PENYIMPANAN','Nomor dibuat otomatis dan revisi awal adalah R0. Form upload utama tidak menampilkan pengaturan penerima lengkap; distribusi awal diarahkan ke divisi asal. Gunakan menu Distribusi untuk penerima tambahan.')

p=page('Alur upload & penomoran','Nomor dokumen dibuat dari jenis, divisi, urutan, dan tahun.','05 / KELOLA DOKUMEN')
flow(p,'Gambar 2. Pembuatan dokumen baru',['Pengelola','Sistem'],[(0,'Isi jenis, divisi, nama,\ntanggal, PDF, catatan'),(1,'Periksa field wajib,\nformat PDF dan ukuran'),(1,'Tentukan urutan berikutnya\nuntuk jenis + divisi + tahun'),(1,'Simpan dokumen R0\ndan distribusi divisi asal'),(0,'Periksa nomor dan isi PDF;\nlanjutkan distribusi')])
sub(p,'Contoh nomor (ilustrasi)')
table(p,['BAGIAN','NILAI','MAKNA'],[['Jenis','SOP','Kode dari master jenis dokumen.'],['Divisi','LEG','Kode divisi pemilik.'],['Urutan','001','Nomor urut dalam kombinasi jenis/divisi/tahun.'],['Tahun','2026','Diambil dari tanggal terbit.'],['Nomor lengkap','SOP-LEG/001/2026','Identitas dasar dokumen.'],['Versi tampil','SOP-LEG/001/2026 R0','Nomor dasar ditambah angka revisi.']],[.23,.34,.43])
note(p,'JIKA VALIDASI GAGAL','Perbaiki field yang ditandai. File upload mungkin perlu dipilih kembali. Jika penyimpanan menghasilkan error, cari dulu dokumen pada daftar sebelum mengirim ulang agar tidak membuat duplikat.')
note(p,'TANGGAL DAN STATUS','Tanggal masa depan tidak otomatis menjadi penjadwalan publikasi. Periksa Active secara terpisah. Nomor yang terbentuk bukan nomor yang perlu diketik manual oleh pengguna.')

p=page('Edit metadata, status & hapus','Pilih tindakan yang sesuai dengan tujuan perubahan.','05 / KELOLA DOKUMEN')
sub(p,'A. Mencari dan memeriksa dokumen')
steps(p,['Buka Kelola Dokumen. Gunakan Jenis Dokumen, Divisi, dan Cari Dokumen, lalu jalankan pencarian.','Buka kelompok nomor dokumen untuk melihat baris revisi, nama, jenis/divisi, tanggal, dan status.','Periksa label <b>Diubah dari</b> atau <b>Diganti oleh</b> bila ada hubungan penggantian.','Gunakan ikon mata pada kolom Buka untuk membuka dokumen.'])
sub(p,'B. Edit metadata')
steps(p,['Pada baris yang benar, pilih <b>Pilih Tindakan > Edit metadata</b>.','Perbarui nama, tanggal, catatan, atau status. Periksa jenis dan divisi sebelum menyimpan.','File pengganti bersifat opsional. Biarkan kosong bila file lama tetap digunakan.','Simpan lalu periksa kembali baris dokumen dan isi PDF.'])
note(p,'EDIT BUKAN REVISI','Mengganti file lewat Edit menimpa file pada versi tersebut dan file lama dihapus oleh aplikasi. Jika perlu menjaga riwayat isi, gunakan <b>Buat revisi</b>. Mengubah jenis/divisi melalui Edit dapat menghasilkan nomor baru dan mengembalikan angka revisi ke R0.','warn')
sub(p,'C. Menonaktifkan atau menghapus')
para(p,'Untuk menandai dokumen tidak aktif, matikan Active melalui Edit metadata lalu simpan. Untuk menghapus, pilih <b>Hapus dokumen</b>, pastikan nomor/revisi benar, kemudian setujui konfirmasi yang muncul.')
note(p,'PENGHAPUSAN PERMANEN','Penghapusan menghapus record dan file PDF yang terkait. Tidak tersedia menu pemulihan dalam alur yang diperiksa. Simpan salinan arsip sesuai kebijakan sebelum menghapus.','warn')
note(p,'ACTIVE BUKAN KUNCI AKSES','Inactive menandai status dokumen dan memengaruhi pemilihan distribusi, tetapi bukan jaminan pencabutan seluruh akses baca yang sudah ada. Koordinasikan penarikan dokumen dengan admin.')

p=page('Membuat revisi dokumen','Identitas tetap, versi bertambah.','06 / VERSI DOKUMEN')
steps(p,['Di Kelola Dokumen, cari nomor yang akan diperbarui.','Pilih <b>Pilih Tindakan > Buat revisi R...</b>. Aplikasi membuka halaman Revisions; gunakan tombol pembuatan revisi bila form belum terbuka.','Isi <b>Nama Dokumen Revisi</b>, <b>Tanggal Berlaku</b>, <b>File Revisi (PDF)</b>, dan Keterangan Dokumen.','Periksa status Active, kemudian simpan.','Periksa versi baru. Nomor dasar tetap dan angka revisi mengikuti revisi tertinggi + 1.','Periksa distribusi yang diwarisi serta status versi lama.'])
flow(p,'Gambar 3. Alur revisi',['Pengelola','Sistem'],[(0,'Pilih dokumen dasar;\nunggah PDF revisi'),(1,'Hitung revisi tertinggi\ndengan nomor yang sama'),(1,'Nonaktifkan versi lama;\nbuat versi R berikutnya'),(1,'Salin penerima divisi\ndan pengguna dari dokumen dasar'),(0,'Periksa PDF baru, status\ndan penerima')])
note(p,'CONTOH','SOP-LEG/001/2026 R0 menjadi SOP-LEG/001/2026 R1. Dokumen lama tetap tercatat sebagai versi sebelumnya; jangan menghapusnya hanya karena revisi baru tersedia.')
note(p,'PILIH VERSI DASAR DENGAN BENAR','Distribusi disalin dari baris yang dipilih sebagai dasar. Pilih versi terbaru yang penerimanya sudah benar. Untuk revisi turunan klinik, minta admin memverifikasi hasil karena jalur revisi terpisah belum konsisten menyimpan identitas klinik.','warn')

p=page('Mengganti menjadi dokumen baru','Buat nomor baru sambil menyimpan hubungan dengan dokumen sebelumnya.','06 / VERSI DOKUMEN')
table(p,['TINDAKAN','NOMOR / HASIL','KAPAN DIGUNAKAN'],[['Edit metadata','Versi yang sama; perubahan jenis/divisi dapat menomori ulang.','Koreksi metadata, bukan riwayat isi baru.'],['Revisi','Nomor tetap; R bertambah.','Isi berubah tetapi identitas dokumen tetap.'],['Ubah menjadi dokumen baru','Nomor baru; mulai R0; terhubung ke dokumen lama.','Dokumen pengganti dengan identitas baru.'],['Buat dokumen turunan','Nomor induk + kode klinik; mulai R0.','Penerapan khusus klinik.']],[.28,.38,.34])
steps(p,['Cari dokumen sumber di Kelola Dokumen.','Pilih <b>Pilih Tindakan > Ubah menjadi dokumen baru</b>.','Periksa jenis, divisi, nama, tanggal, catatan, file PDF pengganti, dan status.','Klik Simpan; sistem membuat nomor baru dengan R0 dan menghubungkannya dengan sumber.','Periksa label hubungan pada daftar, isi PDF baru, serta penerima distribusi yang disalin.','Tinjau apakah dokumen lama perlu dinonaktifkan sesuai kebijakan.'])
flow(p,'Gambar 4. Penggantian dokumen',['Pengelola','Sistem'],[(0,'Pilih dokumen lama\ndan isi dokumen pengganti'),(1,'Buat nomor baru R0;\nhubungkan lama dengan baru'),(1,'Salin distribusi dokumen lama\nbila tersedia'),(0,'Periksa hubungan dan status\nkedua dokumen')])
note(p,'STATUS DOKUMEN LAMA','Pembuatan dokumen pengganti tidak otomatis menonaktifkan dokumen sumber pada alur yang diperiksa. Label Diganti oleh juga bukan pencabutan akses.','warn')

p=page('Membuat turunan klinik','Hubungkan dokumen khusus klinik dengan dokumen pusat.','06 / VERSI DOKUMEN')
steps(p,['Pastikan klinik tujuan sudah aktif pada Master Data > Klinik.','Cari dokumen induk pada Kelola Dokumen.','Pilih <b>Pilih Tindakan > Buat dokumen turunan</b>. Form berjudul <b>Turunkan Dokumen ke Klinik</b>.','Pilih Klinik, isi Document Name, Publish Date, Catatan, PDF, dan status Active.','Simpan, lalu periksa nomor turunan dan hubungan dengan dokumen induk.','Buka Distribusi untuk memastikan penerimanya sesuai kebutuhan klinik.'])
flow(p,'Gambar 5. Turunan dokumen klinik',['Pengelola','Sistem','Penerima'],[(0,'Pilih induk dan\nklinik tujuan'),(0,'Unggah PDF khusus klinik\ndan lengkapi metadata'),(1,'Buat nomor induk + kode klinik\npada revisi R0'),(1,'Simpan relasi turunan;\nsalin distribusi induk'),(0,'Sesuaikan distribusi\nuntuk klinik tujuan'),(2,'Buka dokumen melalui\ntautan yang memiliki akses')])
note(p,'CONTOH NOMOR','Induk SOP-LEG/001/2026 menghasilkan SOP-LEG/001/2026-KLINIK01 R0 untuk klinik berkode KLINIK01. Nomor yang sama pada R0 tidak dapat dibuat dua kali.')
note(p,'TURUNAN TIDAK OTOMATIS KHUSUS KLINIK','Distribusi awal mewarisi induk, bukan otomatis dibatasi klinik tujuan. Galeri utama saat ini menampilkan dokumen non-klinik; gunakan Kelola Dokumen dan koordinasikan tautan akses dengan pengelola bila turunan tidak muncul di galeri.','warn')

p=page('Mengatur distribusi','Pilih penerima per divisi, cabang, orang, atau gabungannya.','07 / DISTRIBUSI')
steps(p,['Buka <b>Document Control > Distribusi</b>, atau <b>Pilih Tindakan > Atur distribusi</b> pada dokumen.','Pada <b>Choose Documents</b>, pilih satu atau beberapa dokumen aktif. Periksa nomor dan revisinya.','Untuk setiap dokumen, buka tab kelompok divisi seperti Holding atau DJC.','Klik tanda pilih/+ pada divisi yang akan menerima. Jika ada detail/cabang, buka pilihan cabang dan tandai penerima yang diperlukan.','Pada <b>Distribusi langsung per orang</b>, cari nama/username dan pilih akun penerima tambahan bila diperlukan.','Periksa seluruh pilihan pada setiap dokumen. Jangan menganggap pilihan dokumen pertama otomatis berlaku pada dokumen lain.','Atur <b>Kirim notifikasi WhatsApp</b>: aktif untuk mencoba pengiriman WA, atau nonaktif jika hanya menyimpan distribusi.','Klik <b>Simpan & Distribusikan</b>.','Buka kembali dokumen di Distribusi untuk memeriksa pilihan yang tersimpan. Minta penerima memeriksa galeri.'])
table(p,['JENIS PENERIMA','DAMPAK'],[['Divisi','Anggota divisi penerima dapat membuka dokumen melalui galeri.'],['Cabang / detail divisi','Pilih entitas cabang yang benar; cocokkan dengan divisi pada akun penerima.'],['Orang tertentu','Akun tersebut dapat membuka meskipun divisinya tidak dipilih.'],['Kombinasi','Akses dapat berasal dari salah satu jalur yang memenuhi syarat.']],[.30,.70])
note(p,'SIMPAN MENGGANTI PILIHAN','Daftar penerima untuk dokumen yang disimpan diganti dengan pilihan saat ini. Penerima yang tidak lagi dipilih dapat terhapus dari distribusi. Periksa sebelum menekan Simpan.','warn')
note(p,'RESET','Reset memuat ulang pilihan tersimpan. Perubahan yang belum disimpan dapat hilang; gunakan bila ingin membatalkan perubahan form.')

p=page('Alur distribusi & perubahan penerima','Hak baca dan pengiriman pesan merupakan dua hasil yang berbeda.','07 / DISTRIBUSI')
flow(p,'Gambar 6. Alur distribusi',['Pengelola','Sistem','Penerima'],[(0,'Pilih dokumen aktif\ndan penerima per dokumen'),(0,'Tentukan opsi WA; klik\nSimpan & Distribusikan'),(1,'Validasi pilihan;\nsimpan distribusi terbaru'),(1,'Kirim pembaruan\nnotifikasi aplikasi'),(1,'Jika opsi WA aktif,\ncoba kirim ke tujuan divisi'),(2,'Periksa notifikasi;\nlogin dan buka galeri'),(2,'Buka PDF jika akses\nmemenuhi syarat')])
sub(p,'Mengurangi atau mengganti penerima')
steps(p,['Pilih dokumen yang sama pada Distribusi.','Hilangkan tanda pilih penerima lama; tambahkan penerima baru bila perlu.','Periksa daftar divisi, cabang, dan orang sekaligus, lalu simpan.','Cocokkan hasil dengan penerima dan administrator.'])
note(p,'AKSES MASIH BISA ADA','Menghapus distribusi per orang belum tentu menghilangkan akses jika pengguna masih berada di divisi pemilik, menerima lewat distribusi divisi, mempunyai approval valid, atau berstatus Superadmin.','warn')
note(p,'WHATSAPP PER ORANG','Pilihan orang memberi akses aplikasi dan dapat menerima notifikasi aplikasi. Pengiriman WA pada implementasi saat ini menggunakan tujuan divisi, sehingga jangan menganggap nomor WA setiap orang yang dipilih otomatis menerima pesan.')

p=page('Galeri & pencarian AI','Temukan dokumen dan pastikan identitas versinya.','08 / MEMBACA DOKUMEN')
sub(p,'A. Pencarian biasa')
steps(p,['Buka <b>Document Control > Lihat Dokumen</b>.','Isi pencarian nama/nomor, pilih jenis dokumen dan divisi bila diperlukan.','Jalankan pencarian. Gunakan tombol pembersih filter untuk kembali ke daftar semula.','Periksa kartu: nama, nomor, R, jenis, divisi, tanggal, status dokumen, dan status akses.','Gunakan navigasi halaman bila dokumen belum terlihat.'])
table(p,['LABEL','ARTI'],[['Active / Inactive','Status dokumen; berbeda dari izin pembaca.'],['Locked','Akun saat ini belum memenuhi syarat akses baca.'],['Unlocked','Akun dapat membuka berdasarkan aturan akses galeri.'],['Distributed','Akses berasal dari distribusi kepada divisi atau akun.'],['Pending','Permintaan akses sedang menunggu keputusan.'],['Rejected','Permintaan ditolak; pengajuan ulang tersedia melalui alur galeri.']],[.29,.71])
sub(p,'B. Pencarian AI')
steps(p,['Gunakan kolom pencarian AI dan masukkan kata/frasa yang menjelaskan kebutuhan.','Jalankan pencarian lalu tunggu hasil. Contoh: prosedur pengarsipan kontrak.','Periksa nama, nomor, revisi, divisi, dan catatan pada hasil.','Klik hasil yang tepat. Aturan akses baca tetap diperiksa saat dokumen dibuka.'])
note(p,'CAKUPAN PENCARIAN','AI mencari dari nama, nomor, dan catatan, lalu dapat mengurutkan kandidat. Ini bukan pencarian seluruh isi PDF. Hasil dapat memuat revisi lama/tidak aktif, sehingga cocokkan revisi sebelum menggunakan dokumen.')
note(p,'GALERI UTAMA','Daftar utama menampilkan revisi tertinggi per nomor untuk dokumen non-klinik. Hasil AI dan daftar Kelola Dokumen dapat berbeda karena cakupannya berbeda.')

p=page('Membaca PDF & memahami akses','Pastikan dokumen dan sumber akses sebelum membaca.','08 / MEMBACA DOKUMEN')
steps(p,['Pada kartu Unlocked, klik <b>Open</b>. Pada dokumen Locked, ikuti alur permintaan akses pada halaman berikutnya.','Periksa judul dan nomor revisi pada halaman pembaca.','Gunakan tombol sebelumnya/berikutnya untuk berpindah halaman.','Gunakan tombol minus/plus untuk memperkecil atau memperbesar tampilan PDF.','Jika indikator sisa waktu tersedia, selesaikan pekerjaan dalam masa akses tersebut.','Kembali ke galeri untuk memilih dokumen lain.'])
table(p,['SUMBER AKSES','PENJELASAN'],[['Superadmin','Dapat membuka melalui pengecualian role pada galeri.'],['Divisi pemilik / OWNER','Divisi akun sama dengan divisi pemilik dokumen.'],['DISTRIBUTION','Divisi atau akun dipilih sebagai penerima distribusi.'],['APPROVAL','Ada permintaan disetujui yang masih berlaku menurut data masa akses.']],[.34,.66])
sub(p,'Jika akses memiliki batas waktu')
para(p,'Penghitung mundur ditampilkan bila halaman menerima waktu kedaluwarsa yang valid untuk akses approval. Setelah habis, halaman menampilkan pemberitahuan. Kembali ke galeri dan ajukan lagi jika pekerjaan masih memerlukan akses.')
note(p,'TIDAK SELALU ADA TIMER','Approval tanpa waktu kedaluwarsa tidak menampilkan timer. Form Approve saat ini tidak menyediakan isian tanggal kedaluwarsa. Jangan menganggap semua approval otomatis dibatasi durasi; koordinasikan dengan admin (halaman 25).','warn')
note(p,'UNDUH DAN CETAK','Viewer utama menyediakan navigasi dan zoom. Jangan menganggap tombol unduh/cetak tersedia pada semua halaman. Watermark dan akses waktu tidak dapat menarik kembali salinan yang sudah dimiliki pengguna.')

p=page('Mengajukan akses dokumen','Permintaan dibuat saat pengguna membuka dokumen yang masih terkunci.','09 / PERSETUJUAN AKSES')
steps(p,['Cari dokumen di Lihat Dokumen dan periksa status Locked.','Klik tindakan akses pada kartu. Jika belum ada permintaan pending, aplikasi membuat permintaan baru dan menampilkan halaman <b>Request Document Access</b>.','Periksa informasi dokumen dan status permintaan. Tidak ada kewajiban mengisi alasan pada alur galeri saat ini.','Tunggu keputusan pihak yang berhak. Hindari membuka berulang kali dengan tujuan membuat permintaan tambahan.','Setelah ada keputusan, muat ulang galeri atau buka kembali dokumen.','Jika ditolak atau akses kedaluwarsa, koordinasikan kebutuhan dan gunakan pengajuan ulang yang tersedia.'])
flow(p,'Gambar 7. Permintaan akses',['Pemohon','Sistem','Approver'],[(0,'Pilih dokumen\nyang Locked'),(1,'Periksa apakah sudah\nada request Pending'),(1,'Tampilkan request lama\natau buat request baru'),(2,'Tinjau pemohon\ndan dokumen'),(2,'Pilih Approve\natau Reject'),(0,'Buka ulang galeri:\nbaca jika disetujui')])
note(p,'ALUR PENOLAKAN','Reject mengakhiri permintaan tersebut. Pengajuan ulang adalah permintaan baru; diskusikan alasan kebutuhan melalui kanal kerja yang digunakan organisasi.')
note(p,'PERMINTAAN TIDAK MUNCUL','Jika halaman error atau status tidak berubah, catat nomor dokumen dan waktu kejadian lalu hubungi admin. Jangan menganggap permintaan tersimpan hanya karena tombol sudah diklik.','warn')

p=page('Memproses persetujuan akses','Untuk pengguna yang mendapat hak pada menu Persetujuan.','09 / PERSETUJUAN AKSES')
steps(p,['Buka <b>Document Control > Persetujuan</b>.','Pilih filter <b>Pending</b> untuk meninjau permintaan yang belum diputuskan.','Periksa User, Department, Document, Reason bila tersedia, Requested At, dan Status.','Konfirmasikan kebutuhan pemohon sesuai kewenangan kerja Anda.','Klik <b>Approve</b> untuk memberi persetujuan akses atau <b>Reject</b> untuk menolak.','Periksa hasil pada kolom Status dan Decision. Nama pengambil keputusan serta waktunya dicatat.','Minta pemohon membuka ulang galeri. Jika status tidak berubah, muat ulang halaman untuk memastikan hasil terakhir.'])
table(p,['STATUS','TINDAKAN BERIKUTNYA'],[['Pending','Dapat diproses dengan Approve atau Reject.'],['Approved','Pemohon dapat membuka bila akses memenuhi aturan masa berlaku.'],['Rejected','Permintaan ditolak; kebutuhan baru dapat diajukan ulang.'],['Expired / waktu habis','Periksa waktu akses aktual; ajukan lagi bila diperlukan.']],[.28,.72])
note(p,'APA YANG DISETUJUI','Persetujuan berlaku untuk akses pemohon terhadap dokumen tertentu. Ini tidak mengubah file, nomor, atau distribusi ke semua pengguna.')
note(p,'MASA AKSES','Tampilan Approve yang diperiksa tidak memiliki input tanggal/jam kedaluwarsa. Penetapan akses berbatas waktu perlu dipastikan bersama administrator sebelum dijanjikan kepada pemohon.','warn')
note(p,'KEPUTUSAN SUDAH DIPROSES','Permintaan selain Pending tidak menyediakan tindakan keputusan yang sama. Jika diperlukan koreksi akses, koordinasikan dengan administrator; jangan membuat akun pengganti untuk menghindari keputusan.')

p=page('Notifikasi aplikasi & WhatsApp','Bedakan pemberitahuan sistem dari bukti dokumen telah dibaca.','10 / NOTIFIKASI')
sub(p,'A. Notifikasi di aplikasi')
para(p,'Ikon notifikasi pada navbar menampilkan dokumen belum dibaca. Sistem mengirim pembaruan realtime ketika dokumen dibuat, file/status tertentu berubah, atau distribusi diperbarui. Penerimanya ditentukan dari divisi pemilik dan distribusi dokumen.')
steps(p,['Buka ikon notifikasi dan pilih dokumen yang ingin diperiksa.','Saat dokumen dibuka melalui galeri, aplikasi menandai notifikasi dibaca untuk akun tersebut.','Gunakan tindakan tandai semua dibaca bila tersedia dan memang diperlukan.','Jika daftar belum berubah, muat ulang halaman. Koneksi realtime memiliki mekanisme pengambilan feed berkala saat terputus.'])
sub(p,'B. Notifikasi WhatsApp dari Distribusi')
steps(p,['Pada Distribusi, pastikan dokumen dan tujuan divisi/cabang sudah benar.','Aktifkan <b>Kirim notifikasi WhatsApp</b>, lalu simpan distribusi.','Penerima memeriksa pesan berisi identitas dokumen dan tautan aplikasi.','Penerima tetap harus login; pesan WA bukan pemberian akses terpisah dari distribusi.'])
table(p,['KEJADIAN','YANG PERLU DIPAHAMI'],[['WA tidak diterima','Periksa tujuan Personal/Group dan konfigurasi integrasi bersama admin.'],['Distribusi tersimpan, WA gagal','Hak distribusi dapat tetap tersimpan meskipun pesan tidak sampai.'],['Hanya penerima per orang','Jangan mengasumsikan WA otomatis dikirim ke nomor setiap akun.'],['Notifikasi ditandai dibaca','Menandai notifikasi bukan tanda tangan atau bukti isi telah dipahami.']],[.34,.66])
note(p,'MENGIRIM ULANG','Tidak ditemukan tombol khusus kirim ulang WA pada halaman distribusi yang diperiksa. Menyimpan kembali dengan opsi WA aktif dapat mencoba pengiriman lagi; pastikan daftar penerima tetap benar agar tidak berubah tanpa sengaja.','warn')
note(p,'SETELAH APPROVE / REJECT','Jangan bergantung pada pesan realtime keputusan. Pemohon sebaiknya membuka ulang galeri untuk melihat hasil terbaru.')

p=page('Mengelola pengguna & HRIS','Membuat akun, menetapkan divisi/role, dan memperbarui status.','11 / ADMINISTRASI AKSES')
sub(p,'A. Membuat akun lokal')
steps(p,['Buka <b>User Access > Users</b>. Gunakan form tambah pengguna.','Biarkan Karyawan HRIS kosong untuk akun lokal.','Isi Full Name, Username, Email, Nomor WhatsApp bila diperlukan, Department, dan Role.','Isi Password serta konfirmasinya. Username dan email tidak boleh dipakai akun lain.','Aktifkan Status bila akun boleh login, lalu simpan.','Cari pengguna pada daftar dan periksa divisi/role yang tersimpan.'])
sub(p,'B. Membuat akun dari HRIS')
steps(p,['Pilih <b>Karyawan HRIS</b> yang tepat pada form.','Periksa identitas karyawan yang dimuat. Nama, username, email, nomor WA, dan password mengikuti sumber HRIS.','Tentukan Department dan Role aplikasi, periksa Status, lalu simpan.','Jika HRIS tidak tersedia, periksa pemberitahuan halaman dan hubungi admin integrasi.'])
sub(p,'C. Edit, reset password, dan nonaktifkan')
para(p,'Cari pengguna berdasarkan nama/username/email, divisi, atau status. Gunakan Edit untuk memperbarui. Pada akun lokal, password kosong saat edit mempertahankan password lama; isi dan konfirmasikan untuk mengganti. Matikan Active untuk mencegah login berikutnya. Gunakan Hapus hanya setelah mempertimbangkan catatan yang terkait.')
note(p,'SINKRONISASI HRIS','Saat halaman Users dibuka, aplikasi mencoba memperbarui identitas akun yang tertaut HRIS. Perubahan lokal pada identitas/password akun tersebut dapat ditimpa sumber HRIS. Perbaiki data sumber melalui pengelola HRIS.','warn')
note(p,'LINGKUP DIVISI','Tetapkan divisi secara sengaja. Akun tanpa divisi dapat memperoleh cakupan daftar/notifikasi lebih luas pada sebagian halaman; jangan mengosongkannya hanya untuk melewati pembatasan.')

p=page('Mengelola role & permission','Atur siapa boleh melihat dan menjalankan tindakan.','11 / ADMINISTRASI AKSES')
sub(p,'A. Membuat atau mengubah role')
steps(p,['Buka <b>User Access > Roles</b>.','Isi Nama Role dan periksa Guard Name. Untuk penggunaan web, gunakan guard <b>web</b>.','Simpan. Gunakan Edit untuk koreksi nama role sesuai kebutuhan organisasi.','Sebelum menghapus role, periksa pengguna yang masih menggunakannya.'])
sub(p,'B. Menetapkan permission')
steps(p,['Buka <b>User Access > Permission Settings</b>.','Pilih role yang akan diatur. Pastikan bukan role lain dengan nama mirip.','Buka kelompok fitur, lalu centang izin yang diperlukan. Periksa izin lihat dan izin tindakan secara terpisah.','Gunakan pilih semua hanya bila role memang memerlukan seluruh izin yang dipilih.','Simpan perubahan.','Buka Users dan pastikan akun memakai role yang benar. Uji menu menggunakan akun dengan role tersebut.'])
table(p,['KELOMPOK IZIN','KEBUTUHAN KERJA'],[['Dashboard / master','Melihat ringkasan atau mengelola data dasar.'],['Kelola dokumen','Lihat, tambah, update, hapus, penggantian, dan turunan sesuai pilihan.'],['Distribusi / revisi / approval','Menjalankan alur dokumen lanjutan sesuai tugas.'],['Users / Roles / Permissions','Administrasi akun dan izin.'],['Settings / Framework','Mengakses atau mengubah pengaturan terkait.']],[.40,.60])
note(p,'UJI AKSES NYATA','Izin lihat tidak selalu dipisahkan dari tindakan pada semua modul. Khusus revisi, distribusi, dan approval, pemeriksaan backend saat ini berpusat pada permission view modul. Uji hasil role sebelum diberikan secara luas.','warn')
note(p,'NAMA SUPERADMIN','Nama Superadmin memiliki perilaku khusus. Jangan memakainya untuk role biasa yang hanya membutuhkan sebagian izin.')

p=page('Pengaturan watermark / DRM','Atur penanda visual dan periksa hasil pada PDF.','12 / PENGATURAN')
steps(p,['Buka <b>Settings > Watermark/DRM</b>. Pastikan badge Editable jika akan mengubah.','Aktifkan <b>Enable watermark</b>. Pilih Mode Text atau Image.','Untuk Text, isi Text Template; untuk Image, unggah PNG/JPG maksimal 2 MB.','Atur ukuran huruf, rotasi, opacity, posisi, warna, dan pola berulang sesuai kebutuhan.','Periksa opsi Show on download, lalu simpan pengaturan.','Uji hasil pada dokumen contoh dan jalur pembacaan yang digunakan penerima.'])
table(p,['PENGATURAN','PILIHAN / BATAS'],[['Font Size','8-120.'],['Rotation','-180 sampai 180 derajat.'],['Opacity','0-100; tampilan aktual perlu diperiksa pada PDF.'],['Position','Center, Top Left, Top Right, Bottom Left, Bottom Right.'],['Color','Kode HEX, misalnya #A0A0A0.'],['Repeat','Mengulang pola watermark.'],['Text Template','Contoh: INTERNAL - {user.name} - {date}.']],[.31,.69])
sub(p,'Variabel template pada pemrosesan PDF upload')
para(p,'Pengguna: <b>{user.name}</b>, <b>{user.username}</b>. Waktu: <b>{date}</b>, <b>{datetime}</b>. Dokumen: <b>{doc.name}</b>, <b>{doc.number}</b>, <b>{doc.revision}</b>, <b>{doc.dept}</b>, <b>{doc.type}</b>.')
note(p,'BATAS IMPLEMENTASI SAAT INI','Viewer galeri mengambil PDF asli melalui jalurnya sendiri, sehingga pengaturan ini belum menjamin watermark muncul di sana. Jalur PDF upload memiliki pemrosesan watermark dan dapat kembali ke file asli jika pemrosesan gagal.','warn')
note(p,'MAKNA DRM','Nama menu Watermark/DRM tidak berarti aplikasi menjamin pencegahan salin, foto layar, cetak, atau unduh. Gunakan hasil uji admin untuk menetapkan prosedur distribusi dokumen sensitif.')

p=page('Document Access & Framework System','Pengaturan durasi akses dan tampilan diagram internal.','12 / PENGATURAN')
sub(p,'A. Document Access')
steps(p,['Buka <b>Settings > Document Access</b>.','Periksa pengaturan <b>Document Access Expiry</b> dan sakelar enabled.','Isi <b>Default Duration (minutes)</b> dengan bilangan minimal 1. Contoh: 60 untuk satu jam, 1440 untuk satu hari.','Simpan, kemudian buka kembali halaman untuk memastikan nilai tersimpan.','Uji bersama admin menggunakan satu permintaan akses contoh. Periksa apakah waktu kedaluwarsa benar-benar muncul di viewer.'])
note(p,'DURASI BELUM SERAGAM','Jalur pembacaan lama menghitung masa akses dari waktu keputusan dan durasi default; galeri membaca waktu kedaluwarsa pada permintaan. Form Approve tidak mengisi waktu tersebut. Karena itu, aktifnya pengaturan saja belum membuktikan semua akses memiliki timer.','warn')
note(p,'ERROR SETELAH SIMPAN','Kode saat ini mengarahkan penyimpanan ke nama route yang belum terdaftar. Jika muncul error sesudah menekan Simpan, jangan langsung menyimpan berulang. Buka kembali pengaturan untuk memeriksa nilainya dan laporkan ke admin.','warn')
sub(p,'B. Framework System')
steps(p,['Buka <b>Settings > Framework System</b>.','Baca diagram yang disediakan pengelola aplikasi sebagai gambaran proses internal.','Jika muncul pesan diagram tidak ditemukan, hubungi admin untuk memeriksa file sumber diagram.'])
sub(p,'Pemeriksaan perubahan pengaturan')
bullets(p,['Catat nilai sebelum dan sesudah perubahan.','Gunakan dokumen contoh yang boleh dipakai untuk pengujian.','Uji dengan akun penerima biasa, bukan hanya Superadmin.','Periksa jalur membuka dokumen yang benar-benar digunakan sehari-hari.'])

p=page('Contoh penggunaan lengkap','Contoh berikut memakai data fiktif untuk latihan.','13 / SIMULASI KERJA')
sub(p,'Skenario A. SOP baru untuk dua divisi dan satu orang')
steps(p,['Pengelola menyiapkan jenis SOP dan divisi LEG yang aktif.','Upload Pedoman Arsip Kontrak dengan tanggal 17 September 2026 dan PDF maksimal 10 MB.','Periksa nomor yang dihasilkan, misalnya SOP-LEG/001/2026 R0.','Pada Distribusi, pilih dokumen tersebut; pilih divisi LEG dan FIN, lalu tambahkan akun penerima khusus.','Atur opsi WA dan klik Simpan & Distribusikan.','Minta penerima login, mencari nomor tersebut, dan membuka PDF. Pastikan revisi yang dibaca R0.'])
sub(p,'Skenario B. Isi SOP diperbarui')
steps(p,['Cari SOP-LEG/001/2026 dan pilih Buat revisi.','Unggah PDF terbaru, isi tanggal berlaku dan catatan perubahan, lalu simpan.','Periksa bahwa R1 dibuat dan versi lama tidak aktif.','Periksa penerima distribusi yang diwarisi. Sesuaikan bila ada perubahan organisasi.','Minta penerima membuka versi terbaru, bukan memakai salinan R0 yang pernah disimpan.'])
sub(p,'Skenario C. Pengguna di luar distribusi memerlukan akses')
steps(p,['Pengguna mencari dokumen dan melihat Locked.','Pengguna membuka tindakan akses; aplikasi membuat atau menampilkan request Pending.','Approver membuka Persetujuan, memeriksa pengguna dan dokumen, lalu memilih Approve atau Reject.','Pengguna membuka ulang galeri. Jika disetujui dan valid, PDF dapat dibuka.','Jika harus dibatasi waktu, admin terlebih dahulu memastikan pengaturan kedaluwarsa berfungsi pada jalur galeri.'])
note(p,'CHECKPOINT','Setiap simulasi dianggap selesai setelah hasil tersimpan dan diperiksa oleh pihak berikutnya dalam alur, bukan hanya setelah tombol Simpan ditekan.')

p=page('Penanganan masalah','Periksa penyebab sederhana sebelum mengulangi tindakan.','14 / BANTUAN')
table(p,['GEJALA','LANGKAH YANG DILAKUKAN'],[
 ['Login gagal','Periksa username/password dan status akun. Untuk akun HRIS, koordinasikan data sumber.'],
 ['Menu hilang atau 403','Minta admin memeriksa role dan permission menu/tindakan. Sertakan nama menu dan akun.'],
 ['Jenis/divisi/klinik tidak tersedia','Periksa status aktif pada master dan lingkup divisi akun.'],
 ['PDF ditolak saat upload','Pastikan format PDF asli, ukuran maksimal 10 MB, dan semua field wajib terisi. Pilih ulang file bila form kembali.'],
 ['Nomor turunan sudah ada','Cari nomor induk + kode klinik yang sama. Jangan membuat turunan R0 ganda.'],
 ['Dokumen tidak terlihat','Hapus filter; cek pagination, divisi, status, revisi terbaru, dan apakah dokumen merupakan turunan klinik.'],
 ['PDF kosong / tidak ditemukan','Muat ulang dan pastikan koneksi. Minta pengelola memeriksa file; sertakan nomor dan revisinya.'],
 ['Masih Locked setelah distribusi','Periksa akun login, divisi akun, penerima per orang, dan revisi yang didistribusikan.'],
 ['Approval gagal disimpan','Muat ulang daftar untuk memeriksa status terakhir. Laporkan ke admin bila tetap Pending atau muncul error.'],
 ['AI lambat / gagal / hasil kurang tepat','Coba nama/nomor spesifik atau pencarian biasa. Catatan dokumen yang jelas membantu pencarian.'],
 ['WA tidak masuk','Pastikan opsi WA aktif serta tujuan divisi valid. Minta admin memeriksa layanan WA; distribusi bisa tetap tersimpan.'],
 ['Watermark / timer tidak muncul','Periksa catatan halaman 24-25. Minta admin menguji jalur viewer; jangan berasumsi akses sudah dibatasi.']],[.33,.67])
note(p,'FORMAT LAPORAN KE ADMIN','Sertakan waktu kejadian, akun/divisi, menu, nomor dokumen + revisi, langkah terakhir, dan pesan error. Hindari mengirim password atau token integrasi.')

p=page('Pertanyaan umum / FAQ','Jawaban singkat untuk pekerjaan sehari-hari.','14 / BANTUAN')
table(p,['PERTANYAAN','JAWABAN'],[
 ['Kapan memakai Edit dan kapan Revisi?','Edit untuk koreksi pada versi yang sama. Revisi untuk isi baru yang perlu menyimpan versi lama.'],
 ['Apa beda Revisi dengan Ubah?','Revisi mempertahankan nomor dan menaikkan R. Ubah menjadi dokumen baru membuat nomor baru dan hubungan penggantian.'],
 ['Apakah upload langsung memberi akses semua orang?','Tidak. Distribusi awal ke divisi asal; penerima lain diatur di Distribusi atau melalui approval.'],
 ['Apakah melihat kartu berarti boleh membaca?','Tidak. Locked/Unlocked menunjukkan status akses akun terhadap PDF.'],
 ['Bolehkah distribusi ke orang tanpa memilih divisinya?','Ya. Gunakan Distribusi langsung per orang dan simpan.'],
 ['Mengapa versi lama masih muncul di AI?','Pencarian AI mencakup revisi lama, termasuk yang tidak aktif. Periksa revisi sebelum membaca.'],
 ['Mengapa turunan klinik tidak ada di galeri utama?','Galeri utama saat ini menyaring dokumen non-klinik. Periksa melalui Kelola Dokumen dan koordinasikan akses.'],
 ['Apakah Inactive menutup semua akses?','Tidak dijamin oleh implementasi saat ini. Status dan hak baca perlu ditinjau terpisah.'],
 ['Apakah dokumen lama otomatis mati setelah Ubah?','Tidak pada alur penggantian yang diperiksa. Tinjau status lama secara terpisah.'],
 ['Apakah penerima WA pasti sudah membaca?','Tidak. WA adalah pemberitahuan, dan status notifikasi dibaca bukan bukti memahami isi.'],
 ['Apakah approval selalu satu hari?','Tidak. Durasi bergantung pada data kedaluwarsa dan jalur akses; perlu verifikasi admin.'],
 ['Apakah sistem menyediakan export laporan PDF/Excel?','Tidak ditemukan menu export laporan tersebut pada routes yang ditinjau. Manual ini tidak mengasumsikan fitur itu tersedia.'],
 ['Bisakah dokumen terhapus dipulihkan dari menu?','Tidak ada menu pemulihan yang ditemukan. Hubungi admin untuk menilai kemungkinan pemulihan dari cadangan.']],[.40,.60])

p=page('Glosarium & checklist kerja','Gunakan untuk pemeriksaan sebelum dan sesudah penyimpanan.','15 / REFERENSI CEPAT')
table(p,['ISTILAH','ARTI'],[['Nomor dokumen','Identitas dasar yang dibentuk dari kode jenis/divisi, urutan, dan tahun.'],['R0 / R1 / R2','Versi awal dan urutan revisi berikutnya.'],['Metadata','Nama, jenis, divisi, tanggal, status, dan catatan dokumen.'],['Dokumen pengganti','Dokumen bernomor baru yang terhubung ke dokumen sebelumnya.'],['Turunan klinik','Dokumen khusus klinik dengan hubungan ke dokumen induk.'],['Distribusi','Penetapan divisi/cabang/akun yang menerima akses.'],['Approval','Keputusan atas permintaan akses baca.'],['HRIS','Sumber data karyawan untuk akun yang dihubungkan.'],['Watermark','Penanda visual pada PDF pada jalur yang mendukungnya.']],[.30,.70])
sub(p,'Checklist pengelola')
bullets(p,['PDF, nama, jenis, divisi, tanggal, status, dan catatan sudah benar.','Tindakan yang dipilih sesuai: baru, edit, revisi, penggantian, atau turunan.','Nomor dan revisi hasil penyimpanan sudah diperiksa.','Penerima tiap dokumen sudah diperiksa, termasuk divisi dan orang.','Status dokumen lama ditinjau jika ada revisi atau penggantian.'])
sub(p,'Checklist penerima dan approver')
bullets(p,['Akun yang digunakan adalah akun sendiri dengan divisi yang benar.','Nomor dan revisi cocok dengan kebutuhan kerja.','Approver memeriksa pemohon sebelum mengambil keputusan.','Pengguna membuka ulang galeri untuk melihat keputusan terbaru.'])
note(p,'CHECKLIST ADMIN','Uji role, akses PDF, distribusi, approval, watermark, durasi, dan notifikasi dengan akun biasa. Catat hasil uji pada versi aplikasi yang digunakan organisasi.')

p=page('Lampiran admin: verifikasi & sumber','Catatan untuk memastikan manual sesuai instalasi operasional.','16 / LAMPIRAN')
para(p,'Panduan ini disusun melalui pemeriksaan kode dan tampilan Blade. Diagram adalah visualisasi alur implementasi, bukan screenshot aplikasi. Pengujian semua skenario melalui akun operasional, WA, HRIS, dan AI belum dilakukan.')
table(p,['AREA','VERIFIKASI SEBELUM DIGUNAKAN LUAS'],[
 ['Permintaan akses','Cocokkan nama kolom migration dengan model/controller. Migration awal memakai nama berbeda; constraint status huruf besar juga berbeda dari nilai huruf kecil yang ditulis controller.'],
 ['Durasi akses','Selaraskan perilaku endpoint upload dan galeri. Pastikan approval benar-benar menyimpan kedaluwarsa jika akses berbatas waktu diperlukan.'],
 ['Pengaturan akses','Perbaiki/validasi tujuan redirect setelah penyimpanan Document Access. Nilai dapat sudah tersimpan walaupun halaman berikutnya error.'],
 ['Watermark & file','Uji jalur galeri yang mengirim PDF asli. Periksa akses penyimpanan publik serta perilaku fallback tanpa watermark.'],
 ['Revisi klinik','Verifikasi penyimpanan clinic_id dan nomor urut pada jalur revisi terpisah.'],
 ['Role dan lingkup','Uji kesesuaian role utama, permission, lingkup divisi, dan tindakan pada modul yang hanya memeriksa view.'],
 ['WhatsApp / notifikasi','Selaraskan label field grup, konfigurasi tujuan, status kirim, akses feed notifikasi, dan perilaku keputusan approval.'],
 ['Pencarian AI','Tinjau metadata yang dikirim ke penyedia, verifikasi TLS, lingkup hasil, dan escaping tampilan hasil.']],[.25,.75])
sub(p,'Dasar penyusunan')
para(p,'Sumber internal: routes/web.php; controller Documents, Master, Access, Settings dan dashboard; model Document dan model pendukung; service pencarian, notifikasi, dan watermark; tampilan Blade tiap menu; resources/menu/verticalMenu.json; migration; serta REVERB.md.')
para(p,'Acuan penyajian: <b>OneAsset.pdf</b>, 16 halaman, khusus untuk pola manual dan diagram. Fitur OneAsset yang tidak ada pada Sistem Dokumen tidak dipindahkan ke panduan ini.')
note(p,'KONTROL DOKUMEN','Edisi 1.1 - 17 September 2026. Tinjau ulang setelah perubahan menu, label tombol, permission, alur akses, atau konfigurasi integrasi. Catatan lampiran ini ditujukan kepada admin dan tim pengembang.')

# Detailed operational reference - added for edition 1.1.
p=page('Referensi tombol & form','Cara membaca panduan rinci pada halaman berikutnya.','17 / REFERENSI MENU')
para(p,'Bagian ini menjelaskan menu berdasarkan tampilan aplikasi saat ini. Gunakan sebagai pendamping langkah utama: cari menu, isi field sesuai tabel, pilih tombol, lalu periksa hasilnya.')
table(p,['PENANDA','ARTI'],[
 ['Wajib','Field harus diisi sebelum form dapat disimpan.'],
 ['Opsional','Field boleh kosong jika tidak dibutuhkan.'],
 ['Simpan','Mengirim perubahan ke sistem. Tunggu pesan sukses atau cek ulang daftar.'],
 ['Batal / Reset','Menutup form atau mengembalikan pilihan yang belum disimpan.'],
 ['Edit','Mengubah data yang sudah ada. Periksa dampak perubahan sebelum menyimpan.'],
 ['Hapus','Menghapus data. Hanya lakukan setelah memeriksa item yang tepat.'],
 ['Filter / Search','Membatasi daftar; tidak mengubah data.'],
 ['Active','Menandai data aktif. Arti detailnya dapat berbeda menurut menu.'],
],[.27,.73])
sub(p,'Aturan umum sebelum menekan Simpan')
bullets(p,['Baca judul modal/form dan nomor dokumen sebelum mengubah data.','Periksa field merah/wajib serta pesan validasi setelah simpan.','Jangan menekan Simpan berkali-kali saat halaman masih memproses.','Setelah muncul pesan sukses, cari kembali data pada daftar untuk memastikan nilai benar.','Gunakan screenshot atau catat nomor dokumen/revisi bila meminta bantuan administrator.'])
note(p,'BAHASA TOMBOL','Sebagian label aplikasi masih berbahasa Inggris, misalnya Add Document, Choose Documents, View, Approve, dan Reject. Manual menjelaskan fungsi sesuai label yang tampil.')

p=page('Menu Login & Dashboard','Referensi tombol dan informasi yang tersedia setelah masuk.','18 / MENU RINCI')
table(p,['AREA / TOMBOL','CARA PAKAI & HASIL'],[
 ['Username - wajib','Masukkan username akun, bukan email kecuali administrator memang membuat username berbentuk email.'],
 ['Password - wajib','Masukkan password akun. Karakter tidak akan ditampilkan pada layar.'],
 ['Remember - opsional','Menjaga sesi login pada perangkat yang sama. Jangan gunakan di komputer bersama.'],
 ['Masuk / Login','Memvalidasi kredensial dan status Active. Jika berhasil, aplikasi membuka Dashboard atau halaman tujuan sebelumnya.'],
 ['Logout','Menutup sesi akun. Gunakan sebelum meninggalkan komputer.'],
 ['Dashboard','Menyajikan ringkasan berbeda menurut role. Superadmin melihat statistik; pengguna lain melihat dokumen sesuai akses.'],
 ['Filter dashboard','Jika tersedia, pilih nilai lokasi/jenis/status lalu periksa tabel sebelum membuat keputusan. Filter tidak mengubah data.'],
],[.31,.69])
sub(p,'Data yang diperiksa pada dashboard Superadmin')
para(p,'Total/aktif/tidak aktif, dokumen bulan dan tahun berjalan, jumlah revisi, permintaan akses menurut status, jumlah master data aktif, serta ringkasan dokumen menurut divisi dan jenis. Angka adalah ringkasan dan perlu dibuka ke daftar dokumen untuk detailnya.')
note(p,'LOGIN GAGAL','Periksa ejaan username dan password, lalu pastikan akun masih Active. Jika gagal berulang kali, hubungi administrator; jangan mencoba kredensial milik pengguna lain.','warn')

p=page('Menu Master Data > Document Types','Menambah dan memelihara jenis dokumen.','18 / MENU RINCI')
steps(p,['Buka <b>Master Data > Document Types</b>. Gunakan kotak pencarian bila daftar panjang.','Klik tombol tambah jenis dokumen.','Isi field sesuai tabel, lalu simpan.','Gunakan Edit pada baris yang benar untuk koreksi. Gunakan Hapus hanya jika sudah dipastikan tidak dibutuhkan.','Periksa status Active sebelum menggunakan jenis tersebut pada form Add Document.'])
table(p,['FIELD / TOMBOL','INPUT / FUNGSI'],[
 ['Kode - wajib','Kode singkat, misalnya SOP, SK, atau POL. Kode masuk ke nomor dokumen.'],
 ['Nama - wajib','Nama jenis yang mudah dimengerti, misalnya Standar Operasional Prosedur.'],
 ['Deskripsi - opsional','Penjelasan singkat tentang penggunaan jenis dokumen.'],
 ['Status - wajib','Pilih Active agar jenis dapat dipilih saat upload; pilih Inactive untuk menghentikan pemakaian baru.'],
 ['Simpan','Membuat atau memperbarui jenis dokumen.'],
 ['Edit','Membuka data jenis dokumen pada form perubahan.'],
 ['Hapus','Menghapus jenis dokumen setelah konfirmasi.']
],[.31,.69])
note(p,'KODE JENIS','Jangan mengganti kode sembarangan karena kode dipakai dalam nomor dokumen baru. Periksa standar penamaan organisasi sebelum membuat kode tambahan.')

p=page('Menu Master Data > Divisi','Membuat divisi utama dan detail/cabang penerima.','18 / MENU RINCI')
sub(p,'A. Form Add Divisi')
table(p,['FIELD','INPUT YANG DIISI'],[
 ['Jenis Office - wajib','Pilih Holding atau DJC sesuai struktur organisasi.'],
 ['Jenis Divisi (WA) - wajib','Pilih Personal untuk nomor individu atau Group untuk tujuan grup yang dikonfigurasi admin.'],
 ['Fonnte API Token - wajib pada form','Isi hanya sesuai arahan administrator integrasi. Jangan memasukkan token pribadi.'],
 ['Code - wajib','Kode singkat divisi, misalnya HRD, FIN, LEG.'],
 ['Name - wajib','Nama lengkap divisi.'],
 ['Description - opsional','Keterangan fungsi divisi.'],
 ['No. WhatsApp - opsional','Nomor tujuan jika pola notifikasi divisi menggunakannya.'],
 ['Status - wajib','Active atau Inactive.']
],[.32,.68])
sub(p,'B. Tombol pada daftar divisi')
table(p,['TOMBOL','HASIL'],[
 ['Add Divisi','Membuka form divisi utama.'],['Tambah Detail','Membuka form cabang/detail di bawah divisi induk.'],['Edit Divisi','Mengubah data divisi utama.'],['Edit Detail','Mengubah data cabang/detail.'],['Hapus','Menghapus entitas yang dipilih setelah konfirmasi.'],['Search / Clear','Mencari atau membersihkan filter daftar.']],[.32,.68])
note(p,'DETAIL CABANG','Saat menambah detail, nama detail wajib dan harus berada di bawah divisi induk yang benar. Periksa struktur ini kembali ketika memilih cabang di Distribusi.')

p=page('Menu Master Data > Klinik','Membuat klinik untuk dokumen turunan.','18 / MENU RINCI')
steps(p,['Buka <b>Master Data > Klinik</b>.','Gunakan Search untuk mencari berdasarkan code, name, atau phone.','Klik <b>Add Klinik</b>.','Isi Code dan Name, lalu isi Address/Phone bila diperlukan.','Pilih Status, klik Simpan, lalu periksa data pada tabel.'])
table(p,['FIELD / TOMBOL','INPUT / FUNGSI'],[
 ['Code - wajib','Kode unik klinik, misalnya KLINIK01. Kode menjadi akhiran nomor dokumen turunan.'],
 ['Name - wajib','Nama klinik.'],
 ['Address - opsional','Alamat klinik.'],
 ['Phone - opsional','Nomor telepon/WhatsApp klinik dalam format yang dipakai organisasi.'],
 ['Status','Pilih Active agar klinik tersedia dalam form Turunkan Dokumen ke Klinik.'],
 ['Edit','Memperbaiki data klinik yang dipilih.'],
 ['Hapus','Menghapus klinik setelah memeriksa hubungan dokumen turunannya.']
],[.31,.69])
note(p,'KLINIK AKTIF','Klinik Inactive tidak dapat dipilih sebagai tujuan turunan baru. Sebelum menonaktifkan klinik, periksa dampaknya terhadap pekerjaan yang masih berjalan.')

p=page('Menu Kelola Dokumen: daftar & filter','Memahami tombol pada halaman daftar dokumen.','19 / KELOLA DOKUMEN RINCI')
table(p,['KONTROL','CARA PAKAI'],[
 ['Jenis Dokumen','Pilih satu jenis untuk membatasi daftar. Kosongkan untuk semua jenis.'],
 ['Divisi','Pilih satu divisi untuk membatasi daftar yang ditampilkan.'],
 ['Cari Dokumen','Masukkan nama, nomor dokumen, kode/nama jenis, atau kode/nama divisi.'],
 ['Tombol Search','Menerapkan seluruh filter dan kata pencarian.'],
 ['Tombol Clear','Menghapus filter dan kembali ke daftar semula.'],
 ['Tambah Dokumen','Membuka modal Add Document untuk dokumen baru R0.'],
 ['Header nomor / panah','Membuka atau menutup kelompok versi untuk nomor dokumen tersebut.'],
 ['Ikon mata / View','Membuka file dokumen pada jalur Kelola Dokumen sesuai izin fitur.'],
 ['Pilih Tindakan','Membuka menu aksi untuk baris dokumen yang dipilih. Isi menu mengikuti permission akun.']
],[.31,.69])
sub(p,'Kolom daftar')
para(p,'Revisi menunjukkan R0/R1 dan seterusnya. Kolom Dokumen menunjukkan nama serta nomor. Kolom Jenis, Divisi & Tanggal membantu memastikan identitas. Status menunjukkan Active/Inactive. Perhatikan label Diubah dari atau Diganti oleh agar tidak memakai dokumen yang sudah tergantikan.')
note(p,'PILIH BARIS, BUKAN HANYA NOMOR','Satu nomor dapat memiliki banyak revisi. Selalu cocokkan R pada baris sebelum memilih View, Edit, Distribusi, atau Hapus.')

p=page('Form Add Document','Input lengkap untuk membuat dokumen baru R0.','19 / KELOLA DOKUMEN RINCI')
table(p,['FIELD','INPUT YANG DIISI'],[
 ['Document Type - wajib','Pilih jenis dokumen aktif. Nilainya menentukan bagian awal nomor dokumen.'],
 ['Divisi - wajib','Pilih divisi pemilik. Nilainya menentukan bagian divisi pada nomor dokumen.'],
 ['Document Name - wajib','Isi judul dokumen yang jelas.'],
 ['Publish Date - wajib','Pilih tanggal terbit/berlaku. Tahun dari tanggal ini dipakai dalam nomor.'],
 ['Upload Document (PDF) - wajib','Pilih satu file PDF, maksimal 10 MB.'],
 ['Catatan - opsional','Tulis ringkasan isi, ruang lingkup, atau informasi penting perubahan.'],
 ['Active','Biarkan aktif jika dokumen siap digunakan. Matikan bila ingin menyimpan sebagai tidak aktif.'],
 ['Batal','Menutup form tanpa menyimpan.'],
 ['Simpan','Membuat dokumen dan nomor otomatis dengan revisi R0.']
],[.34,.66])
sub(p,'Setelah tombol Simpan')
steps(p,['Tunggu pesan sukses atau pesan validasi.','Cari dokumen baru pada daftar dan buka kelompok nomor bila perlu.','Cocokkan jenis, divisi, nomor, R0, tanggal, status, catatan, dan PDF.','Pilih <b>Atur distribusi</b> bila penerima perlu ditentukan di luar divisi asal.'])
note(p,'PILIH PDF YANG BENAR','Aplikasi menyimpan file yang dipilih saat submit. Buka kembali hasilnya sesudah upload; nama file lokal bukan jaminan isi PDF sudah tepat.')

p=page('Menu Pilih Tindakan pada dokumen','Arti setiap tindakan pada satu baris dokumen.','19 / KELOLA DOKUMEN RINCI')
table(p,['AKSI','FUNGSI & PEMERIKSAAN'],[
 ['Atur distribusi','Membuka Distribusi dengan dokumen tersebut sudah dipilih. Periksa nomor dan revisi sebelum mengubah penerima.'],
 ['Buat revisi R...','Membuka halaman Revisions untuk membuat versi berikutnya dengan nomor dasar tetap.'],
 ['Ubah menjadi dokumen baru','Membuka form dokumen pengganti; sistem membuat nomor baru dan hubungan ke dokumen sumber.'],
 ['Buat dokumen turunan','Membuka form khusus untuk klinik. Pilih klinik dan unggah PDF khusus klinik.'],
 ['Edit metadata','Mengubah jenis, divisi, nama, tanggal, status, catatan, dan optional file pada versi saat ini.'],
 ['Hapus dokumen','Menampilkan konfirmasi lalu menghapus record/file versi tersebut.']
],[.34,.66])
sub(p,'Form Ubah menjadi dokumen baru')
para(p,'Form menggunakan Document Type, Divisi, Document Name, Publish Date, Catatan, Upload Document (PDF), dan Active. Isi seperti dokumen baru, tetapi jangan menganggap nomor lama dipakai kembali. Sistem membentuk nomor baru dan menghubungkannya dengan dokumen sumber.')
sub(p,'Form Turunkan Dokumen ke Klinik')
para(p,'Pilih Klinik - wajib; isi Document Name - wajib; Publish Date - wajib; Catatan - opsional; pilih Upload Document (PDF) - wajib; atur Active. Tombol Batal menutup form; Simpan membuat turunan R0.')
note(p,'KAPAN MEMILIH AKSI','Gunakan Revisi untuk versi berikutnya, Ubah untuk identitas dokumen baru, Turunan untuk klinik, Edit untuk metadata versi yang sama, dan Hapus hanya untuk penghapusan yang sudah disetujui.','warn')

p=page('Menu Revisions','Membuat versi baru dan memeriksa riwayat.','20 / REVISI RINCI')
table(p,['KONTROL','CARA PAKAI'],[
 ['Document Type / Department','Filter daftar revisi menurut jenis atau divisi.'],
 ['Search','Mencari nama atau nomor dokumen.'],
 ['Accordion nomor','Membuka riwayat revisi per nomor dasar.'],
 ['Buat Revisi','Membuka modal setelah basis dokumen dipilih.'],
 ['View','Membuka file revisi.'],
 ['Download','Mengunduh file jika tombol tersedia untuk akun.']
],[.31,.69])
table(p,['FIELD FORM BUAT REVISI','INPUT YANG DIISI'],[
 ['Nama Dokumen Revisi - wajib','Judul versi terbaru.'],['Tanggal Berlaku - wajib','Tanggal versi revisi mulai berlaku.'],['Status','Active untuk versi yang akan digunakan.'],['File Revisi (PDF) - wajib','File PDF pengganti maksimal 10 MB.'],['Keterangan Dokumen - opsional','Ringkasan perubahan dari versi sebelumnya.'],['Batal','Menutup form tanpa menyimpan.'],['Simpan','Membuat R berikutnya dan menonaktifkan seluruh versi lama dengan nomor dasar sama.']
],[.42,.58])
note(p,'HASIL REVISI','Penerima divisi dan pengguna dari dokumen dasar disalin ke versi baru. Selalu periksa apakah distribusi tersebut masih benar setelah revisi dibuat.')

p=page('Menu Distribusi: memilih dokumen','Langkah awal sebelum memilih penerima.','21 / DISTRIBUSI RINCI')
steps(p,['Buka <b>Document Control > Distribusi</b>.','Gunakan Search bila perlu untuk menemukan nomor atau nama dokumen.','Pada <b>Choose Documents (multi)</b>, pilih satu atau beberapa dokumen aktif.','Periksa setiap chip/opsi pilihan: nomor, revisi, nama, dan divisi.','Setelah dokumen dipilih, halaman menampilkan panel penerima untuk masing-masing dokumen.'])
table(p,['KONTROL','FUNGSI'],[
 ['Search document number or name','Membatasi opsi dokumen yang bisa dipilih.'],
 ['Choose Documents','Memilih satu atau banyak dokumen yang distribusinya akan diatur.'],
 ['Select Documents','Bagian yang menampilkan pilihan aktif.'],
 ['No Document Selected','Petunjuk bahwa belum ada dokumen untuk diatur.'],
 ['Informasi / Mengerti','Dialog bantuan distribusi jika muncul pada halaman.']
],[.36,.64])
note(p,'DOKUMEN AKTIF SAJA','Pilihan distribusi memvalidasi dokumen aktif. Jika dokumen tidak muncul, periksa status Active dan gunakan Kelola Dokumen untuk melihat versinya.')

p=page('Menu Distribusi: penerima & tombol simpan','Mengisi pilihan per dokumen secara aman.','21 / DISTRIBUSI RINCI')
table(p,['KONTROL','CARA PAKAI & HASIL'],[
 ['Tab Holding / DJC / lainnya','Pilih kelompok organisasi untuk menemukan divisi penerima.'],
 ['Badge / checkbox divisi','Klik untuk memilih atau melepas satu divisi. Warna/centang menunjukkan pilihan saat ini.'],
 ['Tanda + / Pilih Cabang','Membuka modal cabang/detail dari divisi agar cabang tertentu bisa dipilih.'],
 ['Checkbox cabang','Tandai cabang yang menerima dokumen. Gunakan simpan/tutup modal sesuai petunjuk.'],
 ['Distribusi langsung per orang','Cari dan pilih nama/username akun untuk memberi akses personal.'],
 ['Kirim notifikasi WhatsApp','Aktif: mencoba mengirim WA ke tujuan distribusi divisi. Mati: hanya menyimpan penerima.'],
 ['Reset','Memuat ulang pilihan distribusi tersimpan dan membuang perubahan belum disimpan.'],
 ['Simpan & Distribusikan','Menyimpan penerima seluruh dokumen pilihan dan menjalankan notifikasi yang dipilih.']
],[.36,.64])
sub(p,'Pemeriksaan sebelum Simpan & Distribusikan')
bullets(p,['Periksa panel untuk setiap dokumen, bukan hanya dokumen pertama.','Periksa pilihan divisi induk dan cabang/detail yang terbuka.','Periksa akun pada distribusi langsung.','Periksa sakelar WhatsApp sesuai kebutuhan.','Ingat bahwa penyimpanan mengganti daftar penerima lama untuk dokumen tersebut.'])
note(p,'JIKA HANYA INGIN MENAMBAH SATU PENERIMA','Buka dokumen dan periksa penerima yang sudah tercentang sebelum menambah. Menyimpan pilihan yang tidak lengkap dapat menghapus distribusi sebelumnya.','warn')

p=page('Menu Lihat Dokumen / Gallery','Memakai filter, kartu dokumen, dan tindakan akses.','22 / GALERI RINCI')
table(p,['KONTROL','CARA PAKAI'],[
 ['Pencarian nama/nomor','Ketik kata yang spesifik, misalnya kode atau sebagian judul.'],
 ['Filter jenis dokumen','Menyempitkan kartu ke satu jenis.'],
 ['Filter divisi','Menyempitkan kartu ke divisi tertentu.'],
 ['Clear filters','Mengembalikan daftar ke kondisi tanpa filter.'],
 ['Kartu dokumen','Periksa judul, nomor, R, jenis, divisi, status, tanggal, dan badge akses.'],
 ['Open','Membuka viewer jika status Unlocked.'],
 ['Tindakan Locked','Memulai atau menampilkan permintaan akses bila akun belum berhak.'],
 ['Halaman pagination','Membuka halaman kartu berikutnya/ sebelumnya.']
],[.31,.69])
sub(p,'Pencarian AI')
table(p,['KONTROL','CARA PAKAI'],[['Kolom AI search','Tulis kebutuhan dalam kata biasa; gunakan nama/nomor spesifik bila ada.'],['Tombol cari','Mengirim query dan menampilkan kandidat relevan.'],['Hasil AI','Periksa nomor, R, divisi, dan catatan sebelum klik. Hasil tidak otomatis berarti Anda memiliki akses.']],[.31,.69])
note(p,'KARTU LOCKED','Locked tidak selalu berarti dokumen rahasia bagi seluruh organisasi; artinya akun yang sedang dipakai belum memenuhi aturan akses untuk dokumen itu.')

p=page('Menu Viewer PDF & halaman Pending','Tombol saat membaca dan status permintaan akses.','22 / GALERI RINCI')
table(p,['TOMBOL / AREA','FUNGSI'],[
 ['Halaman sebelumnya (<)','Membuka halaman PDF sebelumnya jika tersedia.'],
 ['Nomor halaman','Menunjukkan posisi Halaman X / total halaman.'],
 ['Halaman berikutnya (>)','Membuka halaman PDF berikutnya jika tersedia.'],
 ['Zoom minus (-)','Memperkecil tampilan halaman PDF.'],
 ['Zoom plus (+)','Memperbesar tampilan halaman PDF.'],
 ['Sisa waktu akses','Ditampilkan bila viewer menerima batas waktu akses approval.'],
 ['Modal waktu habis','Menjelaskan akses telah habis; tekan tombol konfirmasi lalu kembali ke galeri.'],
 ['Request Document Access','Halaman yang ditampilkan saat aplikasi membuat/menemukan permintaan pending.']
],[.34,.66])
sub(p,'Yang dilakukan saat halaman Pending')
steps(p,['Periksa Document Information dan pastikan nomor/revisi sesuai kebutuhan.','Jangan membuat permintaan duplikat melalui akun lain.','Tunggu approver memproses dari menu Persetujuan.','Muat ulang atau buka kembali dokumen setelah mendapat informasi keputusan.'])
note(p,'TIMER DAN WATERMARK','Ketersediaan keduanya bergantung jalur pembaca dan konfigurasi. Jika dokumen memerlukan pembatasan ketat, konfirmasi kepada administrator sebelum menyebarkannya.')

p=page('Menu Persetujuan Akses','Meninjau dan memutuskan request yang masuk.','23 / PERSETUJUAN RINCI')
table(p,['KONTROL','CARA PAKAI'],[
 ['Filter status','Pilih Semua status, Pending, Approved, atau Rejected untuk membatasi daftar.'],
 ['User','Nama/username pemohon. Pastikan identitas sesuai.'],
 ['Department','Divisi pemohon yang tercatat.'],
 ['Document','Nomor dan nama dokumen yang diminta.'],
 ['Reason','Alasan bila tersedia pada request.'],
 ['Requested At','Waktu permintaan dibuat.'],
 ['Status','Keadaan request sekarang.'],
 ['Decision','Nama dan waktu pengambil keputusan sesudah diproses.'],
 ['Approve','Menyetujui request Pending.'],
 ['Reject','Menolak request Pending.']
],[.31,.69])
sub(p,'Urutan keputusan')
steps(p,['Filter Pending.','Baca seluruh kolom request.','Validasi kebutuhan dengan prosedur internal.','Klik Approve atau Reject satu kali.','Periksa perubahan badge Status dan kolom Decision.','Beritahu pemohon untuk membuka ulang galeri.'])
note(p,'TIDAK ADA TOMBOL PEMBATALAN','Setelah request diproses, halaman tidak menyediakan tombol keputusan ulang. Jangan mengklik tanpa pemeriksaan identitas dan dokumen.')

p=page('Menu Users','Form tambah, edit, filter, dan status akun.','24 / USER ACCESS RINCI')
table(p,['FIELD','INPUT YANG DIISI'],[
 ['Karyawan HRIS - opsional','Pilih karyawan bila akun harus mengambil identitas dari HRIS.'],
 ['Full Name','Wajib jika tidak memakai HRIS.'],
 ['Username','Wajib jika tidak memakai HRIS; harus unik.'],
 ['Email','Wajib jika tidak memakai HRIS; harus unik dan format email benar.'],
 ['Nomor WhatsApp - opsional','Nomor pengguna bila dibutuhkan organisasi.'],
 ['Department - opsional','Divisi akun; tentukan secara sengaja karena memengaruhi lingkup dokumen.'],
 ['Role - opsional','Role utama akun. Pilih role yang sudah memiliki permission tepat.'],
 ['Password / confirmation','Wajib untuk akun lokal baru. Saat edit, kosongkan untuk mempertahankan password lama.'],
 ['Active','Aktifkan agar akun dapat login.']
],[.35,.65])
table(p,['TOMBOL / FILTER','FUNGSI'],[['Department, Status, Search','Membatasi daftar pengguna.'],['Tambah / Save','Membuat akun baru.'],['Edit','Membuka data pengguna pada form edit.'],['Hapus','Menghapus akun sesuai tindakan yang tersedia.'],['Clear','Menghapus filter daftar.']],[.35,.65])
note(p,'AKUN HRIS','Identitas akun HRIS mengikuti sumber HRIS ketika halaman Users melakukan sinkronisasi. Jangan menganggap perubahan lokal pada nama/email/password akun ini akan permanen.')

p=page('Menu Roles & Permission Settings','Membuat role dan menandai izin per fitur.','24 / USER ACCESS RINCI')
sub(p,'A. Roles')
table(p,['FIELD / TOMBOL','INPUT / FUNGSI'],[['Nama Role','Nama role, misalnya Pengelola Dokumen atau Approver.'],['Guard Name','Gunakan web untuk akun aplikasi web.'],['Simpan','Membuat atau memperbarui role.'],['Edit','Memperbaiki role yang dipilih.'],['Hapus','Menghapus role setelah memeriksa penggunaan akun.']],[.32,.68])
sub(p,'B. Permission Settings')
table(p,['KONTROL','CARA PAKAI'],[['Pilih Role','Pilih role yang akan menerima izin.'],['Kelompok permission','Buka accordion sesuai modul: master, dokumen, akses, settings, dan lain-lain.'],['Checkbox permission','Centang izin tindakan yang dibutuhkan.'],['Check All','Menandai seluruh permission yang terlihat; gunakan dengan sangat hati-hati.'],['Simpan / Update','Menyimpan set permission untuk role pilihan.']],[.32,.68])
note(p,'SETELAH MENGUBAH PERMISSION','Minta pengguna logout lalu login kembali atau muat ulang halaman. Uji menu menggunakan akun role tersebut, bukan hanya akun Superadmin.')

p=page('Menu Settings: Watermark / DRM','Form pengaturan watermark secara rinci.','25 / SETTINGS RINCI')
table(p,['FIELD','INPUT YANG DIISI'],[
 ['Enable watermark','Centang untuk mengaktifkan watermark pada jalur yang mendukungnya.'],
 ['Mode','Pilih Text atau Image.'],
 ['Font Size','Angka 8 sampai 120 untuk mode teks.'],
 ['Rotation','Sudut -180 sampai 180.'],
 ['Opacity','Angka 0 sampai 100.'],
 ['Position','Center, Top Left, Top Right, Bottom Left, atau Bottom Right.'],
 ['Color (HEX)','Format #RRGGBB atau #RRGGBBAA, contoh #A0A0A0.'],
 ['Repeat / diagonal pattern','Centang untuk pola watermark berulang.'],
 ['Text Template','Teks watermark. Gunakan variabel yang didukung bila diperlukan.'],
 ['Watermark Image','PNG/JPG/JPEG maksimal 2 MB untuk mode Image.'],
 ['Show on download','Opsi penerapan watermark pada jalur unduh yang mendukungnya.'],
 ['Save / Update','Menyimpan seluruh pengaturan.']
],[.36,.64])
sub(p,'Variabel teks yang dapat dipakai')
para(p,'<b>{user.name}</b>, <b>{user.username}</b>, <b>{date}</b>, <b>{datetime}</b>, <b>{doc.name}</b>, <b>{doc.number}</b>, <b>{doc.revision}</b>, <b>{doc.dept}</b>, dan <b>{doc.type}</b>. Gunakan dokumen uji untuk memastikan hasilnya.')
note(p,'JANGAN MENYIMPAN KREDENSIAL','Template watermark boleh memuat penanda identitas pembaca sesuai kebijakan, tetapi jangan memuat password, token, atau data pribadi yang tidak diperlukan.')

p=page('Menu Settings: Document Access & Framework','Mengatur durasi dan memahami tombol akhir.','25 / SETTINGS RINCI')
table(p,['KONTROL','CARA PAKAI'],[
 ['Enabled','Centang untuk mengaktifkan pengaturan document access expiry.'],
 ['Default Duration (minutes) - wajib','Masukkan bilangan menit minimal 1. Contoh: 60, 480, atau 1440.'],
 ['Simpan','Menyimpan pengaturan. Setelah klik, buka kembali halaman untuk memastikan nilai tersimpan.'],
 ['Framework System','Membuka halaman diagram framework internal.'],
 ['Diagram framework','Hanya untuk dibaca. Jika tidak tampil, laporkan ke administrator.']
],[.38,.62])
sub(p,'Pemeriksaan perubahan durasi')
steps(p,['Catat nilai lama dan baru.','Simpan satu kali.','Buka kembali Settings > Document Access untuk memeriksa nilai.','Gunakan akun pemohon dan approver uji untuk memeriksa apakah viewer menerima waktu kedaluwarsa.','Catat hasil sebelum menerapkan kebijakan akses berbatas waktu ke seluruh pengguna.'])
note(p,'PESAN ERROR SESUDAH SIMPAN','Jika setelah simpan muncul error halaman, jangan segera memasukkan nilai berulang kali. Nilai mungkin sudah tersimpan. Buka ulang halaman dan minta admin memeriksa konfigurasi route bila masalah berlanjut.','warn')

p=page('Checklist menu per pekerjaan','Ringkasan tombol utama sebelum mengakhiri pekerjaan.','26 / REFERENSI CEPAT')
table(p,['PEKERJAAN','MENU','TOMBOL / HASIL YANG DIPERIKSA'],[
 ['Dokumen baru','Kelola Dokumen','Tambah Dokumen > isi form > Simpan > nomor R0 dan PDF benar.'],
 ['Perbaiki metadata','Kelola Dokumen','Pilih Tindakan > Edit metadata > Simpan > baris versi benar.'],
 ['Isi versi baru','Kelola Dokumen / Revisions','Buat revisi > PDF revisi > Simpan > R bertambah dan versi lama tidak aktif.'],
 ['Dokumen pengganti','Kelola Dokumen','Ubah menjadi dokumen baru > Simpan > nomor baru dan hubungan benar.'],
 ['Turunan klinik','Kelola Dokumen','Buat dokumen turunan > pilih Klinik > Simpan > kode klinik benar.'],
 ['Bagikan dokumen','Distribusi','Choose Documents > pilih penerima > Simpan & Distribusikan.'],
 ['Baca dokumen','Lihat Dokumen','Filter/Search > periksa R & status > Open atau ajukan akses.'],
 ['Putuskan akses','Persetujuan','Filter Pending > periksa data > Approve/Reject > status berubah.'],
 ['Buat akun','Users','isi akun/HRIS + divisi + role + Active > Simpan.'],
 ['Atur izin','Permission Settings','Pilih Role > checkbox permission > Simpan/Update > uji akun.'],
 ['Atur watermark','Watermark/DRM','isi mode/pengaturan > Save > uji PDF.'],
],[.22,.23,.55])
note(p,'VERSI 1.1','Bagian referensi menu ini ditambahkan untuk menjelaskan field form dan tombol secara operasional. Tinjau ulang manual setelah perubahan antarmuka aplikasi.')

class Diagram(Flowable):
    def __init__(self, caption, lanes, rows, edges=None):
        Flowable.__init__(self); self.caption=caption;self.lanes=lanes;self.rows=rows
        self.width=CW;self.rh=43;self.height=34+len(rows)*self.rh+28
    def draw(self):
        c=self.canv;n=len(self.lanes);lw=CW/n;top=self.height-21
        c.setFillColor(PALE);c.roundRect(0,21,CW,self.height-21,6,fill=1,stroke=0)
        for j,l in enumerate(self.lanes):
            c.setFillColor(NAVY);c.rect(j*lw,top-24,lw,24,fill=1,stroke=0)
            q=Paragraph(escape(l),ParagraphStyle('lane',parent=styles['head'],alignment=1,fontSize=8.3));_,h=q.wrap(lw-10,30);q.drawOn(c,j*lw+5,top-12-h/2)
            if j:c.setStrokeColor(LINE);c.line(j*lw,24,j*lw,top-24)
        centers=[];nw=min(lw-20,200);nh=32
        for i,(lane,t) in enumerate(self.rows):
            x=lane*lw+lw/2;y=top-46-i*self.rh;centers.append((x,y))
        c.setStrokeColor(colors.HexColor('#8597A7'));c.setLineWidth(.8)
        for i in range(len(centers)-1):
            x,y=centers[i];xx,yy=centers[i+1];start=y-nh/2;end=yy+nh/2
            if x==xx:c.line(x,start,xx,end)
            else:
                mid=(start+end)/2;c.line(x,start,x,mid);c.line(x,mid,xx,mid);c.line(xx,mid,xx,end)
            c.setFillColor(colors.HexColor('#8597A7'));path=c.beginPath();path.moveTo(xx,end);path.lineTo(xx-3,end+5);path.lineTo(xx+3,end+5);path.close();c.drawPath(path,fill=1,stroke=0)
        for i,((lane,t),(x,y)) in enumerate(zip(self.rows,centers)):
            c.setStrokeColor(BLUE if lane!=1 else TEAL);c.setFillColor(colors.white if lane!=1 else colors.HexColor('#EAF5F4'))
            c.roundRect(x-nw/2,y-nh/2,nw,nh,5,fill=1,stroke=1)
            q=Paragraph(escape(t).replace('\n','<br/>'),styles['node']);_,h=q.wrap(nw-12,40);q.drawOn(c,x-nw/2+6,y-h/2)
        q=Paragraph(escape(self.caption),ParagraphStyle('caption',parent=styles['small'],alignment=1,fontName='Italic'))
        _,h=q.wrap(CW,30);q.drawOn(c,0,1)

def P(s,kind='body'):return Paragraph(s,styles[kind])
def make_table(heads,rows,widths):
    widths=widths or [1/len(heads)]*len(heads)
    data=[[P(escape(str(x)),'head') for x in heads]]+[[P(escape(str(x)),'cell') for x in row] for row in rows]
    t=Table(data,colWidths=[CW*x for x in widths],hAlign='LEFT')
    commands=[('BACKGROUND',(0,0),(-1,0),NAVY),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),9),('RIGHTPADDING',(0,0),(-1,-1),9),('TOPPADDING',(0,0),(-1,-1),6),('BOTTOMPADDING',(0,0),(-1,-1),6),('LINEBELOW',(0,0),(-1,0),.5,NAVY)]
    for i in range(1,len(data)):commands.extend([('BACKGROUND',(0,i),(-1,i),PALE if i%2 else colors.white),('LINEBELOW',(0,i),(-1,i),.35,LINE)])
    t.setStyle(TableStyle(commands));return t

def blocks(p):
    out=[]
    for kind,data in p['blocks']:
        if kind=='p':out.append((P(data),7))
        elif kind=='h':out.append((P(data,'h2'),8))
        elif kind=='step':
            i,s=data;t=Table([[P(f'<b>{i:02d}</b>','cell'),P(s)]],colWidths=[26,CW-26]);t.setStyle(TableStyle([('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),0),('RIGHTPADDING',(0,0),(-1,-1),2),('TOPPADDING',(0,0),(-1,-1),0),('BOTTOMPADDING',(0,0),(-1,-1),0)]));out.append((t,6))
        elif kind=='bullet':
            t=Table([[P('<b>-</b>'),P(data)]],colWidths=[14,CW-14]);t.setStyle(TableStyle([('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),0),('RIGHTPADDING',(0,0),(-1,-1),0),('TOPPADDING',(0,0),(-1,-1),0),('BOTTOMPADDING',(0,0),(-1,-1),0)]));out.append((t,5))
        elif kind=='table':out.append((make_table(*data),12))
        elif kind=='flow':out.append((Diagram(*data),13))
        elif kind=='note':
            title,s,k=data;bg='#FFF5DB' if k=='warn' else '#EAF2F8';accent=GOLD if k=='warn' else BLUE
            t=Table([[P(f'<b>{escape(title)}</b><br/>{s}','box')]],colWidths=[CW]);t.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),colors.HexColor(bg)),('LINEBEFORE',(0,0),(0,0),3,accent),('LEFTPADDING',(0,0),(-1,-1),11),('RIGHTPADDING',(0,0),(-1,-1),11),('TOPPADDING',(0,0),(-1,-1),8),('BOTTOMPADDING',(0,0),(-1,-1),8)]));out.append((t,10))
    return out

c=canvas.Canvas(str(OUT),pagesize=A4)
c.setTitle('Manual Book Sistem Dokumen');c.setAuthor('Dokumentasi Sistem Dokumen');c.setSubject('Panduan pengguna dan alur pengendalian dokumen - Edisi 1.0')
qa=[]
for num,p in enumerate(pages,1):
    c.bookmarkPage(f'p{num}');c.addOutlineEntry(p['title'].replace('\n',' '),f'p{num}',0,False)
    c.setFillColor(BLUE);c.setFont('Bold',9);c.drawRightString(W-M,H-29,'SISTEM DOKUMEN | MANUAL BOOK')
    c.setStrokeColor(LINE);c.line(M,H-37,W-M,H-37)
    c.setFillColor(TEAL);c.setFont('Bold',8);c.drawString(M,H-59,p['section'])
    title_size=30 if num==1 else 21
    title=Paragraph(escape(p['title']).replace('\n','<br/>'),ParagraphStyle('title',fontName='Bold',fontSize=title_size,leading=title_size+5,textColor=NAVY))
    _,th=title.wrap(CW,110);title.drawOn(c,M,H-76-th)
    y=H-76-th-11
    subtitle=P(escape(p['subtitle']),'small');_,sh=subtitle.wrap(CW,50);subtitle.drawOn(c,M,y-sh);y-=sh+18
    c.setStrokeColor(GOLD);c.setLineWidth(1.4);c.line(M,y+6,M+54,y+6)
    items=blocks(p);heights=[f.wrap(CW,900)[1]+gap for f,gap in items];total=sum(heights);available=y-57
    scale=min(1,available/total)
    if scale < .85:raise ValueError(f'Page {num} overcrowded: scale={scale:.3f}, total={total:.0f}, available={available:.0f}')
    c.saveState();c.translate(M,y);c.scale(1,scale)
    cursor=0
    for (f,gap),h in zip(items,heights):
        actual=h-gap;cursor-=actual;f.drawOn(c,0,cursor);cursor-=gap
    c.restoreState()
    bottom=y-total*scale;qa.append({'page':num,'title':p['title'],'scale':round(scale,3),'bottom':round(bottom,1)})
    c.setStrokeColor(LINE);c.setLineWidth(.5);c.line(M,42,W-M,42)
    c.setFillColor(MUTED);c.setFont('Body',8);c.drawString(M,28,'Sistem Dokumen - Panduan Pengguna | Edisi 1.1')
    c.setFont('Bold',8);c.drawRightString(W-M,28,f'{num:02d} / {len(pages)}')
    c.showPage()
c.save()
(ROOT/'tmp/pdfs/manual_qa.json').write_text(json.dumps(qa,indent=2),encoding='utf-8')
print(OUT);print(json.dumps(qa,indent=2))
