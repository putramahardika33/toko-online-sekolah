<?php
    $bulan_tes = array(
        '01' => "Januari",
        '02' => "Februari",
        '03' => "Maret",
        '04' => "April",
        '05' => "Mei",
        '06' => "Juni",
        '07' => "Juli",
        '08' => "Agustus",
        '09' => "September",
        '10' => "Oktober",
        '11' => "November",
        '12' => "Desember",
    );

    $cariParamRaw = filter_input(INPUT_GET, 'cari', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $cariActive = is_string($cariParamRaw) && $cariParamRaw !== '';

    $hariParamRaw = filter_input(INPUT_GET, 'hari', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $hariActive = ($hariParamRaw === 'cek');

    $bulanPostRaw = filter_input(INPUT_POST, 'bln', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $bulanPost = (is_string($bulanPostRaw) && preg_match('/^(0[1-9]|1[0-2])$/', $bulanPostRaw)) ? $bulanPostRaw : '';

    $tahunPostRaw = filter_input(INPUT_POST, 'thn', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $tahunPost = (is_string($tahunPostRaw) && preg_match('/^\d{4}$/', $tahunPostRaw)) ? $tahunPostRaw : '';

    $hariPostRaw = filter_input(INPUT_POST, 'hari', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $hariPost = is_string($hariPostRaw) ? trim($hariPostRaw) : '';
?>
<div class="row">
    <div class="col-md-12">
        <h4>
            <?php if ($cariActive && $bulanPost !== '' && $tahunPost !== '') { ?>
            Data Laporan Penjualan <?= htmlspecialchars($bulan_tes[$bulanPost] ?? $bulanPost, ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($tahunPost, ENT_QUOTES, 'UTF-8'); ?>
            <?php } elseif ($hariActive && $hariPost !== '') { ?>
            Data Laporan Penjualan <?= htmlspecialchars($hariPost, ENT_QUOTES, 'UTF-8'); ?>
            <?php } else { ?>
            Data Laporan Penjualan <?= htmlspecialchars($bulan_tes[date('m')], ENT_QUOTES, 'UTF-8'); ?> <?= date('Y'); ?>
            <?php } ?>
        </h4>
        <br />
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mt-2">Cari Laporan Per Bulan</h5>
            </div>
            <div class="card-body p-0">
                <form method="post" action="index.php?page=laporan&cari=ok">
                    <?php echo csrf_field(); ?>
                    <table class="table table-striped">
                        <tr>
                            <th>Pilih Bulan</th>
                            <th>Pilih Tahun</th>
                            <th>Aksi</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="bln" class="form-control">
                                    <option selected="selected">Bulan</option>
                                    <?php
                                        $bulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                                        $bln1  = ['01','02','03','04','05','06','07','08','09','10','11','12'];
                                        foreach ($bulan as $idx => $namaBulan) {
                                            echo '<option value="'.$bln1[$idx].'">'.$namaBulan.'</option>';
                                        }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <select name="thn" class="form-control">
                                    <option selected="selected">Tahun</option>
                                    <?php
                                        $now = (int) date('Y');
                                        for ($tahun = 2017; $tahun <= $now; $tahun++) {
                                            echo '<option value="'.$tahun.'">'.$tahun.'</option>';
                                        }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="periode" value="ya">
                                <button class="btn btn-primary">
                                    <i class="fa fa-search"></i> Cari
                                </button>
                                <a href="index.php?page=laporan" class="btn btn-success">
                                    <i class="fa fa-refresh"></i> Refresh</a>

                                <?php if ($cariActive && $bulanPost !== '' && $tahunPost !== '') { ?>
                                <a href="excel.php?cari=yes&bln=<?= urlencode($bulanPost); ?>&thn=<?= urlencode($tahunPost); ?>"
                                    class="btn btn-info"><i class="fa fa-file-excel"></i>
                                    Excel</a>
                                <a href="pdf.php?cari=yes&bln=<?= urlencode($bulanPost); ?>&thn=<?= urlencode($tahunPost); ?>"
                                    class="btn btn-danger"><i class="fa fa-file-pdf"></i>
                                    PDF</a>
                                <?php } else { ?>
                                <a href="excel.php" class="btn btn-info"><i class="fa fa-file-excel"></i>
                                    Excel</a>
                                <a href="pdf.php" class="btn btn-danger"><i class="fa fa-file-pdf"></i>
                                    PDF</a>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                </form>
                <form method="post" action="index.php?page=laporan&hari=cek">
                    <?php echo csrf_field(); ?>
                    <table class="table table-striped">
                        <tr>
                            <th>Pilih Hari</th>
                            <th>Aksi</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="date" value="<?= date('Y-m-d'); ?>" class="form-control" name="hari">
                            </td>
                            <td>
                                <input type="hidden" name="periode" value="ya">
                                <button class="btn btn-primary">
                                    <i class="fa fa-search"></i> Cari
                                </button>
                                <a href="index.php?page=laporan" class="btn btn-success">
                                    <i class="fa fa-refresh"></i> Refresh</a>

                                <?php if ($hariActive && $hariPost !== '') { ?>
                                <a href="excel.php?hari=cek&tgl=<?= urlencode($hariPost); ?>" class="btn btn-info"><i
                                        class="fa fa-file-excel"></i>
                                    Excel</a>
                                <a href="pdf.php?hari=cek&tgl=<?= urlencode($hariPost); ?>" class="btn btn-danger"><i
                                        class="fa fa-file-pdf"></i>
                                    PDF</a>
                                <?php } else { ?>
                                <a href="excel.php" class="btn btn-info"><i class="fa fa-file-excel"></i>
                                    Excel</a>
                                <a href="pdf.php" class="btn btn-danger"><i class="fa fa-file-pdf"></i>
                                    PDF</a>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        <br />
        <br />
        <!-- view barang -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered w-100 table-sm" id="example1">
                        <thead>
                            <tr style="background:#DFF0D8;color:#333;">
                                <th> No</th>
                                <th> No Transaksi</th>
                                <th> Tanggal</th>
                                <th> Kasir</th>
                                <th style="width:10%;"> Jumlah Item</th>
                                <th style="width:10%;"> Modal</th>
                                <th style="width:10%;"> Total</th>
                                <th> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if ($cariActive && $bulanPost !== '' && $tahunPost !== '') {
                                    $periode = $bulanPost.'-'.$tahunPost;
                                    $hasil = $lihat -> periode_jual($periode);
                                } elseif ($hariActive && $hariPost !== '') {
                                    $hasil = $lihat -> hari_jual($hariPost);
                                } else {
                                    $hasil = $lihat -> jual();
                                }

                                // Kelompokkan baris nota per transaksi (no_transaksi)
                                $transaksiList = [];
                                foreach ($hasil as $isi) {
                                    $key = $isi['no_transaksi'] !== '' ? $isi['no_transaksi'] : 'legacy-'.$isi['id_nota'];
                                    if (!isset($transaksiList[$key])) {
                                        $transaksiList[$key] = [
                                            'no_transaksi' => $isi['no_transaksi'],
                                            'nm_member' => $isi['nm_member'],
                                            'tanggal_input' => $isi['tanggal_input'],
                                            'total' => 0.0,
                                            'modal' => 0.0,
                                            'jumlah' => 0,
                                            'items' => [],
                                        ];
                                    }
                                    $itemJumlah = (int) $isi['jumlah'];
                                    $itemTotal = (float) $isi['total'];
                                    $itemModal = (float) $isi['harga_beli'] * $itemJumlah;

                                    $transaksiList[$key]['total']  += $itemTotal;
                                    $transaksiList[$key]['modal']  += $itemModal;
                                    $transaksiList[$key]['jumlah'] += $itemJumlah;
                                    $transaksiList[$key]['items'][] = [
                                        'nama_barang' => (string) $isi['nama_barang'],
                                        'id_barang' => (string) $isi['id_barang'],
                                        'jumlah' => $itemJumlah,
                                        'total' => $itemTotal,
                                    ];
                                }

                                $no = 1;
                                $bayar = 0.0;
                                $jumlah = 0;
                                $modal = 0.0;
                                foreach ($transaksiList as $t) {
                                    $bayar  += $t['total'];
                                    $modal  += $t['modal'];
                                    $jumlah += $t['jumlah'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $no, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= $t['no_transaksi'] !== '' ? htmlspecialchars($t['no_transaksi'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td><?= htmlspecialchars($t['tanggal_input'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($t['nm_member'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $t['jumlah'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>Rp.<?php echo number_format($t['modal']); ?>,-</td>
                                <td>Rp.<?php echo number_format($t['total']); ?>,-</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                        data-target="#modalDetailTransaksi"
                                        data-notrx="<?= htmlspecialchars($t['no_transaksi'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-kasir="<?= htmlspecialchars($t['nm_member'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tanggal="<?= htmlspecialchars($t['tanggal_input'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-items="<?= htmlspecialchars(json_encode($t['items']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-eye"></i> Detail</button>
                                    <?php if ($t['no_transaksi'] !== '') { ?>
                                    <a href="print.php?notrx=<?= urlencode($t['no_transaksi']); ?>&nm_member=<?= urlencode($t['nm_member']); ?>"
                                        target="_blank" class="btn btn-secondary btn-sm">
                                        <i class="fa fa-print"></i> Cetak Ulang</a>
                                    <a href="print_thermal.php?notrx=<?= urlencode($t['no_transaksi']); ?>&nm_member=<?= urlencode($t['nm_member']); ?>"
                                        target="_blank" class="btn btn-warning btn-sm">
                                        <i class="fa fa-print"></i> Thermal</a>
                                    <a class="btn btn-danger btn-sm"
                                        onclick="javascript:return confirm('Hapus transaksi ini dan kembalikan stok barangnya?');"
                                        href="fungsi/hapus/hapus.php?notrx=<?= urlencode($t['no_transaksi']); ?>&csrf_token=<?= urlencode(csrf_get_token()); ?>">
                                        <i class="fa fa-trash"></i> Hapus</a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php $no++; } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Terjual</th>
                                <th><?= htmlspecialchars((string) $jumlah, ENT_QUOTES, 'UTF-8'); ?></th>
                                <th>Rp.<?php echo number_format($modal); ?>,-</th>
                                <th>Rp.<?php echo number_format($bayar); ?>,-</th>
                                <th style="background:#0bb365;color:#fff;">
                                    Untung Rp.<?php echo number_format($bayar - $modal); ?>,-</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div class="modal fade" id="modalDetailTransaksi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi <span id="modalDetailNotrx"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Kasir: <strong id="modalDetailKasir"></strong></p>
                <p class="mb-3">Tanggal: <strong id="modalDetailTanggal"></strong></p>
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="modalDetailBody"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$('#modalDetailTransaksi').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var items = [];
    try {
        items = JSON.parse(button.attr('data-items') || '[]');
    } catch (e) {
        items = [];
    }

    $('#modalDetailNotrx').text(button.attr('data-notrx') || '-');
    $('#modalDetailKasir').text(button.attr('data-kasir') || '-');
    $('#modalDetailTanggal').text(button.attr('data-tanggal') || '-');

    var tbody = $('#modalDetailBody');
    tbody.empty();
    items.forEach(function (item) {
        var row = $('<tr></tr>');
        row.append($('<td></td>').text(item.nama_barang));
        row.append($('<td></td>').text(item.jumlah));
        row.append($('<td></td>').text('Rp.' + Number(item.total).toLocaleString('id-ID') + ',-'));
        tbody.append(row);
    });
});
</script>
