<?php

$password         = $argv[1];
$entered_password = $argv[2];
$interface        = $argv[3];

if ($password == $entered_password) {
    header("Content-type: text/plain");
    if ($interface == 1) {
        $file = fopen("clear.php", "r") or exit("Unable to open file!");
        echo ("\n\n\n ============== clear.php ==============\n\n\n");
        while (!feof($file))
            echo fgets($file);
        fclose($file);
    } elseif ($interface == 2) {
        $file = fopen("index.php", "r") or exit("Unable to open file!");
        echo ("\n\n\n ============== index.php ==============\n\n\n");
        while (!feof($file))
            echo fgets($file);
        fclose($file);
        echo ("\n\n\n ============== spider.php ==============\n\n\n");
        $file = fopen("spider.php", "r") or exit("Unable to open file!");
        while (!feof($file))
            echo fgets($file);
        fclose($file);
    } elseif ($interface == 3) {
        $file = fopen("list.php", "r") or exit("Unable to open file!");
        echo ("\n\n\n ============== list.php ==============\n\n\n");
        while (!feof($file))
            echo fgets($file);
        fclose($file);
    } else
        echo "No such interface: " . $interface;
    
    echo ("\n\n\n ============== check.php ============== \n\n\n");
    $file = fopen("check.php", "r") or exit("Unable to open file!");
    while (!feof($file))
        echo fgets($file);
    fclose($file);
} else {
    header("Content-type: text/plain");
    echo "Wrong password entered: ";
    echo $entered_password;
}
?>