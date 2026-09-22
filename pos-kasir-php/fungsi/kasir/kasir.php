<?php
declare(strict_types=1);

session_start();
require_once __DIR__.'/../csrf.php';
session_enforce_timeout();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
    exit;
}

require __DIR__.'/../../config.php';
csrf_guard();

function sanitize_scalar_input($value, bool $allowNewlines = false): string
{
    $stringValue = trim((string) $value);
    $pattern = $allowNewlines ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
    $cleaned = preg_replace($pattern, '', $stringValue);

    return $cleaned === null ? '' : trim($cleaned);
}

function get_post_string(string $key): string
{
    $value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    if ($value === null) {
        return '';
    }

    return sanitize_scalar_input($value);
}

function get_post_int(string $key): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($value === null || $value === false) {
        return 0;
    }

    return (int) $value;
}

function get_post_float(string $key): float
{
    $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_NUMBER_FLOAT, ['flags' => FILTER_FLAG_ALLOW_FRACTION]);
    if ($value === null || $value === false || $value === '') {
        return 0.0;
    }

    return (float) $value;
}

function generate_no_transaksi(PDO $config): string
{
    $prefix = date('Ymd');
    $sql = 'SELECT no_transaksi FROM nota WHERE no_transaksi LIKE ? ORDER BY no_transaksi DESC LIMIT 1';
    $stmt = $config->prepare($sql);
    $stmt->execute([$prefix.'-%']);
    $last = $stmt->fetchColumn();

    $seq = 1;
    if ($last) {
        $parts = explode('-', $last);
        $seq = ((int) end($parts)) + 1;
    }

    return $prefix.'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
}

function current_cart(PDO $config): array
{
    $sql = "SELECT penjualan.*, barang.nama_barang
            FROM penjualan
            LEFT JOIN barang ON barang.id_barang = penjualan.id_barang
            ORDER BY id_penjualan";
    $row = $config->prepare($sql);
    $row->execute();
    $rows = $row->fetchAll();

    $cart = [];
    $total = 0.0;
    foreach ($rows as $r) {
        $jumlah = (int) $r['jumlah'];
        $rowTotal = (float) $r['total'];
        $cart[] = [
            'id_penjualan' => (int) $r['id_penjualan'],
            'id_barang' => (string) $r['id_barang'],
            'nama_barang' => (string) ($r['nama_barang'] ?? '(barang dihapus)'),
            'jumlah' => $jumlah,
            'harga_satuan' => $jumlah > 0 ? $rowTotal / $jumlah : $rowTotal,
            'total' => $rowTotal,
        ];
        $total += $rowTotal;
    }

    return ['cart' => $cart, 'total' => $total];
}

function respond_with_cart(PDO $config, bool $ok, string $message = ''): void
{
    $state = current_cart($config);
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'cart' => $state['cart'],
        'total' => $state['total'],
    ]);
    exit;
}

$aksi = get_post_string('aksi');

if ($aksi === 'state') {
    respond_with_cart($config, true);
}

if ($aksi === 'cari') {
    $keyword = get_post_string('keyword');
    $items = [];
    if ($keyword !== '') {
        $param = "%{$keyword}%";
        $sql = 'SELECT id_barang, nama_barang, merk, harga_jual, stok
                FROM barang
                WHERE id_barang LIKE ? OR nama_barang LIKE ? OR merk LIKE ?
                ORDER BY nama_barang ASC
                LIMIT 25';
        $row = $config->prepare($sql);
        $row->execute([$param, $param, $param]);
        foreach ($row->fetchAll() as $r) {
            $items[] = [
                'id_barang' => (string) $r['id_barang'],
                'nama_barang' => (string) $r['nama_barang'],
                'merk' => (string) $r['merk'],
                'harga_jual' => (float) $r['harga_jual'],
                'stok' => (int) $r['stok'],
            ];
        }
    }
    echo json_encode(['ok' => true, 'items' => $items]);
    exit;
}

if ($aksi === 'tambah') {
    $idBarang = get_post_string('id_barang');
    if ($idBarang === '' || !preg_match('/^[A-Za-z0-9-]+$/', $idBarang)) {
        respond_with_cart($config, false, 'Barang tidak valid.');
    }

    $sql = 'SELECT * FROM barang WHERE id_barang = ?';
    $row = $config->prepare($sql);
    $row->execute([$idBarang]);
    $barang = $row->fetch();

    if (!$barang) {
        respond_with_cart($config, false, 'Barang tidak ditemukan.');
    }

    $stok = (int) $barang['stok'];
    $hargaJual = (float) $barang['harga_jual'];

    $sqlExisting = 'SELECT * FROM penjualan WHERE id_barang = ?';
    $rowExisting = $config->prepare($sqlExisting);
    $rowExisting->execute([$idBarang]);
    $existing = $rowExisting->fetch();

    if ($existing) {
        $jumlahBaru = (int) $existing['jumlah'] + 1;
        if ($jumlahBaru > $stok) {
            respond_with_cart($config, false, 'Stok barang tidak cukup.');
        }
        $totalBaru = $hargaJual * $jumlahBaru;
        $sqlUpdate = 'UPDATE penjualan SET jumlah = ?, total = ? WHERE id_penjualan = ?';
        $rowUpdate = $config->prepare($sqlUpdate);
        $rowUpdate->execute([$jumlahBaru, $totalBaru, (int) $existing['id_penjualan']]);
    } else {
        if ($stok <= 0) {
            respond_with_cart($config, false, 'Stok barang habis.');
        }
        $idKasir = (int) ($_SESSION['admin']['id_member'] ?? 0);
        $tgl = date('j F Y, G:i');
        $sqlInsert = 'INSERT INTO penjualan (id_barang,id_member,jumlah,total,tanggal_input) VALUES (?,?,?,?,?)';
        $rowInsert = $config->prepare($sqlInsert);
        $rowInsert->execute([$idBarang, $idKasir, 1, $hargaJual, $tgl]);
    }

    respond_with_cart($config, true, 'Barang ditambahkan ke keranjang.');
}

