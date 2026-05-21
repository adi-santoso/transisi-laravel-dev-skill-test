<?php

$textExample = isset($_POST['text_example']) ? $_POST['text_example'] : null;

function countLowerCaseCharacter($text)
{
    return preg_match_all('/[a-z]/', $text);
}
?>

<div style="padding: 1rem">
    <a href="index.php">< Kembali</a>
    <h3>
        SOAL 2 <br>
        ======================================================================= <br>
        Buatlah sebuah fungsi dalam PHP untuk menentukan jumlah huruf kecil dalam sebuah string.<br>
        Contoh : bila fungsi diberikan input “TranSISI” maka akan menghasilkan output : “TranSISI” mengandung 3 buah huruf kecil <br> <br>
    </h3>


    <form method="post" action="test_2.php">
        <label for="text_example">Input Text</label>
        <input type="text" required name="text_example" value="<?php echo $textExample ?>">
        <button>Cek</button>
    </form>

    <?php
    if($textExample!==null){
        echo '"'.$textExample."\" mengandung ". countLowerCaseCharacter($textExample). " buah huruf kecil";
    }
    ?>
</div>
