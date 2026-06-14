<?php
require 'function.php';
require 'cek.php';
$currentPage = basename($_SERVER['PHP_SELF']);

// ── HAPUS BARANG ──────────────────────────────────────────────────────────────
if (isset($_POST['hapusbarang'])) {
    $idbarang = mysqli_real_escape_string($conn, $_POST['idbarang']);
    mysqli_query($conn, "DELETE FROM stock WHERE idbarang='$idbarang'");
    header("Location: index.php");
    exit;
}

// ── TAMBAH BARANG ─────────────────────────────────────────────────────────────
if (isset($_POST['addnewbarang'])) {
    $namabarang = mysqli_real_escape_string($conn, $_POST['namabarang']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $stock = (int) $_POST['stock'];
    mysqli_query($conn, "INSERT INTO stock (namabarang, deskripsi, stock)
                         VALUES ('$namabarang','$deskripsi','$stock')");
    header("Location: index.php");
    exit;
}

// ── UPDATE BARANG ─────────────────────────────────────────────────────────────
if (isset($_POST['updatebarang'])) {
    $idbarang = mysqli_real_escape_string($conn, $_POST['idbarang']);
    $namabarang = mysqli_real_escape_string($conn, $_POST['namabarang']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $stock = (int) $_POST['stock'];
    mysqli_query($conn, "UPDATE stock SET namabarang='$namabarang',
                         deskripsi='$deskripsi', stock='$stock'
                         WHERE idbarang='$idbarang'");
    header("Location: index.php");
    exit;
}

// ── EKSPOR CSV ────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="stock_barang_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM agar Excel bisa baca UTF-8
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['No', 'Nama Barang', 'Deskripsi', 'Stock', 'Status']);
    $rows = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
    $i = 1;
    while ($r = mysqli_fetch_assoc($rows)) {
        fputcsv($out, [
            $i++,
            $r['namabarang'],
            $r['deskripsi'],
            $r['stock'],
            $r['stock'] == 0 ? 'Habis' : 'Tersedia'
        ]);
    }
    fclose($out);
    exit;
}

// ── AMBIL DATA DETAIL (AJAX) ──────────────────────────────────────────────────
if (isset($_GET['detail_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['detail_id']);
    $item = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM stock WHERE idbarang='$id'"
    ));

    // Riwayat masuk (tabel barangmasuk — sesuaikan kolom jika berbeda)
    $masuk = mysqli_query(
        $conn,
        "SELECT * FROM barangmasuk  WHERE idbarang='$id' ORDER BY tanggal DESC LIMIT 10"
    );
    // Riwayat keluar
    $keluar = mysqli_query(
        $conn,
        "SELECT * FROM barangkeluar WHERE idbarang='$id' ORDER BY tanggal DESC LIMIT 10"
    );

    header('Content-Type: application/json');
    echo json_encode([
        'item' => $item,
        'masuk' => mysqli_fetch_all($masuk, MYSQLI_ASSOC),
        'keluar' => mysqli_fetch_all($keluar, MYSQLI_ASSOC),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Stock Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <!-- ── Style khusus print & badge ──────────────────────────────────────── -->
    <style>
        @media print {

            /* Sembunyikan semua kecuali tabel print */
            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: fixed;
                inset: 0;
                padding: 24px;
            }

            .no-print {
                display: none !important;
            }
        }

        .badge-habis {
            background: #dc3545;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: .8em;
        }

        .badge-tersedia {
            background: #198754;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: .8em;
        }

        #printArea {
            display: none;
        }

        /* hanya tampil saat print */
    </style>
</head>

<body class="sb-nav-fixed">

    <!-- ═══════════════════════════  TOPNAV  ══════════════════════════════════════ -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">ditss kicau mania</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <div id="layoutSidenav">
        <!-- ═══════════════════════  SIDENAV  ════════════════════════════════════ -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Stock Barang
                        </a>
                        <a class="nav-link <?= $currentPage === 'masuk.php' ? 'active' : '' ?>" href="masuk.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-circle-down"></i></div>Barang Masuk
                        </a>
                        <a class="nav-link <?= $currentPage === 'keluar.php' ? 'active' : '' ?>" href="keluar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-circle-up"></i></div>Barang Keluar
                        </a>
                        <a class="nav-link" href="logout.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>Logout
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- ═══════════════════════  CONTENT  ════════════════════════════════════ -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Stock Barang</h1>

                    <!-- Alert stock habis -->
                    <?php
                    $cekHabis = mysqli_query($conn, "SELECT COUNT(*) AS total FROM stock WHERE stock=0");
                    $countHabis = mysqli_fetch_assoc($cekHabis);
                    if ($countHabis['total'] > 0): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Perhatian!</strong>
                            Ada <strong><?= $countHabis['total'] ?></strong> barang yang stok-nya habis.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex gap-2 flex-wrap">
                            <!-- Tambah -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="fas fa-plus me-1"></i>Tambah Barang
                            </button>

                            <!-- ── EKSPOR ─────────────────────────────────── -->
                            <div class="btn-group no-print">
                                <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-export me-1"></i>Ekspor Data
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="?export=csv">
                                            <i class="fas fa-file-csv me-2 text-success"></i>Ekspor ke CSV / Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" id="btnPrint">
                                            <i class="fas fa-print me-2 text-danger"></i>Cetak / Ekspor PDF
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Barang</th>
                                        <th>Deskripsi</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th class="no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $allStock = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
                                    $i = 1;
                                    while ($data = mysqli_fetch_assoc($allStock)):
                                        $id = $data['idbarang'];
                                        $nama = $data['namabarang'];
                                        $desk = $data['deskripsi'];
                                        $stok = $data['stock'];
                                        $rowCls = $stok == 0 ? 'table-danger' : '';
                                        ?>
                                        <tr class="<?= $rowCls ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($nama) ?></td>
                                            <td><?= htmlspecialchars($desk) ?></td>
                                            <td><?= $stok ?></td>
                                            <td>
                                                <?php if ($stok == 0): ?>
                                                    <span class="badge-habis">HABIS</span>
                                                <?php else: ?>
                                                    <span class="badge-tersedia">TERSEDIA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print">
                                                <!-- Detail -->
                                                <button type="button" class="btn btn-sm btn-info text-white btn-detail"
                                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                                    data-id="<?= $id ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <!-- Edit -->
                                                <button type="button" class="btn btn-sm btn-warning btn-edit"
                                                    data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?= $id ?>"
                                                    data-name="<?= htmlspecialchars($nama, ENT_QUOTES) ?>"
                                                    data-desc="<?= htmlspecialchars($desk, ENT_QUOTES) ?>"
                                                    data-stock="<?= $stok ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <!-- Hapus -->
                                                <form method="post" class="d-inline"
                                                    onsubmit="return confirm('Hapus barang ini?');">
                                                    <input type="hidden" name="idbarang" value="<?= $id ?>">
                                                    <button type="submit" name="hapusbarang" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div><!-- card-body -->
                    </div><!-- card -->
                </div><!-- container -->
            </main>
        </div><!-- layoutSidenav_content -->
    </div><!-- layoutSidenav -->


    <!-- ════════════════════════  AREA PRINT (tersembunyi)  ══════════════════════ -->
    <div id="printArea">
        <h3 style="text-align:center">Laporan Stock Barang — ditss kicau mania</h3>
        <p style="text-align:center;font-size:.9em">Dicetak pada: <?= date('d-m-Y H:i:s') ?></p>
        <table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:.9em">
            <thead style="background:#343a40;color:#fff">
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Deskripsi</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $printData = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
                $pi = 1;
                while ($pd = mysqli_fetch_assoc($printData)):
                    ?>
                    <tr>
                        <td><?= $pi++ ?></td>
                        <td><?= htmlspecialchars($pd['namabarang']) ?></td>
                        <td><?= htmlspecialchars($pd['deskripsi']) ?></td>
                        <td><?= $pd['stock'] ?></td>
                        <td><?= $pd['stock'] == 0 ? 'HABIS' : 'Tersedia' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>


    <!-- ════════════════════════════  MODAL: TAMBAH  ═════════════════════════════ -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Barang</label>
                            <input type="text" name="namabarang" class="form-control" placeholder="Nama Barang"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi barang"
                                required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Stok Awal</label>
                            <input type="number" name="stock" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="addnewbarang" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ═══════════════════════════  MODAL: EDIT  ════════════════════════════════ -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Ubah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_idbarang" name="idbarang">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Barang</label>
                            <input type="text" id="edit_namabarang" name="namabarang" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea id="edit_deskripsi" name="deskripsi" class="form-control" rows="3"
                                required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Stok</label>
                            <input type="number" id="edit_stock" name="stock" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="updatebarang" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════  MODAL: DETAIL  ═══════════════════════════════ -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Barang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBody">
                    <!-- Diisi via JavaScript / fetch -->
                    <div class="text-center py-4" id="detailLoading">
                        <div class="spinner-border text-info" role="status"></div>
                        <p class="mt-2 text-muted">Memuat data…</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>


    <!-- ════════════════════════════  SCRIPTS  ═══════════════════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>

    <script>
        // ── Isi modal Edit ───────────────────────────────────────────────────────────
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('edit_idbarang').value = this.dataset.id;
                document.getElementById('edit_namabarang').value = this.dataset.name;
                document.getElementById('edit_deskripsi').value = this.dataset.desc;
                document.getElementById('edit_stock').value = this.dataset.stock;
            });
        });

        // ── Isi modal Detail (fetch ke server) ──────────────────────────────────────
        document.querySelectorAll('.btn-detail').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const body = document.getElementById('detailBody');

                // Reset ke loading
                body.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-info" role="status"></div>
                <p class="mt-2 text-muted">Memuat data…</p>
            </div>`;

                fetch('index.php?detail_id=' + id)
                    .then(r => r.json())
                    .then(json => {
                        const it = json.item;
                        if (!it) { body.innerHTML = '<p class="text-danger">Data tidak ditemukan.</p>'; return; }

                        const statusBadge = it.stock == 0
                            ? '<span class="badge bg-danger">HABIS</span>'
                            : '<span class="badge bg-success">TERSEDIA</span>';

                        // ── Tabel riwayat masuk ──────────────────────────────────
                        let masukHtml = '<p class="text-muted fst-italic">Belum ada riwayat.</p>';
                        if (json.masuk && json.masuk.length > 0) {
                            masukHtml = `<table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Tanggal</th><th>Jumlah</th><th>Keterangan</th></tr></thead>
                        <tbody>` +
                                json.masuk.map(r => `<tr>
                            <td>${r.tanggal ?? '-'}</td>
                            <td>${r.jumlah ?? '-'}</td>
                            <td>${r.keterangan ?? '-'}</td>
                        </tr>`).join('') +
                                `</tbody></table>`;
                        }

                        // ── Tabel riwayat keluar ─────────────────────────────────
                        let keluarHtml = '<p class="text-muted fst-italic">Belum ada riwayat.</p>';
                        if (json.keluar && json.keluar.length > 0) {
                            keluarHtml = `<table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Tanggal</th><th>Jumlah</th><th>Keterangan</th></tr></thead>
                        <tbody>` +
                                json.keluar.map(r => `<tr>
                            <td>${r.tanggal ?? '-'}</td>
                            <td>${r.jumlah ?? '-'}</td>
                            <td>${r.keterangan ?? '-'}</td>
                        </tr>`).join('') +
                                `</tbody></table>`;
                        }

                        body.innerHTML = `
                <!-- Info utama -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="card h-100 border-info">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-1">Nama Barang</h6>
                                <p class="card-text fs-5 fw-bold">${it.namabarang}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <h6 class="card-subtitle text-muted mb-1">Stok Saat Ini</h6>
                                <p class="card-text fs-3 fw-bold">${it.stock}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <h6 class="card-subtitle text-muted mb-1">Status</h6>
                                <p class="card-text mt-1">${statusBadge}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-1">Deskripsi</h6>
                                <p class="card-text">${it.deskripsi}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat masuk -->
                <h6 class="fw-bold text-success"><i class="fas fa-arrow-circle-down me-1"></i>10 Riwayat Barang Masuk Terakhir</h6>
                <div class="mb-4">${masukHtml}</div>

                <!-- Riwayat keluar -->
                <h6 class="fw-bold text-danger"><i class="fas fa-arrow-circle-up me-1"></i>10 Riwayat Barang Keluar Terakhir</h6>
                <div>${keluarHtml}</div>`;
                    })
                    .catch(() => {
                        body.innerHTML = '<p class="text-danger">Gagal memuat data. Periksa koneksi Anda.</p>';
                    });
            });
        });

        // ── Tombol Print / Ekspor PDF ────────────────────────────────────────────────
        document.getElementById('btnPrint').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('printArea').style.display = 'block';
            window.print();
            // Sembunyikan kembali setelah print dialog
            setTimeout(() => {
                document.getElementById('printArea').style.display = 'none';
            }, 1000);
        });
    </script>

</body>

</html>