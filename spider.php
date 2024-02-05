<?php

include 'database.php';
include 'password.php';

$lynx_file_name = "result.txt";
$url_file_name  = "urls.txt";

$seed_url   = $argv[1];
$max_pages  = $argv[2];

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error)
    die('Could not connect: ' . $conn->connect_error);


function extract_urls($url, $seed_url, $url_file_name)
{
    $cmd = "lynx --listonly --nonumbers --display_charset=utf-8 --dump '$url' | grep ^$seed_url | uniq > $url_file_name 2>&1";
    system($cmd, $return_value);
    ($return_value == 0) or die("error: $cmd");
    return $return_value;
}


function extract_source($URL, $lynx_file_name)
{
    $cmd = "lynx -dump -source '$URL' | grep '<title>' > $lynx_file_name 2>&1";
    system($cmd, $return_value);
    #($return_value == 0) or die("error: $cmd");
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
    # Find the ID of the input keyword from the keywords table.
    $sql = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
    #echo ($sql . "\n\n");
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
        while ($row = $result->fetch_assoc())
            $kwID = $row['kwID'];
    else {
        $sql = "INSERT INTO keywords( keyword ) VALUES ( '$keyword' );";
        #echo ($sql . "\n\n");
        $conn->query($sql);
        $sql = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
        #echo ($sql . "\n\n");
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
    #echo ($sql . "\n\n");
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
        while ($row = $result->fetch_assoc())
            $urlID = $row['urlID'];
    else {
        $sql = "INSERT INTO url_title( url, title, keywords, description )";
        $sql .= " VALUES ( '$URL', '$title', '$url_meta_keywords',";
        $sql .= " '$url_meta_description' );";
        #echo ($sql . "\n\n");
        $conn->query($sql);
        $sql = "SELECT urlID FROM url_title WHERE url='$URL';";
        #echo ($sql . "\n\n");
        $result = $conn->query($sql);
        if ($result->num_rows > 0)
            while ($row = $result->fetch_assoc())
                $urlID = $row['urlID'];
    }
    return $urlID;
}


function update_inverted_list($kwID, $urlID, $conn)
{
    $sql = "INSERT INTO www_index VALUES ( '$kwID', '$urlID' );";
    #echo ($sql . "\n\n");
    $conn->query($sql);
}

$time_start = microtime(true); 

$stopwords_array = explode("\n", file_get_contents('stopwords.txt'));

extract_urls($seed_url, $seed_url, $url_file_name);

$url_stack = array_unique(explode("\n", file_get_contents($url_file_name)));

$do_not_crawl = array('.3gp',
 '.aac',
 '.ai',
 '.aiff',
 '.asf',
 '.asx',
 '.au',
 '.avi',
 '.bin',
 '.bmp',
 '.css',
 '.doc',
 '.drw',
 '.dxf',
 '.eps',
 '.exe',
 '.gif',
 '.jpeg',
 '.jpg',
 '.m4a',
 '.mid',
 '.mng',
 '.mov',
 '.mp3',
 '.mp4',
 '.mpg',
 '.ogg',
 '.pct',
 '.pdf',
 '.png',
 '.ps',
 '.psp',
 '.pst',
 '.qt',
 '.ra',
 '.rar',
 '.rm',
 '.rss',
 '.svg',
 '.swf',
 '.tif',
 '.tiff',
 '.wav',
 '.wma',
 '.wmv',
 '.xml',
 '.zip',
 'st)}',
 '.xls',
 '.xsl'
);

$crawled_url = array($seed_url);
while (count($url_stack) > 0) {
    $url = array_pop($url_stack);
    if (count($crawled_url) >= $max_pages) {
        echo "Reached max urls $max_pages\n";
        break;
    }
    # do not crawl some files
    if (in_array(substr($url, -4), $do_not_crawl) || $url == "") {
        continue;
    }
    if (in_array($url, $crawled_url)) {
        #echo "$url already crawled skipping\n";
        continue;
    }
    extract_urls($url, $seed_url, $url_file_name);
    $next_url_array = explode("\n", file_get_contents($url_file_name));
    $url_stack = array_unique(array_merge($next_url_array, $url_stack));
    array_push($crawled_url, $url);
}
unset($url);

$crawled_url = array_slice($crawled_url, 0, $max_pages);

# push the seed url back onto the array
#array_push($crawled_url, $seed_url);

foreach ($crawled_url as $key=>$url) {
    echo "[$key] $url\n";
}
unset($url);


# Use built in function to extract meta tags from URL'
$tags = get_meta_tags($seed_url);

# Extract keywords from tags, truncate to 160 characters.
$url_meta_keywords = substr($tags['keywords'], 0, 160);

# Extract description from tags, truncate to 160 characters.
$url_meta_description = substr($tags['description'], 0, 160);

foreach ($crawled_url as $key=>$url) {
    $n = $key + 1;
    echo "[$n] Extracting source from $url \n";
    extract_source($url, $lynx_file_name);

    # Use regular expression to extract title, truncate to 160 chars.
    $title = substr(extract_title($lynx_file_name), 0, 160);

    $keywords_array = array_filter(explode(' ', $title));

    foreach ($keywords_array as $keyword) {
        if (in_array($keyword, $stopwords_array)) {
            echo "keyword $keyword removed\n";
            continue;
        } else {
            $kwID = get_keyword_id($keyword, $conn);
            
            $urlID = get_url_id($url, $title, $url_meta_keywords, $url_meta_description, $conn);

            update_inverted_list($kwID, $urlID, $conn);
        }
    }
}

$conn->close();
echo 'Spider execution time in seconds: ' . (microtime(true) - $time_start);

?>