if ($aksi === 'update') {
    $idPenjualan = get_post_int('id_penjualan');
    $jumlah = get_post_int('jumlah');

    if ($idPenjualan <= 0 || $jumlah <= 0) {
        respond_with_cart($config, false, 'Data tidak valid.');
    }

    $sqlItem = 'SELECT * FROM penjualan WHERE id_penjualan = ?';
    $rowItem = $config->prepare($sqlItem);
    $rowItem->execute([$idPenjualan]);
    $item = $rowItem->fetch();

    if (!$item) {
        respond_with_cart($config, false, 'Item tidak ditemukan.');
    }

    $sqlBarang = 'SELECT * FROM barang WHERE id_barang = ?';
    $rowBarang = $config->prepare($sqlBarang);
    $rowBarang->execute([$item['id_barang']]);
    $barang = $rowBarang->fetch();

    $stok = $barang ? (int) $barang['stok'] : 0;
    if ($jumlah > $stok) {
        respond_with_cart($config, false, 'Jumlah melebihi stok barang.');
    }

    $hargaJual = $barang ? (float) $barang['harga_jual'] : 0.0;
    $total = $hargaJual * $jumlah;

    $sqlUpdate = 'UPDATE penjualan SET jumlah = ?, total = ? WHERE id_penjualan = ?';
    $rowUpdate = $config->prepare($sqlUpdate);
    $rowUpdate->execute([$jumlah, $total, $idPenjualan]);

    respond_with_cart($config, true);
}

if ($aksi === 'hapus') {
    $idPenjualan = get_post_int('id_penjualan');
    if ($idPenjualan <= 0) {
        respond_with_cart($config, false, 'Data tidak valid.');
    }

    $sql = 'DELETE FROM penjualan WHERE id_penjualan = ?';
    $row = $config->prepare($sql);
    $row->execute([$idPenjualan]);

    respond_with_cart($config, true, 'Barang dihapus dari keranjang.');
}

if ($aksi === 'reset') {
    $config->prepare('DELETE FROM penjualan')->execute();
    respond_with_cart($config, true, 'Keranjang dikosongkan.');
}

if ($aksi === 'bayar') {
    $bayarNominal = get_post_float('bayar');
    $state = current_cart($config);
    $cart = $state['cart'];
    $total = $state['total'];

    if (empty($cart)) {
        echo json_encode(['ok' => false, 'message' => 'Keranjang kosong.']);
        exit;
    }

    if ($bayarNominal < $total) {
        echo json_encode([
            'ok' => false,
            'message' => 'Uang pembayaran kurang.',
            'total' => $total,
        ]);
        exit;
    }

    $periode = date('m-Y');
    $tglInput = date('j F Y, G:i');
    $idKasir = (int) ($_SESSION['admin']['id_member'] ?? 0);

    $config->beginTransaction();
    try {
        $noTransaksi = generate_no_transaksi($config);

        foreach ($cart as $item) {
            $sqlStok = 'UPDATE barang SET stok = stok - ? WHERE id_barang = ? AND stok >= ?';
            $stmtStok = $config->prepare($sqlStok);
            $stmtStok->execute([$item['jumlah'], $item['id_barang'], $item['jumlah']]);

            if ($stmtStok->rowCount() === 0) {
                throw new RuntimeException('Stok barang '.$item['nama_barang'].' tidak cukup.');
            }

            $sqlNota = 'INSERT INTO nota (no_transaksi,id_barang,id_member,jumlah,total,tanggal_input,periode)
                        VALUES (?,?,?,?,?,?,?)';
            $stmtNota = $config->prepare($sqlNota);
            $stmtNota->execute([
                $noTransaksi,
                $item['id_barang'],
                $idKasir,
                $item['jumlah'],
                $item['total'],
                $tglInput,
                $periode,
            ]);

            $sqlHapus = 'DELETE FROM penjualan WHERE id_penjualan = ?';
            $stmtHapus = $config->prepare($sqlHapus);
            $stmtHapus->execute([$item['id_penjualan']]);
        }

        $config->commit();
    } catch (Throwable $e) {
        $config->rollBack();
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'no_transaksi' => $noTransaksi,
        'total' => $total,
        'bayar' => $bayarNominal,
        'kembali' => $bayarNominal - $total,
    ]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Aksi tidak dikenal.']);
