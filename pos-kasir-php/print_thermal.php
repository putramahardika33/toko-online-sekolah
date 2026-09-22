<?php
declare(strict_types=1);
@ob_start();
session_start();
require_once 'fungsi/csrf.php';
session_enforce_timeout();

if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';
include $view;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$lihat = new view($config);
$toko  = $lihat->toko();

$notrxRaw = filter_input(INPUT_GET, 'notrx', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$notrx    = (is_string($notrxRaw) && preg_match('/^[A-Za-z0-9-]{6,50}$/', trim($notrxRaw))) ? trim($notrxRaw) : '';

if ($notrx !== '') {
    $hsl = $lihat->nota_transaksi($notrx);
    $totalBayar = 0.0;
    foreach ($hsl as $isi) {
        $totalBayar += (float) ($isi['total'] ?? 0);
    }
} else {
    echo "Nomor transaksi tidak ditemukan.";
    exit;
}

$bayarInput     = filter_input(INPUT_GET, 'bayar', FILTER_VALIDATE_FLOAT);
$kembaliInput   = filter_input(INPUT_GET, 'kembali', FILTER_VALIDATE_FLOAT);
$bayarNominal   = ($bayarInput !== false && $bayarInput !== null) ? (float) $bayarInput : 0.0;
$kembaliNominal = ($kembaliInput !== false && $kembaliInput !== null) ? (float) $kembaliInput : 0.0;

function rupiah(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

try {
    // ==== KONFIGURASI PRINTER ====
    // Jika menggunakan Windows, pastikan printer sudah di-share, misalnya 'ThermalPrinter'
    // $connector = new WindowsPrintConnector("ThermalPrinter");
    // 
    // Untuk keperluan simulasi di Windows/Linux tanpa printer fisik, gunakan file:
    // $connector = new FilePrintConnector("php://stdout"); 
    
    // GANTI baris di bawah dengan konektor printer Anda (misalnya WindowsPrintConnector)
    $connector = new WindowsPrintConnector("EPSON_TM_U220"); 
    
    $printer = new Printer($connector);
    
    // Header
    $printer -> setJustification(Printer::JUSTIFY_CENTER);
    $printer -> text($toko['nama_toko'] . "\n");
    $printer -> text($toko['alamat_toko'] . "\n");
    $printer -> text("--------------------------------\n");
    
    // Meta
    $printer -> setJustification(Printer::JUSTIFY_LEFT);
    $printer -> text("No. Trx : " . $notrx . "\n");
    $printer -> text("Tanggal : " . date('d/m/Y H:i') . "\n");
    $printer -> text("Kasir   : " . $_SESSION['admin']['nm_member'] . "\n");
    $printer -> text("--------------------------------\n");
    
    // Items
    foreach ($hsl as $isi) {
        $nama   = $isi['nama_barang'];
        $jumlah = (int) $isi['jumlah'];
        $total  = (float) $isi['total'];
        
        // Buat format: Nama Barang
        //              Qty x Harga      Subtotal
        $printer -> text($nama . "\n");
        $hargaStr = $jumlah . " x " . rupiah($total / $jumlah);
        $subtotalStr = rupiah($total);
        
        // Padding right (contoh: 32 karakter per baris thermal standard)
        $spaces = 32 - strlen($hargaStr) - strlen($subtotalStr);
        $printer -> text($hargaStr . str_repeat(" ", max(1, $spaces)) . $subtotalStr . "\n");
    }
    
    $printer -> text("--------------------------------\n");
    $printer -> setJustification(Printer::JUSTIFY_RIGHT);
    $printer -> text("Total   : " . rupiah($totalBayar) . "\n");
    $printer -> text("Bayar   : " . rupiah($bayarNominal) . "\n");
    $printer -> text("Kembali : " . rupiah($kembaliNominal) . "\n");
    
    $printer -> text("\n");
    $printer -> setJustification(Printer::JUSTIFY_CENTER);
    $printer -> text("Terima kasih telah berbelanja!\n");
    $printer -> text("\n\n\n");
    
    // Cut the receipt
    $printer -> cut();
    
    // Close printer
    $printer -> close();
    
    echo "<script>alert('Berhasil mencetak struk thermal!'); window.close();</script>";
    
} catch (Exception $e) {
    echo "Gagal mencetak ke printer thermal: " . $e -> getMessage();
    echo "<br><br><b>Tips:</b> Pastikan nama printer di print_thermal.php sudah sesuai dengan nama sharing printer di Windows Anda.";
}
?>
