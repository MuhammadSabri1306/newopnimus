<?php
require __DIR__.'/../app/bootstrap.php';

use App\Core\TelegramText;

$text = TelegramText::create()
    ->startBold()->addText('Term of Use | Ketentuan Penggunaan OPNIMUS')->endBold()->newLine(2)
    ->addTabspace()->addText('Pembaca diharuskan memahami beberapa persyaratan berikut yang ditetapkan oleh OPNIMUS. Jika anda tidak menyetujuinya, disarankan untuk tidak menggunakan tools ini.')->newLine(2)
    ->addTabspace()->startItalic()->addText('Konten')->endItalic()->newLine()
    ->addTabspace()->addText('1. Semua materi di dalam ***OPNIMUS*** tidak boleh disalin, diproduksi ulang, dipublikasikan, dan disebarluaskan baik berupa informasi, data, gambar, foto, logo dan lainnya secara utuh maupun sebagian dengan cara apapun dan melalui media apapun kecuali untuk keperluan Operasional.')->newLine()
    ->addTabspace()->addText('2. Aplikasi  ini berikut seluruh materinya tidak boleh dimanfaatkan untuk hal-hal yang bertujuan melanggar hukum atau norma agama dan masyarakat atau hak asasi manusia.')->newLine(2)
    ->addTabspace()->startItalic()->addText('Dalam Penggunaan OPNIMUS, anda setuju untuk:')->endItalic()->newLine()
    ->addTabspace()->addText('1. Memberikan informasi yang akurat, baru dan lengkap tentang diri Anda saat Mendaftar ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')->newLine()
    ->addTabspace()->addText('2. ')->startBold()->addText('OPNIMUS')->endBold()->addText(' tidak bertanggungjawab atas dampak yang ditimbulkan atas penggunaan materi di dalam aplikasi.')->newLine()
    ->addTabspace()->addText('3. Anda tidak diperbolehkan menggunakan ')->startBold()->addText('OPNIMUS')->endBold()->addText(' dalam kondisi atau cara apapun yang dapat merusak, melumpuhkan, membebani dan/atau mengganggu server atau jaringan ')->startBold()->addText('OPNIMUS')->endBold()->addText('. Anda juga tidak diperbolehkan untuk mengakses layanan, akun pengguna, sistem komputer atau jaringan secara tidak sah, dengan cara perentasan (hacking), password mining, dan/atau cara lainnya.')->newLine(2)
    ->addTabspace()->addText('Dengan menggunakan atau mengakses (termasuk berbagai perubahan) ')->startBold()->addText('OPNIMUS')->endBold()->addText(' ini, anda berarti menyetujui syarat-syarat penggunaan ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')
    ->get();

dd($text);