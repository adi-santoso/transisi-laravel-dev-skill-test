<?php

$textExample = isset($_POST['text_example']) ? $_POST['text_example'] : null;


function getNGram($text, $n)
{
    $unigramArray = preg_split('/\s+/', trim($text));

    $nGramArray = [];
    for ($i = 0; $i < count($unigramArray); $i += $n) {

        $chunk = array_slice($unigramArray, $i, $n);

        if (count($chunk) == $n) {
            $nGramArray[] = implode(' ', $chunk);
        }
    }


    return implode(', ', $nGramArray);
}
?>

<div style="padding: 1rem">
    <a href="index.php">< Kembali</a>
    <h3>
        SOAL 3 <br>
        Buatlah sebuah fungsi dalam PHP untuk membentuk unigram, bigram, trigram dari sebuah string.<br>
        Contoh : bila fungsi diberikan input “Jakarta adalah ibukota negara Republik Indonesia”, maka akan menghasilkan output :<br>
        ● Unigram : jakarta, adalah, ibukota, negara, republik, indonesia<br>
        ● Bigram : jakarta adalah, ibukota negara, republik indonesia<br>
        ● Trigram : jakarta adalah ibukota, negara republik indonesia<br><br>

    </h3>


    <form method="post" action="test_3.php">
        <label for="text_example">Input Text</label>
        <input type="text" required name="text_example" value="<?php echo $textExample ?>">
        <button>Cek</button>
    </form>

    <?php

    if($textExample!==null){
        echo "<br> Unigram : ".getNGram($textExample, 1);
        echo "<br> Bigram : ".getNGram($textExample, 2);
        echo "<br> Trigram : ".getNGram($textExample, 3);
    }
    ?>
</div>
