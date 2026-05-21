
<?php

$n = 64;

?>

<div style="padding: 1rem">
    <a href="index.php">< Kembali</a>
    <h3>
        SOAL 4 <br>
        =======================================================================<br>
        membuat tabel seperti di soal
    </h3>

    <table>
        <tbody>
        <?php
        echo '<tr>';
        for ($i=1; $i<=$n; $i++){
            if($i % 8 === 1){
                echo '<tr>';
            }

            $isWhiteClass = $i % 3 ===0 || $i % 4 === 0;
            $cellClass = $isWhiteClass ? 'cell-white' : '';
            echo "<td class=".$cellClass.">".$i."</td>";

            if($i % 8 === 0){
                echo '</tr>';
            }
        }
        ?>
        </tbody>
    </table>
</div>

<style>
    table, th, td {
        padding: 0.5rem;
    }

    td {
        background-color: black;
        color: white;
    }

    .cell-white{
        background-color: white;
        color: black;
    }
</style>
