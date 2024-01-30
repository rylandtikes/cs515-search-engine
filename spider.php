<?php

include 'database.php';
include 'password.php';

$lynx_file_name = "result.txt";

$keywords  = $argv[1];
$URL       = $argv[2];
$max_pages = $argv[3];

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error)
    die('Could not connect: ' . $conn->connect_error);

function extract_source($URL, $lynx_file_name)
{
    $cmd = "lynx -dump -source '" . $URL . "' > $lynx_file_name 2>&1";
    system($cmd, $return_value);
    ($return_value == 0) or die("error: $cmd");
    return $return_value;
}


function extract_title($lynx_file_name)
{
    $file    = file_get_contents($lynx_file_name);
    $pattern = '/<title>.*?<\/title>/';
    preg_match($pattern, $file, $matches);
    $title = strip_tags($matches[0]);
    return $title;
}


function get_keyword_id($keyword, $conn)
{
    # Find the ID of the input keyword from the keywords table.
    $sql = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
    echo ($sql . "\n\n");
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
        while ($row = $result->fetch_assoc())
            $kwID = $row['kwID'];
    else {
        $sql = "INSERT INTO keywords( keyword ) VALUES ( '$keyword' );";
        echo ($sql . "\n\n");
        $conn->query($sql);
        $sql = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
        echo ($sql . "\n\n");
        $result = $conn->query($sql);
        if ($result->num_rows > 0)
            while ($row = $result->fetch_assoc())
                $kwID = $row['kwID'];
    }
    return $kwID;
}


function get_url_id($URL, $title, $url_meta_keywords, $url_meta_description, $conn)
{
    # Find the ID of the input URL from the url_title table.
    $sql = "SELECT urlID FROM url_title WHERE url='$URL';";
    echo ($sql . "\n\n");
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
        while ($row = $result->fetch_assoc())
            $urlID = $row['urlID'];
    else {
        $sql = "INSERT INTO url_title( url, title, keywords, description )";
        $sql .= " VALUES ( '$URL', '$title', '$url_meta_keywords',";
        $sql .= " '$url_meta_description' );";
        echo ($sql . "\n\n");
        $conn->query($sql);
        $sql = "SELECT urlID FROM url_title WHERE url='$URL';";
        echo ($sql . "\n\n");
        $result = $conn->query($sql);
        if ($result->num_rows > 0)
            while ($row = $result->fetch_assoc())
                $urlID = $row['urlID'];
    }
    return $urlID;
}


function is_keyword_found($title, $keyword)
{
    $found = false;
    if (substr_count(strtolower($title), $keyword) != 0) {
        $found = true;
    }
    return $found;
}


function update_inverted_list($kwID, $urlID, $conn)
{
    $sql = "INSERT INTO www_index VALUES ( '$kwID', '$urlID' );";
    echo ($sql . "\n\n");
    $conn->query($sql);
}


$stopwords_array = explode("\n", file_get_contents('stopwords.txt'));

extract_source($URL, $lynx_file_name);

# Use regular expression to extract title, truncate to 160 chars.
$title = substr(extract_title($lynx_file_name), 0, 160);

# Use built in function to extract meta tags from URL'
$tags = get_meta_tags($URL);

# Extract keywords from tags, truncate to 160 characters.
$url_meta_keywords = substr($tags['keywords'], 0, 160);

# Extract description from tags, truncate to 160 characters.
$url_meta_description = substr($tags['description'], 0, 160);

$keywords_array = array_filter(explode(' ', $keywords));

foreach ($keywords_array as $keyword) {
    if (in_array($keyword, $stopwords_array)) {
        echo "keyword $keyword removed\n";
        continue;
    } else {
        $kwID = get_keyword_id($keyword, $conn);
        
        $urlID = get_url_id($URL, $title, $url_meta_keywords, $url_meta_description, $conn);
        
        $found = is_keyword_found($title, $keyword);
        
        # Update the inverted list if the keyword is found.
        if ($found == true) {
            update_inverted_list($kwID, $urlID, $conn);
        }
        
    }
}

$conn->close();

?>