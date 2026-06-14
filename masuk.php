<?php
require 'function.php';
require 'cek.php';
$currentPage = basename($_SERVER['PHP_SELF']);

// ── HAPUS BARANG MASUK ────────────────────────────────────────────────────────
if (isset($_POST['hapusmasuk'])) {
    $idmasuk = mysqli_real_escape_string($conn, $_POST['idmasuk']);
    // Ambil data sebelum dihapus untuk kembalikan stok
    $row = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM masuk WHERE idmasuk='$idmasuk'"
    ));
    if ($row) {
        mysqli_query($conn, "UPDATE stock SET stock = stock - {$row['qty']}
                             WHERE idbarang='{$row['idbarang']}'");
        mysqli_query($conn, "DELETE FROM masuk WHERE idmasuk='$idmasuk'");
    }
    header("Location: masuk.php");
    exit;
}

// ── TAMBAH BARANG MASUK ───────────────────────────────────────────────────────
if (isset($_POST['barangmasuk'])) {
    $idbarang = mysqli_real_escape_string($conn, $_POST['barangnya']);
    $qty = (int) $_POST['qty'];
    $penerima = mysqli_real_escape_string($conn, $_POST['penerima']);
    $tanggal = date('Y-m-d');
    mysqli_query($conn, "INSERT INTO masuk (idbarang, qty, keterangan, tanggal)
                         VALUES ('$idbarang','$qty','$penerima','$tanggal')");
    mysqli_query($conn, "UPDATE stock SET stock = stock + $qty
                         WHERE idbarang='$idbarang'");
    header("Location: masuk.php");
    exit;
}

// ── UPDATE BARANG MASUK ───────────────────────────────────────────────────────
if (isset($_POST['updatemasuk'])) {
    $idmasuk = mysqli_real_escape_string($conn, $_POST['idmasuk']);
    $idbarang = mysqli_real_escape_string($conn, $_POST['barangnya']);
    $newQty = (int) $_POST['qty'];
    $penerima = mysqli_real_escape_string($conn, $_POST['penerima']);

    $old = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM masuk WHERE idmasuk='$idmasuk'"
    ));
    if ($old) {
        $diff = $newQty - $old['qty'];
        mysqli_query($conn, "UPDATE masuk SET idbarang='$idbarang', qty='$newQty',
                             keterangan='$penerima' WHERE idmasuk='$idmasuk'");
        mysqli_query($conn, "UPDATE stock SET stock = stock + $diff
                             WHERE idbarang='$idbarang'");
    }
    header("Location: masuk.php");
    exit;
}

// ── EKSPOR CSV ────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($conn, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($conn, $_GET['sampai']) : '';

    $where = '';
    if ($dari && $sampai)
        $where = "AND m.tanggal BETWEEN '$dari' AND '$sampai'";
    elseif ($dari)
        $where = "AND m.tanggal >= '$dari'";
    elseif ($sampai)
        $where = "AND m.tanggal <= '$sampai'";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="barang_masuk_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['No', 'Tanggal', 'Nama Barang', 'Jumlah', 'Penerima/Keterangan']);
    $rows = mysqli_query(
        $conn,
        "SELECT m.*, s.namabarang FROM masuk m
         JOIN stock s ON s.idbarang = m.idbarang
         WHERE 1=1 $where ORDER BY m.tanggal DESC"
    );
    $i = 1;
    while ($r = mysqli_fetch_assoc($rows))
        fputcsv($out, [$i++, $r['tanggal'], $r['namabarang'], $r['qty'], $r['keterangan']]);
    fclose($out);
    exit;
}

// ── FILTER TANGGAL ────────────────────────────────────────────────────────────
$dari = isset($_GET['dari']) ? mysqli_real_escape_string($conn, $_GET['dari']) : '';
$sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($conn, $_GET['sampai']) : '';
$where = '';
if ($dari && $sampai)
    $where = "AND m.tanggal BETWEEN '$dari' AND '$sampai'";
elseif ($dari)
    $where = "AND m.tanggal >= '$dari'";
elseif ($sampai)
    $where = "AND m.tanggal <= '$sampai'";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Barang Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        @media print {
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

        #printArea {
            display: none;
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
    </style>
</head>

<body class="sb-nav-fixed">

    <!-- TOPNAV -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">ditss kicau mania</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <div id="layoutSidenav">
        <!-- SIDENAV -->
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

        <!-- CONTENT -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Barang Masuk</h1>

                    <!-- Filter Tanggal -->
                    <div class="card mb-3 no-print">
                        <div class="card-body py-2">
                            <form method="get" class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label class="form-label mb-0 fw-semibold">Dari</label>
                                    <input type="date" name="dari" class="form-control form-control-sm"
                                        value="<?= htmlspecialchars($dari) ?>">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-0 fw-semibold">Sampai</label>
                                    <input type="date" name="sampai" class="form-control form-control-sm"
                                        value="<?= htmlspecialchars($sampai) ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="masuk.php" class="btn btn-sm btn-secondary ms-1">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex gap-2 flex-wrap no-print">
                            <!-- Tambah -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="fas fa-plus me-1"></i>Tambah Barang Masuk
                            </button>
                            <!-- Ekspor -->
                            <div class="btn-group">
                                <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-export me-1"></i>Ekspor Data
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item"
                                            href="?export=csv&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>">
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
                                        <th>Tanggal</th>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Keterangan/Penerima</th>
                                        <th class="no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = mysqli_query(
                                        $conn,
                                        "SELECT m.*, s.namabarang, s.stock, s.idbarang as sid
                                FROM masuk m
                                JOIN stock s ON s.idbarang = m.idbarang
                                WHERE 1=1 $where
                                ORDER BY m.tanggal DESC"
                                    );
                                    while ($data = mysqli_fetch_assoc($query)):
                                        $idmasuk = $data['idmasuk'];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($data['tanggal']) ?></td>
                                            <td><?= htmlspecialchars($data['namabarang']) ?></td>
                                            <td><?= $data['qty'] ?></td>
                                            <td>
                                                <?php if ($data['stock'] == 0): ?>
                                                    <span class="badge-habis">HABIS</span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($data['keterangan']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print">
                                                <!-- Detail -->
                                                <button type="button"
                                                    class="btn btn-sm btn-info text-white btn-detail-masuk"
                                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                                    data-id="<?= $idmasuk ?>"
                                                    data-tanggal="<?= htmlspecialchars($data['tanggal']) ?>"
                                                    data-barang="<?= htmlspecialchars($data['namabarang'], ENT_QUOTES) ?>"
                                                    data-qty="<?= $data['qty'] ?>"
                                                    data-ket="<?= htmlspecialchars($data['keterangan'], ENT_QUOTES) ?>"
                                                    data-stock="<?= $data['stock'] ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <!-- Edit -->
                                                <button type="button" class="btn btn-sm btn-warning btn-edit-masuk"
                                                    data-bs-toggle="modal" data-bs-target="#editModalMasuk"
                                                    data-id="<?= $idmasuk ?>" data-barang="<?= $data['sid'] ?>"
                                                    data-qty="<?= $data['qty'] ?>"
                                                    data-ket="<?= htmlspecialchars($data['keterangan'], ENT_QUOTES) ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <!-- Hapus -->
                                                <form method="post" class="d-inline"
                                                    onsubmit="return confirm('Hapus data masuk ini?');">
                                                    <input type="hidden" name="idmasuk" value="<?= $idmasuk ?>">
                                                    <button type="submit" name="hapusmasuk" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- container -->
            </main>

            <footer class="py-4 bg-light mt-auto no-print">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; ditss kicau mania <?= date('Y') ?></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>


    <!-- ══════════════════  AREA PRINT  ══════════════════════════════════════════ -->
    <div id="printArea">
        <h3 style="text-align:center">Laporan Barang Masuk — ditss kicau mania</h3>
        <p style="text-align:center;font-size:.85em">
            <?php if ($dari || $sampai): ?>
                Periode: <?= $dari ?: '…' ?> s/d <?= $sampai ?: '…' ?> &nbsp;|&nbsp;
            <?php endif; ?>
            Dicetak: <?= date('d-m-Y H:i:s') ?>
        </p>
        <table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:.88em">
            <thead style="background:#343a40;color:#fff">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $printQ = mysqli_query(
                    $conn,
                    "SELECT m.*, s.namabarang FROM masuk m
            JOIN stock s ON s.idbarang = m.idbarang
            WHERE 1=1 $where ORDER BY m.tanggal DESC"
                );
                $pi = 1;
                while ($pd = mysqli_fetch_assoc($printQ)):
                    ?>
                    <tr>
                        <td><?= $pi++ ?></td>
                        <td><?= $pd['tanggal'] ?></td>
                        <td><?= htmlspecialchars($pd['namabarang']) ?></td>
                        <td><?= $pd['qty'] ?></td>
                        <td><?= htmlspecialchars($pd['keterangan']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>


    <!-- ═══════════════════  MODAL: TAMBAH  ══════════════════════════════════════ -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Barang Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Barang</label>
                            <select name="barangnya" class="form-select" required>
                                <?php
                                $sel = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
                                while ($r = mysqli_fetch_assoc($sel)):
                                    ?>
                                    <option value="<?= $r['idbarang'] ?>"><?= htmlspecialchars($r['namabarang']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" name="qty" class="form-control" placeholder="0" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Penerima / Keterangan</label>
                            <input type="text" name="penerima" class="form-control"
                                placeholder="Nama penerima / keterangan" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="barangmasuk" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ════════════════════  MODAL: EDIT  ═══════════════════════════════════════ -->
    <div class="modal fade" id="editModalMasuk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Ubah Barang Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_idmasuk" name="idmasuk">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Barang</label>
                            <select id="edit_barangnya" name="barangnya" class="form-select" required>
                                <?php
                                $sel2 = mysqli_query($conn, "SELECT * FROM stock ORDER BY namabarang ASC");
                                while ($r = mysqli_fetch_assoc($sel2)):
                                    ?>
                                    <option value="<?= $r['idbarang'] ?>"><?= htmlspecialchars($r['namabarang']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" id="edit_qty" name="qty" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Penerima / Keterangan</label>
                            <input type="text" id="edit_keterangan" name="penerima" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="updatemasuk" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ═══════════════════  MODAL: DETAIL  ══════════════════════════════════════ -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Barang Masuk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailBodyMasuk">
                    <!-- Diisi via JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════  SCRIPTS  ═════════════════════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script>
        // ── Isi modal Edit ────────────────────────────────────────────────────────────
        document.querySelectorAll('.btn-edit-masuk').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('edit_idmasuk').value = this.dataset.id;
                document.getElementById('edit_barangnya').value = this.dataset.barang;
                document.getElementById('edit_qty').value = this.dataset.qty;
                document.getElementById('edit_keterangan').value = this.dataset.ket;
            });
        });

        // ── Isi modal Detail ──────────────────────────────────────────────────────────
        document.querySelectorAll('.btn-detail-masuk').forEach(btn => {
            btn.addEventListener('click', function () {
                const body = document.getElementById('detailBodyMasuk');
                const stok = parseInt(this.dataset.stock);
                const statusBadge = stok === 0
                    ? '<span class="badge bg-danger">HABIS</span>'
                    : '<span class="badge bg-success">TERSEDIA (' + stok + ')</span>';

                body.innerHTML = `
        <div class="row g-3">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th width="40%" class="text-muted">ID Transaksi</th>
                                    <td>#${this.dataset.id}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Tanggal Masuk</th>
                                    <td><i class="fas fa-calendar-alt me-1 text-info"></i>${this.dataset.tanggal}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Nama Barang</th>
                                    <td><strong>${this.dataset.barang}</strong></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Jumlah Masuk</th>
                                    <td>
                                        <span class="fs-5 fw-bold text-success">
                                            <i class="fas fa-plus-circle me-1"></i>${this.dataset.qty}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Keterangan</th>
                                    <td>${this.dataset.ket || '<em class="text-muted">-</em>'}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Stok Saat Ini</th>
                                    <td>${statusBadge}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>`;
            });
        });

        // ── Print / PDF ───────────────────────────────────────────────────────────────
        document.getElementById('btnPrint').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('printArea').style.display = 'block';
            window.print();
            setTimeout(() => { document.getElementById('printArea').style.display = 'none'; }, 1000);
        });
    </script>
</body>

</html