<?php

include 'database.php';
include 'password.php';

$lynx_file_name = "result.txt";
$url_file_name  = "urls.txt";

$seed_url  = $argv[1];
$max_pages = $argv[2];

$do_not_crawl = array(
    '.3gp' => true,
    '.aac' => true,
    '.ai' => true,
    '.aiff' => true,
    '.asf' => true,
    '.asx' => true,
    '.au' => true,
    '.avi' => true,
    '.bin' => true,
    '.bmp' => true,
    '.css' => true,
    '.doc' => true,
    '.drw' => true,
    '.dxf' => true,
    '.eps' => true,
    '.exe' => true,
    '.gif' => true,
    '.jpeg' => true,
    '.jpg' => true,
    '.m4a' => true,
    '.mid' => true,
    '.mng' => true,
    '.mov' => true,
    '.mp3' => true,
    '.mp4' => true,
    '.mpg' => true,
    '.ogg' => true,
    '.pct' => true,
    '.pdf' => true,
    '.png' => true,
    '.ps' => true,
    '.psp' => true,
    '.pst' => true,
    '.qt' => true,
    '.ra' => true,
    '.rar' => true,
    '.rm' => true,
    '.rss' => true,
    '.svg' => true,
    '.swf' => true,
    '.tif' => true,
    '.tiff' => true,
    '.wav' => true,
    '.wma' => true,
    '.wmv' => true,
    '.xml' => true,
    '.zip' => true,
    'st)}' => true,
    '.xls' => true,
    '.xsl' => true
);

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error)
    die('Could not connect: ' . $conn->connect_error);


function extract_urls($url, $seed_url, $url_file_name)
{
    $cmd = "lynx --listonly --nonumbers --display_charset=utf-8 --dump '$url' | grep ^$seed_url | uniq > $url_file_name 2>&1";
    system($cmd, $return_value);
    return $return_value;
}


function extract_source($URL, $lynx_file_name)
{
    $cmd = "lynx -dump -source '$URL' | grep '<title>' > $lynx_file_name 2>&1";
    system($cmd, $return_value);
    return $return_value;
}


function extract_title($lynx_file_name)
{
    $file    = file_get_contents($lynx_file_name);
    $pattern = '/<title>(.*?)<\/title>/';
    preg_match($pattern, $file, $matches);
    $title = strtolower(trim(strip_tags($matches[0])));
    return $title;
}

function get_keyword_id($keyword, $conn)
{
    $sql    = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row  = $result->fetch_assoc();
        $kwID = $row['kwID'];
    } else {
        $sql = "INSERT INTO keywords( keyword ) VALUES ( '$keyword' );";
        $conn->query($sql);
        $kwID = $conn->insert_id;
    }
    return $kwID;
}


function get_url_id($URL, $title, $keywordsID, $descriptionID, $conn)
{
    $sql    = "SELECT urlID FROM url_title WHERE url='$URL';";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row   = $result->fetch_assoc();
        $urlID = $row['urlID'];
    } else {
        $sql = "INSERT INTO url_title( url, title, keywordsID, descriptionID )";
        $sql .= " VALUES ( '$URL', '$title',";
        $sql .= " $keywordsID, $descriptionID );";
        $conn->query($sql);
        $urlID = $conn->insert_id;
    }
    return $urlID;
}


function update_inverted_list($kwID, $urlID, $conn)
{
    $sql = "INSERT INTO www_index VALUES ( '$kwID', '$urlID' );";
    #echo ($sql . "\n\n");
    $conn->query($sql);
}


function update_meta_description($description, $conn)
{
    $sql = "INSERT INTO meta_description ( description ) VALUES ( '$description' );";
    $conn->query($sql);
    $descriptionID = $conn->insert_id;
    return $descriptionID;
}

function update_meta_keywords($keywords, $conn)
{
    $sql = "INSERT INTO meta_keywords( keywords ) VALUES ( '$keywords' );";
    $conn->query($sql);
    $keywordsID = $conn->insert_id;
    return $keywordsID;
}

$time_start = microtime(true);

# Load stopwards from file into array
$stopwords_array = explode("\n", file_get_contents('stopwords.txt'));

# create associative array for O(1) lookup
$stopwords = [];
foreach ($stopwords_array as $val) {
    $stopwords[$val] = true;
}

extract_urls($seed_url, $seed_url, $url_file_name);

$url_stack = array_unique(explode("\n", file_get_contents($url_file_name)));

$crawl_time_start = microtime(true);

$crawled_url = [];
$crawled_url[$seed_url] = true;

while (!empty($url_stack)) {

    $url = array_pop($url_stack);

    $file_ex3 = substr($url, -3);
    $file_ex4 = substr($url, -4);
    $file_ex5 = substr($url, -5);

    $file_ex = explode(".", string, limit);
    
    if (count($crawled_url) >= $max_pages) {
        echo "Reached max urls $max_pages\n";
        break;
    }
    # do not crawl some files
    if (empty($url) || isset($do_not_crawl[$file_ex3]) || isset($do_not_crawl[$file_ex4]) || isset($do_not_crawl[$file_ex5])) {
        continue;
    }

    if (isset($crawled_url[$url])) {
        #echo "$url already crawled skipping\n";
        continue;
    }

    extract_urls($url, $seed_url, $url_file_name);
    $next_url_array = explode("\n", file_get_contents($url_file_name));
    $url_stack      = array_unique(array_merge($next_url_array, $url_stack));
    $crawled_url[$url] = true;
}
echo 'Extracting URLs execution time in seconds: ' . (microtime(true) - $crawl_time_start);

unset($next_url_array);
unset($url_stack);
unset($url);

$crawled_url = array_slice($crawled_url, 0, $max_pages);

# Use built in function to extract meta tags from URL'
$tags = get_meta_tags($seed_url);

# Extract keywords from tags, truncate to 160 characters.
$url_meta_keywords = substr($tags['keywords'], 0, 160);

$meta_keywordsID = (int) update_meta_keywords($url_meta_keywords, $conn);

# Extract description from tags, truncate to 160 characters.
$url_meta_description = substr($tags['description'], 0, 160);

$descriptionID = (int) update_meta_description($url_meta_description, $conn);

$n = 0;
foreach ($crawled_url as $url => $val) {
    $n++;
    echo "[$n] Extracting source from $url \n";
    extract_source($url, $lynx_file_name);
    
    # Use regular expression to extract title, truncate to 160 chars.
    $title = substr(extract_title($lynx_file_name), 0, 160);
    
    $keywords_array = array_filter(explode(' ', $title));
    
    foreach ($keywords_array as $keyword) {
        if (isset($stopwords[$keyword])) {
            echo "keyword $keyword removed\n";
            continue;
        } else {
            $kwID = get_keyword_id($keyword, $conn);
            
            $urlID = get_url_id($url, $title, $meta_keywordsID, $descriptionID, $conn);
            
            update_inverted_list($kwID, $urlID, $conn);
        }
    }
}

$conn->close();
echo 'Spider lynx execution time in seconds: ' . (microtime(true) - $time_start);

?>