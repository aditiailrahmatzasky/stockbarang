<?php
session_start();

//membuat konekisi ke database
$conn = mysqli_connect("localhost", "root", "", "stockbarang");


//menambahkan barang baru
if (isset($_POST['addnewbarang'])) {
    $namabarang = $_POST['namabarang'];
    $deskripsi = $_POST['deskripsi'];
    $stock = $_POST['stock'];

    $addtotable = mysqli_query($conn, "INSERT INTO `stock`(`namabarang`, `deskripsi`, `stock`) VALUES ('$namabarang','$deskripsi','$stock')");
    if ($addtotable) {
        header('location:index.php');
    } else {
        echo 'Gagal';
        header('location:index.php');
    }
}
;

// Update barang
if (isset($_POST['updatebarang'])) {
    $idbarang = $_POST['idbarang'];
    $namabarang = $_POST['namabarang'];
    $deskripsi = $_POST['deskripsi'];
    $stock = $_POST['stock'];

    $update = mysqli_query($conn, "UPDATE stock SET namabarang='$namabarang', deskripsi='$deskripsi', stock='$stock' WHERE idbarang='$idbarang'");
    if ($update) {
        header('location:index.php');
    } else {
        echo 'Gagal';
        header('location:index.php');
    }
}

// Delete barang
if (isset($_POST['hapusbarang'])) {
    $idbarang = $_POST['idbarang'];
    $delete = mysqli_query($conn, "DELETE FROM stock WHERE idbarang='$idbarang'");
    if ($delete) {
        header('location:index.php');
    } else {
        echo 'Gagal';
        header('location:index.php');
    }
}

// Update barang masuk
if (isset($_POST['updatemasuk'])) {
    $idmasuk = $_POST['idmasuk'];
    $barangnya = $_POST['barangnya'];
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    // Ambil data lama
    $cekdata = mysqli_query($conn, "select * from masuk where idmasuk='$idmasuk'");
    $ambil = mysqli_fetch_array($cekdata);
    $qtylama = $ambil['qty'];
    $baranglama = $ambil['idbarang'];

    // Hitung selisih qty
    $selisih = $qty - $qtylama;

    // Update tabel masuk
    $update = mysqli_query($conn, "UPDATE masuk SET idbarang='$barangnya', keterangan='$penerima', qty='$qty' WHERE idmasuk='$idmasuk'");

    // Update stock
    $cekstock = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambilstock = mysqli_fetch_array($cekstock);
    $stocksekarang = $ambilstock['stock'];
    $stockbaru = $stocksekarang + $selisih;
    $updatestock = mysqli_query($conn, "update stock set stock='$stockbaru' where idbarang='$barangnya'");

    if ($update && $updatestock) {
        header('location:masuk.php');
    } else {
        echo 'Gagal';
        header('location:masuk.php');
    }
}

// Delete barang masuk
if (isset($_POST['hapusmasuk'])) {
    $idmasuk = $_POST['idmasuk'];

    // Ambil data untuk update stock
    $cekdata = mysqli_query($conn, "select * from masuk where idmasuk='$idmasuk'");
    $ambil = mysqli_fetch_array($cekdata);
    $qty = $ambil['qty'];
    $barangnya = $ambil['idbarang'];

    // Update stock (kurangi)
    $cekstock = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambilstock = mysqli_fetch_array($cekstock);
    $stocksekarang = $ambilstock['stock'];
    $stockbaru = $stocksekarang - $qty;
    $updatestock = mysqli_query($conn, "update stock set stock='$stockbaru' where idbarang='$barangnya'");

    // Hapus dari masuk
    $delete = mysqli_query($conn, "DELETE FROM masuk WHERE idmasuk='$idmasuk'");

    if ($delete && $updatestock) {
        header('location:masuk.php');
    } else {
        echo 'Gagal';
        header('location:masuk.php');
    }
}

