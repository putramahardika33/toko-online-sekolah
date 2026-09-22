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

use Dompdf\Dompdf;
use Dompdf\Options;

$lihat = new view($config);

$bulan_tes = array(
    '01'=>"Januari",
    '02'=>"Februari",
    '03'=>"Maret",
    '04'=>"April",
    '05'=>"Mei",
    '06'=>"Juni",
    '07'=>"Juli",
    '08'=>"Agustus",
    '09'=>"September",
    '10'=>"Oktober",
    '11'=>"November",
    '12'=>"Desember"
);

$cariParamRaw = filter_input(INPUT_GET, 'cari', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$cariParam = is_string($cariParamRaw) ? trim($cariParamRaw) : '';
$cariActive = in_array($cariParam, ['yes', 'ok'], true);

$hariParamRaw = filter_input(INPUT_GET, 'hari', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$hariParam = is_string($hariParamRaw) ? trim($hariParamRaw) : '';
$hariActive = ($hariParam === 'cek');

$bulanRaw = filter_input(INPUT_GET, 'bln', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$bulanParam = (is_string($bulanRaw) && preg_match('/^(0[1-9]|1[0-2])$/', $bulanRaw)) ? $bulanRaw : '';

$tahunRaw = filter_input(INPUT_GET, 'thn', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$tahunParam = (is_string($tahunRaw) && preg_match('/^\d{4}$/', $tahunRaw)) ? $tahunRaw : '';

$tanggalRaw = filter_input(INPUT_GET, 'tgl', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
$tanggalParam = (is_string($tanggalRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalRaw)) ? $tanggalRaw : '';

$title = '';
if($cariActive && $bulanParam !== '' && $tahunParam !== ''){
    $title = "Data Laporan Penjualan " . ($bulan_tes[$bulanParam] ?? $bulanParam) . " " . $tahunParam;
    $periode = $bulanParam.'-'.$tahunParam;
    $hasil = $lihat->periode_jual($periode);
} elseif($hariActive && $tanggalParam !== ''){
    $title = "Data Laporan Penjualan " . $tanggalParam;
    $hasil = $lihat->hari_jual($tanggalParam);
} else {
    $title = "Data Laporan Penjualan " . $bulan_tes[date('m')] . " " . date('Y');
    $hasil = $lihat->jual();
}

$html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h3>' . htmlspecialchars($title) . '</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Transaksi</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Modal</th>
                <th>Total</th>
                <th>Kasir</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
$bayar = 0;
$jumlah = 0;
$modal = 0;

foreach($hasil as $isi){
    $itemBayar = (float)$isi['total'];
    $itemModal = (float)$isi['harga_beli'] * (int)$isi['jumlah'];
    $bayar += $itemBayar;
    $modal += $itemModal;
    $jumlah += (int)$isi['jumlah'];
    
    $html .= '<tr>
        <td>'.$no.'</td>
        <td>'.(!empty($isi['no_transaksi']) ? htmlspecialchars($isi['no_transaksi']) : '-').'</td>
        <td>'.htmlspecialchars($isi['nama_barang']).'</td>
        <td>'.htmlspecialchars($isi['jumlah']).'</td>
        <td>Rp. '.number_format($itemModal).'</td>
        <td>Rp. '.number_format($itemBayar).'</td>
        <td>'.htmlspecialchars($isi['nm_member']).'</td>
        <td>'.htmlspecialchars($isi['tanggal_input']).'</td>
    </tr>';
    $no++;
}

$html .= '
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align:right">Total Terjual</th>
                <th>'.$jumlah.'</th>
                <th>Rp. '.number_format($modal).'</th>
                <th>Rp. '.number_format($bayar).'</th>
                <th colspan="2">Untung: Rp. '.number_format($bayar-$modal).'</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "Laporan_Penjualan_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>
