<div class="row">
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading"><h3 class="text-center">Laporan Nilai Seluruh Mahasiswa</h3></div>
            <div class="panel-body">
                <form class="form-inline" action="<?=$_SERVER["REQUEST_URI"]?>" method="post">
                    <label for="tahun">Tahun :</label>
                    <select class="form-control" name="tahun">
                        <option value="">---</option>
                        <?php 
                        $tahun_query = $connection->query("SELECT DISTINCT tahun_mengajukan FROM mahasiswa ORDER BY tahun_mengajukan DESC");
                        while ($tahun = $tahun_query->fetch_assoc()): 
                            $selected = (isset($_POST['tahun']) && $_POST['tahun'] == $tahun['tahun_mengajukan']) ? 'selected' : '';
                        ?>
                            <option value="<?=$tahun['tahun_mengajukan']?>" <?=$selected?>><?=$tahun['tahun_mengajukan']?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </form>

                <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["tahun"])): ?>
                <?php
                $tahun = (int) $_POST["tahun"];
                $q = $connection->query("SELECT b.kd_beasiswa, b.nama, h.nilai, m.nama AS mahasiswa, m.nim, 
                    (SELECT MAX(nilai) FROM hasil WHERE nim=h.nim) AS nilai_max 
                    FROM mahasiswa m 
                    JOIN hasil h ON m.nim=h.nim 
                    JOIN beasiswa b ON b.kd_beasiswa=h.kd_beasiswa 
                    WHERE m.tahun_mengajukan='$tahun'");
                
                $beasiswa = []; 
                $data = [];
                
                while ($r = $q->fetch_assoc()) {
                    $beasiswa[$r["kd_beasiswa"]] = $r["nama"];
                    $d = []; // reset array
                    $s = $connection->query("SELECT b.nama, a.nilai FROM hasil a JOIN beasiswa b USING(kd_beasiswa) WHERE a.nim=$r[nim] AND a.tahun=$tahun");
                    while ($rr = $s->fetch_assoc()){
                        $d[$rr['nama']] = $rr['nilai'];
                    }
                    if (!empty($d)) {
                        $m = max($d);
                        $k = array_search($m, $d);
                        $data[$r["nim"]."-".$r["mahasiswa"]."-".$r["nilai_max"]."-".$k][$r["kd_beasiswa"]] = $r["nilai"];
                    }
                }
                ?>
                <hr>

                <!-- Tombol Export dan Print -->
                <div style="margin-bottom: 10px;">
                    <button class="btn btn-success" onclick="exportTableToExcel('laporan', 'laporan_mahasiswa')">Export Excel</button>
                    <button class="btn btn-info" onclick="printTable()">Print</button>
                
                </div>

                <div id="laporan">
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>NIM</th>
                                <th>Nama</th>
                                <?php foreach ($beasiswa as $val): ?>
                                    <th><?=$val?></th>
                                <?php endforeach; ?>
                                <th>Nilai Maksimal</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($data as $key => $val): ?>
                            <tr>
                                <?php $x = explode("-", $key); ?>
                                <td><?=$x[0]?></td>
                                <td><?=$x[1]?></td>
                                <?php foreach ($beasiswa as $kd => $v): ?>
                                    <td><?= isset($val[$kd]) ? number_format($val[$kd], 1) : '-' ?></td>
                                <?php endforeach; ?>
                                <td><?=number_format($x[2], 1)?></td>
                                <td><?=$x[3]?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>

                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Library DataTables, jsPDF -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// Inisialisasi DataTables
$(document).ready(function() {
    $('#datatable').DataTable();
});

// Export table ke Excel
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    filename = filename ? filename + '.xls' : 'excel_data.xls';

    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);

    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else{
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}

// Print table
function printTable() {
    var divToPrint = document.getElementById('laporan');
    var newWin = window.open('');
    newWin.document.write('<html><head><title>Print</title>');
    newWin.document.write('<link rel="stylesheet" href="assets/css/bootstrap.min.css">');
    newWin.document.write('</head><body>');
    newWin.document.write('<h3 class="text-center">Laporan Nilai Seluruh Mahasiswa</h3><br>');
    newWin.document.write(divToPrint.outerHTML);
    newWin.document.write('</body></html>');
    newWin.print();
    newWin.close();
}

// Export PDF
function exportPDF() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF('landscape');
    doc.text("Laporan Nilai Seluruh Mahasiswa", 14, 20);
    var elementHTML = document.getElementById('datatable');
    doc.autoTable({ html: '#datatable', startY: 30 });
    doc.save('laporan_mahasiswa.pdf');
}
</script>

<script>
function openBeasiswaLinks() {
    const urls = [
        "https://cloudify.biz.id/spk/index.php?page=perhitungan&beasiswa",
        "https://cloudify.biz.id/spk/index.php?page=perhitungan&beasiswa",
        "https://cloudify.biz.id/spk/index.php?page=perhitungan&beasiswa"
    ];

    urls.forEach(url => {
        window.open(url, '_blank');
    });
}
</script>