// Update barang keluar
if (isset($_POST['updatekeluar'])) {
    $idkeluar = $_POST['idkeluar'];
    $barangnya = $_POST['barangnya'];
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    // Ambil data lama
    $cekdata = mysqli_query($conn, "select * from keluar where idkeluar='$idkeluar'");
    $ambil = mysqli_fetch_array($cekdata);
    $qtylama = $ambil['qty'];
    $baranglama = $ambil['idbarang'];

    // Hitung selisih qty
    $selisih = $qtylama - $qty; // Karena keluar, qty lama dikurangi, qty baru ditambah ke stock

    // Update tabel keluar
    $update = mysqli_query($conn, "UPDATE keluar SET idbarang='$barangnya', penerima='$penerima', qty='$qty' WHERE idkeluar='$idkeluar'");

    // Update stock
    $cekstock = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambilstock = mysqli_fetch_array($cekstock);
    $stocksekarang = $ambilstock['stock'];
    $stockbaru = $stocksekarang + $selisih;
    $updatestock = mysqli_query($conn, "update stock set stock='$stockbaru' where idbarang='$barangnya'");

    if ($update && $updatestock) {
        header('location:keluar.php');
    } else {
        echo 'Gagal';
        header('location:keluar.php');
    }
}

// Delete barang keluar
if (isset($_POST['hapuskeluar'])) {
    $idkeluar = $_POST['idkeluar'];

    // Ambil data untuk update stock
    $cekdata = mysqli_query($conn, "select * from keluar where idkeluar='$idkeluar'");
    $ambil = mysqli_fetch_array($cekdata);
    $qty = $ambil['qty'];
    $barangnya = $ambil['idbarang'];

    // Update stock (tambah kembali)
    $cekstock = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambilstock = mysqli_fetch_array($cekstock);
    $stocksekarang = $ambilstock['stock'];
    $stockbaru = $stocksekarang + $qty;
    $updatestock = mysqli_query($conn, "update stock set stock='$stockbaru' where idbarang='$barangnya'");

    // Hapus dari keluar
    $delete = mysqli_query($conn, "DELETE FROM keluar WHERE idkeluar='$idkeluar'");

    if ($delete && $updatestock) {
        header('location:keluar.php');
    } else {
        echo 'Gagal';
        header('location:keluar.php');
    }
}

//Menambah barang masuk
if (isset($_POST['barangmasuk'])) {
    $barangnya = $_POST['barangnya'];
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    $cekstocksekarang = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambildatanya = mysqli_fetch_array($cekstocksekarang);

    $stocksekarang = $ambildatanya['stock'];
    $tambahstocksekarangdenanquetity = $stocksekarang + $qty;

    $addtomasuk = mysqli_query($conn, "insert into masuk (idbarang, keterangan, qty) values('$barangnya','$penerima', '$qty')");
    $updatestockmasuk = mysqli_query($conn, "update stock set stock=$tambahstocksekarangdenanquetity where idbarang='$barangnya'");
    if ($addtomasuk && $updatestockmasuk) {
        header('location:masuk.php');
    } else {
        echo 'Gagal';
        header('location:masuk.php');
    }
}

//Menambah barang keluar
if (isset($_POST['addbarangkeluar'])) {
    $barangnya = $_POST['barangnya'];
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    $cekstocksekarang = mysqli_query($conn, "select * from stock where idbarang='$barangnya'");
    $ambildatanya = mysqli_fetch_array($cekstocksekarang);

    $stocksekarang = $ambildatanya['stock'];

    if ($stocksekarang >= $qty) {
        // Kalau barang cukup

        $tambahstocksekarangdenanquetity = $stocksekarang - $qty;

        $addtokeluar = mysqli_query($conn, "insert into keluar (idbarang, penerima, qty) values('$barangnya','$penerima', '$qty')");
        $updatestockmasuk = mysqli_query($conn, "update stock set stock=$tambahstocksekarangdenanquetity where idbarang='$barangnya'");
        if ($addtokeluar && $updatestockmasuk) {
            header('location:keluar.php');
        } else {
            echo 'Gagal';
            header('location:keluar.php');
        }
    } else {
        //kalau barang gak cukup
        echo "
        <script>
        alert('Stock saat ini tidak mencukupi!');
        window.location='keluar.php';
        </script>
        ";
    }
}

?>