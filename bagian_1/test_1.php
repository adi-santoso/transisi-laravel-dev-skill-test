
<?php
$nilai = array(72, 65, 73, 78, 75, 74, 90, 81, 87, 65, 55, 69, 72, 78, 79, 91, 100, 40, 67, 77, 86);

$nilaiDesc = $nilai;
rsort($nilaiDesc);

$nilaiAsc = $nilai;
sort($nilaiAsc);

$avgValue =  array_sum($nilai) / count($nilai);
$topSevenVal = array_slice($nilaiDesc, 0, 7);
$lowestSevenVal = array_slice($nilaiAsc, 0, 7);
?>

<div style="padding: 1rem">
    <a href="index.php">< Kembali</a>
    <h3>
        SOAL 1 <br>
        =======================================================================<br>
        Nilai ujian sebuah kelas tersimpan dalam sebuah string berikut : <br>
        $nilai = “72 65 73 78 75 74 90 81 87 65 55 69 72 78 79 91 100 40 67 77 86”;  <br>
        Buatlah sebuah PHP script untuk menentukan (1) nilai rata-rata, (2) 7 nilai tertinggi, (3) 7 nilai terendah dari nilai-nilai di atas
    </h3>

    <table>
        <tbody>
        <tr>
            <td>Nilai rata-rata</td>
            <td><?php echo number_format($avgValue, 2) ?> </td>
        </tr>

        <tr>
            <td>7 Nilai tertinggi</td>
            <td>
                <?php
                foreach ($topSevenVal as $val){
                    echo $val;
                    echo ' , ';
                }
                ?>
            </td>
        </tr>

        <tr>
            <td>7 Nilai terendah</td>
            <td>
                <?php
                foreach ($lowestSevenVal as $val){
                    echo $val;
                    echo ' , ';
                }
                ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<style>
    table, th, td {
        border: 1px solid;
        padding: 0.5rem;
    }
</style>
