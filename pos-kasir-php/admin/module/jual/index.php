 <!--sidebar end-->

      <!-- **********************************************************************************************************************************************************
      MAIN CONTENT
      *********************************************************************************************************************************************************** -->
      <!--main content start-->
<?php
    $successParam = filter_input(INPUT_GET, 'success', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $showSuccess = is_string($successParam) && $successParam !== '';

    $removeParam = filter_input(INPUT_GET, 'remove', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]);
    $showRemove = is_string($removeParam) && $removeParam !== '';

    $hasil_penjualan = $lihat -> penjualan();
    $total_bayar = 0.0;
    foreach ($hasil_penjualan as $isi) {
        $total_bayar += (float) $isi['total'];
    }
?>
    <h4>Kasir</h4>
    <br>
    <?php if($showSuccess){?>
    <div class="alert alert-success">
        <p>Edit Data Berhasil !</p>
    </div>
    <?php }?>
    <?php if($showRemove){?>
    <div class="alert alert-danger">
        <p>Hapus Data Berhasil !</p>
    </div>
    <?php }?>

    <div class="row">
        <!-- Kolom kiri: cari & pilih barang -->
        <div class="col-lg-5">
            <div class="card card-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fa fa-search"></i> Cari Barang</h5>
                </div>
                <div class="card-body">
                    <input type="text" id="cari" class="form-control" placeholder="Kode / Nama Barang / Merk" autofocus>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-hover mb-0" id="hasil_cari">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p id="cari_kosong" class="text-muted text-center mt-2" style="display:none;">Barang tidak ditemukan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: keranjang & pembayaran -->
        <div class="col-lg-7">
            <div class="card card-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-shopping-cart"></i> Keranjang</h5>
                    <button type="button" id="btn_reset" class="btn btn-danger btn-sm">
                        <b>RESET KERANJANG</b>
                    </button>
                </div>
                <div class="card-body">
                    <div id="notif" class="alert" style="display:none;"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="tbl_keranjang">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th style="width:15%;">Jumlah</th>
                                    <th style="width:20%;">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="keranjang_body"></tbody>
                        </table>
                        <p id="keranjang_kosong" class="text-muted text-center mt-2" style="display:none;">Keranjang masih kosong.</p>
                    </div>

                    <hr>

                    <table class="table table-borderless mb-0">
                        <tr>
                            <td>Total</td>
                            <td><strong id="lbl_total">Rp 0</strong></td>
                        </tr>
                        <tr>
                            <td>Bayar</td>
                            <td><input type="text" inputmode="numeric" class="form-control" id="input_bayar" placeholder="0"></td>
                        </tr>
                        <tr>
                            <td>Kembali</td>
                            <td><strong id="lbl_kembali">Rp 0</strong></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="button" id="btn_bayar" class="btn btn-success">
                                    <i class="fa fa-shopping-cart"></i> Bayar
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    "use strict";

    var csrfToken = window.csrfToken || '';
    var cartTotal = 0;

    function rupiah(n) {
        n = Math.round(Number(n) || 0);
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function post(aksi, params) {
        var body = new URLSearchParams(params || {});
        body.set('aksi', aksi);
        body.set('csrf_token', csrfToken);
        return fetch('fungsi/kasir/kasir.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        }).then(function (res) { return res.json(); });
    }

    var notifTimer = null;
    function showNotif(type, message) {
        var notif = document.getElementById('notif');
        notif.className = 'alert alert-' + type;
        notif.textContent = message;
        notif.style.display = '';
        clearTimeout(notifTimer);
        notifTimer = setTimeout(function () { notif.style.display = 'none'; }, 4000);
    }

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) { node.className = cls; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function renderCart(data) {
        var tbody = document.getElementById('keranjang_body');
        var empty = document.getElementById('keranjang_kosong');
        tbody.innerHTML = '';
        cartTotal = Number(data.total) || 0;

        if (!data.cart || data.cart.length === 0) {
            empty.style.display = '';
        } else {
            empty.style.display = 'none';
            data.cart.forEach(function (item, idx) {
                var tr = document.createElement('tr');

                tr.appendChild(el('td', null, String(idx + 1)));
                tr.appendChild(el('td', null, item.nama_barang));

                var tdQty = el('td');
                var qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.min = '1';
                qtyInput.className = 'form-control form-control-sm';
                qtyInput.value = item.jumlah;
                qtyInput.addEventListener('change', function () {
                    var jumlah = parseInt(qtyInput.value, 10);
                    if (!jumlah || jumlah < 1) { jumlah = 1; }
                    post('update', {id_penjualan: item.id_penjualan, jumlah: jumlah}).then(function (resp) {
                        if (!resp.ok) { showNotif('danger', resp.message || 'Gagal memperbarui jumlah.'); }
                        renderCart(resp);
                    });
                });
                tdQty.appendChild(qtyInput);
                tr.appendChild(tdQty);

                tr.appendChild(el('td', null, rupiah(item.total)));

                var tdAksi = el('td', 'text-center');
                var btnHapus = el('button', 'btn btn-danger btn-sm');
                btnHapus.type = 'button';
                btnHapus.innerHTML = '<i class="fa fa-times"></i>';
                btnHapus.addEventListener('click', function () {
                    post('hapus', {id_penjualan: item.id_penjualan}).then(renderCart);
                });
                tdAksi.appendChild(btnHapus);
                tr.appendChild(tdAksi);

                tbody.appendChild(tr);
            });
        }

        document.getElementById('lbl_total').textContent = rupiah(cartTotal);
        updateKembali();
    }

    function updateKembali() {
        var bayar = parseFloat(document.getElementById('input_bayar').value.replace(/[^0-9.]/g, '')) || 0;
        var kembali = bayar - cartTotal;
        document.getElementById('lbl_kembali').textContent = rupiah(kembali < 0 ? 0 : kembali);
    }

    function renderSearchResults(items) {
        var tbody = document.querySelector('#hasil_cari tbody');
        var empty = document.getElementById('cari_kosong');
        tbody.innerHTML = '';

        if (!items || items.length === 0) {
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.appendChild(el('td', null, item.nama_barang));
            tr.appendChild(el('td', null, rupiah(item.harga_jual)));
            tr.appendChild(el('td', null, String(item.stok)));

            var tdAksi = el('td', 'text-center');
            var btnTambah = el('button', 'btn btn-success btn-sm');
            btnTambah.type = 'button';
            btnTambah.disabled = item.stok <= 0;
            btnTambah.innerHTML = '<i class="fa fa-plus"></i>';
            btnTambah.addEventListener('click', function () {
                post('tambah', {id_barang: item.id_barang}).then(function (resp) {
                    if (!resp.ok) { showNotif('danger', resp.message || 'Gagal menambahkan barang.'); }
                    renderCart(resp);
                });
            });
            tdAksi.appendChild(btnTambah);
            tr.appendChild(tdAksi);

            tbody.appendChild(tr);
        });
    }

    var searchTimer = null;
    document.getElementById('cari').addEventListener('input', function () {
        var keyword = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (keyword.trim() === '') {
                renderSearchResults([]);
                return;
            }
            post('cari', {keyword: keyword}).then(function (resp) {
                renderSearchResults(resp.items || []);
            });
        }, 300);
    });

    document.getElementById('input_bayar').addEventListener('input', updateKembali);

    document.getElementById('btn_reset').addEventListener('click', function () {
        if (!confirm('Apakah anda ingin reset keranjang ?')) { return; }
        post('reset').then(renderCart);
    });

    document.getElementById('btn_bayar').addEventListener('click', function () {
        var bayar = parseFloat(document.getElementById('input_bayar').value.replace(/[^0-9.]/g, '')) || 0;
        post('bayar', {bayar: bayar}).then(function (resp) {
            if (!resp.ok) {
                showNotif('danger', resp.message || 'Pembayaran gagal.');
                if (resp.cart) { renderCart(resp); }
                return;
            }
            document.getElementById('input_bayar').value = '';
            renderCart({cart: [], total: 0});
            showNotif('success', 'Transaksi ' + resp.no_transaksi + ' selesai, kembalian ' + rupiah(resp.kembali) + '. Mencetak struk...');
            var url = 'print.php?notrx=' + encodeURIComponent(resp.no_transaksi)
                + '&nm_member=' + encodeURIComponent(<?php echo json_encode((string) ($_SESSION['admin']['nm_member'] ?? '')); ?>)
                + '&bayar=' + encodeURIComponent(resp.bayar)
                + '&kembali=' + encodeURIComponent(resp.kembali);
            window.open(url, '_blank');
        });
    });

    renderCart({
        cart: <?php echo json_encode(array_map(static function ($isi) {
            $jumlah = (int) $isi['jumlah'];
            $total = (float) $isi['total'];
            return [
                'id_penjualan' => (int) $isi['id_penjualan'],
                'id_barang' => (string) $isi['id_barang'],
                'nama_barang' => (string) $isi['nama_barang'],
                'jumlah' => $jumlah,
                'harga_satuan' => $jumlah > 0 ? $total / $jumlah : $total,
                'total' => $total,
            ];
        }, $hasil_penjualan)); ?>,
        total: <?php echo json_encode($total_bayar); ?>
    });
})();
</script>
