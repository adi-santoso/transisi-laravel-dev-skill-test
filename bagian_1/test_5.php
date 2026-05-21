<?php

$textExample = isset($_POST['text_example']) ? $_POST['text_example'] : null;
$alphabet = range('A', 'Z');

function encrypt($text){

    $text = str_replace(" ", "", $text);
    $arrayChar = str_split($text);
    $encrypted = "";

    for ($i=0; $i<count($arrayChar); $i++){
        if($i%2!==0){
            $encrypted = $encrypted . shiftAlphabet($arrayChar[$i], -$i-1);;
        } else {
            $encrypted = $encrypted . shiftAlphabet($arrayChar[$i], $i+1);;
        }
    }

    return $encrypted;
}

function shiftAlphabet($char, $shift)
{
    $start = ord('A');
    $index = ord($char) - $start;
    $newIndex = ($index + $shift + 26) % 26;
    return chr($start + $newIndex);
}

?>

<div style="padding: 1rem">
    <a href="index.php">< Kembali</a>
    <h3>
        SOAL 5 <br>
        ======================================================================= <br>
        Buatlah sebuah fungsi “enkripsi”, yang apabila diberikan input DFHKNQ akan memberikan output EDKGSK<br><br>
    </h3>


    <form method="post" action="test_5.php">
        <label for="text_example">Input Text</label>
        <input type="text" required name="text_example" value="<?php echo $textExample ?>">
        <button>Cek</button>
    </form>

    <?php
    if($textExample!==null){
        echo "Hasil Encrypt = ".encrypt($textExample);
    }
    ?>
</div>
