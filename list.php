<?php

include 'database.php';
include 'password.php';

$keywords     = $argv[1];
$keywords_arr = explode(' ', $keywords);
$imploded_arr = implode("','", $keywords_arr);

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error)
    die('Could not connect: ' . $conn->connect_error);

if ($keywords) {
    # Query for when keywords entered
    $sql = "SELECT count(k.keyword) AS keyword_count, group_concat(k.keyword";
    $sql .= " ORDER BY k.keyword ASC SEPARATOR ' ') AS keyword_group, u.url,";
    $sql .= " u.title, u.keywords, u.description FROM keywords k, url_title u,";
    $sql .= " www_index w WHERE k.keyword IN ('" . $imploded_arr . "')";
    $sql .= " AND k.kwID=w.kwID AND w.urlID=u.urlID GROUP BY u.url";
    $sql .= " ORDER BY keyword_count DESC;";
}

else {
    # Query for when no keywords entered
    $sql = "SELECT count(k.keyword) AS keyword_count, group_concat(k.keyword";
    $sql .= " ORDER BY k.keyword ASC SEPARATOR ' ') AS keyword_group, u.url, u.title,";
    $sql .= "  u.keywords, u.description FROM keywords k, url_title u, www_index w";
    $sql .= "  WHERE k.keyword LIKE '%%' AND k.kwID=w.kwID AND w.urlID=u.urlID";
    $sql .= "  GROUP BY u.url ORDER BY keyword_count DESC;";
}

echo ($sql . "<br /><br />");
echo ("<center><table id='table3' width='96%' border='1' cellspacing='1' cellpadding='3' color='navy' bgcolor='lightyellow' class='shadow'>");
echo ("<tr><th width='05%'>No.</th>");
echo ("<th width='15%'>Keywords</th>");
echo ("<th width='05%'>Keywords Count</th>");
echo ("<th width='15%'>URL</th>");
echo ("<th width='20%'>Title</th>");
echo ("<th width='20%'>Meta Keywords</th>");
echo ("<th width='20%'>Description</th></tr>");

$n      = 1;
$result = $conn->query($sql);
if ($result->num_rows > 0)
    while ($row = $result->fetch_assoc()) {
        echo ("<tr><td>" . $n++ . "</td><td>" . $row[keyword_group]);
        echo ("</td><td>" . $row[keyword_count] . "</td>");
        echo ("</td><td><a target='_blank' href='" . $row[url] . "'>" . $row[url]);
        echo ("</a></td><td>" . $row[title] . "</td>");
        echo ("</td><td>" . $row[keywords] . "</td>");
        echo ("</td><td>" . $row[description] . "</td></tr>");
    }
echo ("</table>");

$conn->close();

?>