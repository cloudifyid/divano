<?php
include_once 'config.php'; // Pastikan koneksi sudah dimasukkan

$update = (isset($_GET['action']) && $_GET['action'] == 'update');
if ($update) {
    $sql = $connection->query("SELECT * FROM mahasiswa WHERE nim='".$connection->real_escape_string($_GET['key'])."'");
    $row = $sql->fetch_assoc();
}

$jurusan_list = [];
$query = $connection->query("SELECT DISTINCT prodi FROM master");
while ($data = $query->fetch_assoc()) {
    $jurusan_list[] = $data['prodi'];
}

// Handle form tambah/edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_GET['action']) || $_GET['action'] != 'import')) {
    $nim = $connection->real_escape_string($_POST['nim']);
    $nama = $connection->real_escape_string($_POST['nama']);
    $alamat = $connection->real_escape_string($_POST['alamat']); // diisi dari dropdown jurusan
    $jk = $connection->real_escape_string($_POST['jenis_kelamin']);
    $tahun = date("Y");

    $validasi = false; $err = false;
    if ($update) {
        $key = $connection->real_escape_string($_GET['key']);
        $sql = "UPDATE mahasiswa SET nim='$nim', nama='$nama', alamat='$alamat', jenis_kelamin='$jk', tahun_mengajukan='$tahun' WHERE nim='$key'";
    } else {
        $sql = "INSERT INTO mahasiswa VALUES ('$nim', '$nama', '$alamat', '$jk', '$tahun')";
        $validasi = true;
    }

    if ($validasi) {
        $q = $connection->query("SELECT nim FROM mahasiswa WHERE nim='$nim'");
        if ($q->num_rows) {
            echo alert("$nim sudah terdaftar!", "?page=mahasiswa");
            $err = true;
        }
    }

    if (!$err && $connection->query($sql)) {
        echo alert("Berhasil!", "?page=mahasiswa");
    } else {
        echo alert("Gagal!", "?page=mahasiswa");
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $key = $connection->real_escape_string($_GET['key']);
    $connection->query("DELETE FROM mahasiswa WHERE nim='$key'");
    echo alert("Berhasil!", "?page=mahasiswa");
}

// Fungsi deteksi delimiter CSV
function detectDelimiter($file) {
    $delimiters = [",", ";", "\t"];
    $line = fgets($file);
    rewind($file);
    $counts = [];
    foreach ($delimiters as $d) {
        $counts[$d] = count(str_getcsv($line, $d));
    }
    arsort($counts);
    return key($counts);
}

// Handle import CSV
if (isset($_GET['action']) && $_GET['action'] == 'import' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_FILES['csv_file']['error'] === 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $file = fopen($filename, 'r');
        $delimiter = detectDelimiter($file);
        fgetcsv($file, 1000, $delimiter); // skip header

        $inserted = 0;
        while (($data = fgetcsv($file, 1000, $delimiter)) !== FALSE) {
            if (count($data) < 5) continue;

            list($nim, $nama, $alamat, $jk, $tahun) = array_map([$connection, 'real_escape_string'], $data);

            $cek = $connection->query("SELECT nim FROM mahasiswa WHERE nim='$nim'");
            if ($cek->num_rows == 0) {
                $connection->query("INSERT INTO mahasiswa (nim, nama, alamat, jenis_kelamin, tahun_mengajukan) VALUES ('$nim', '$nama', '$alamat', '$jk', '$tahun')");
                $inserted++;
            }
        }
        fclose($file);
        echo alert("Import berhasil! $inserted data ditambahkan.", "?page=mahasiswa");
    } else {
        echo alert("Gagal mengupload file!", "?page=mahasiswa");
    }
}
?>

<!-- HTML Form dan Tabel -->
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-<?= ($update) ? "warning" : "info" ?>">
            <div class="panel-heading"><h3 class="text-center"><?= ($update) ? "EDIT" : "TAMBAH" ?></h3></div>
            <div class="panel-body">
                <form action="<?=$_SERVER['REQUEST_URI']?>" method="POST">
                    <div class="form-group">
                        <label for="nim">NIM</label>
                        <input type="text" name="nim" class="form-control" <?= (!$update) ?: 'value="'.$row["nim"].'"' ?>>
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" <?= (!$update) ?: 'value="'.$row["nama"].'"' ?>>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Jurusan</label>
                        <select name="alamat" class="form-control" id="alamat">
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($jurusan_list as $jurusan): ?>
                                <option value="<?= $jurusan ?>" <?= ($update && $row["alamat"] == $jurusan) ? 'selected' : '' ?>>
                                    <?= $jurusan ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-control" name="jenis_kelamin">
                            <option>---</option>
                            <option value="Laki-laki" <?= (!$update) ?: (($row["jenis_kelamin"] != "Laki-laki") ?: 'selected="on"') ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (!$update) ?: (($row["jenis_kelamin"] != "Perempuan") ?: 'selected="on"') ?>>Perempuan</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-<?= ($update) ? "warning" : "info" ?> btn-block">Simpan</button>
                    <?php if ($update): ?>
                        <a href="?page=mahasiswa" class="btn btn-info btn-block">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel panel-info">
            <div class="panel-heading"><h3 class="text-center">DAFTAR MAHASISWA</h3></div>
            <div class="panel-body">

                <!-- FORM IMPORT CSV -->
                <form action="?page=mahasiswa&action=import" method="POST" enctype="multipart/form-data" class="form-inline" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="csv_file">Import CSV:</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Import</button>
                </form>

                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Jurusan</th>
                            <th>Jenis Kelamin</th>
                            <th>Tahun</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if ($query = $connection->query("SELECT * FROM mahasiswa")): ?>
                            <?php while($row = $query->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $row['nim'] ?></td>
                                <td><?= $row['nama'] ?></td>
                                <td><?= $row['alamat'] ?></td>
                                <td><?= $row['jenis_kelamin'] ?></td>
                                <td><?= $row['tahun_mengajukan'] ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?page=mahasiswa&action=update&key=<?= $row['nim'] ?>" class="btn btn-warning btn-xs">Edit</a>
                                        <a href="?page=mahasiswa&action=delete&key=<?= $row['nim'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
