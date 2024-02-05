<?php

include 'password.php';


# Remove leading and trailing spacing.
$keywords = trim($_POST["keywords"]);
$URL      = trim($_POST["URL"]);

$entered_password = trim($_POST['password']);
$interface        = trim($_POST['interface']);

#No more than 500 pages are indexed
$max_pages = min(500, trim($_POST['maxpages']));

$meta      = array(
    ";",
    ">",
    ">>",
    "<",
    "*",
    "?",
    "&",
    "|"
);
$keywords = strtolower(str_replace($meta, "", $keywords));
$URL      = str_replace($meta, "", $URL);

if ($_POST['act'] == "Clear system") {
    header("Content-type: text/plain");
    system("/usr/bin/php  clear.php");
}

elseif ($_POST['act'] == "Index web pages") {
    header("Content-type: text/plain");
    system("/usr/bin/php -d max_execution_time=400  spider.php  $URL $max_pages");
} elseif ($_POST['act'] == "List indexes") {
    header("Content-type: text/html");
    system("/usr/bin/php  list.php \"$keywords\"");
} elseif ($_POST['act'] == "Display source") {
    header("Content-type:  text/plain");
    system("/usr/bin/php  check.php  $password  $entered_password  $interface");
} elseif ($_POST["act"] == "Help") {
    header("Content-type: text/html");
    $file = fopen("help.html", "r") or exit("Unable to open file!");
    while (!feof($file))
        echo fgets($file);
    fclose($file);
}

else {
    header("Content-type: text/html");
    echo ("<html><body>");
    echo ("<h3>No such option: " . $_POST["act"] . "</h3>");
    echo ("</body></html>");
}

?>