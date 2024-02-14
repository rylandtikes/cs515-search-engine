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


function extract_urls($url, $seed_url) 
{
    if (substr($url, -1) !== '/') {
        $url = $url . '/';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    #echo $content;
    curl_close($ch);
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $links = $dom->getElementsByTagName('a');
    $urls = [];
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        if (substr($href, 0, 2) === '//') {
            echo 'skipping' . $href . '\n';
            continue;
        }
        $s = get_full_url($seed_url, $href);
        #echo "$s ";
        $pos = strpos($s, $seed_url);
        #echo "$pos \n";
        if ($pos !== false) {
          $urls[] = $s;
        }
    }
    
    return array_unique($urls);
}


function get_full_url($base_url, $href) 
{
 
    if (parse_url($href, PHP_URL_SCHEME) != '') {
        return  $href;
    }
    if($href[0] == '/') {
        return $base_url . $href;
    }
    if (substr($base_url, -1) === '/') {
        $base_url =  substr($base_url, 0, -1);
    }
    return $base_url . '/' . $href;
}


function extract_title($url) 
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    curl_close($ch);
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $titles = $dom->getElementsByTagName('title');
    if ($titles->length > 0) {
        $title = strtolower(trim($titles->item(0)->textContent));
        return $title;
    }
    return "";
}


function get_keyword_id($keyword, $conn)
{
    $sql = "SELECT kwID FROM keywords WHERE keyword='$keyword';";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $kwID = $row['kwID'];
    }    
    else {
        $sql = "INSERT INTO keywords( keyword ) VALUES ( '$keyword' );";
        $conn->query($sql);
        $kwID = $conn->insert_id;
    }
    return $kwID;
}


function get_url_id($URL, $title, $url_meta_keywords, $url_meta_description, $conn)
{
    $sql = "SELECT urlID FROM url_title WHERE url='$URL';";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $urlID = $row['urlID'];
    }
    else {
        $sql = "INSERT INTO url_title( url, title, keywords, description )";
        $sql .= " VALUES ( '$URL', '$title', '$url_meta_keywords',";
        $sql .= " '$url_meta_description' );";
        $conn->query($sql);
        $urlID = $conn->insert_id;
    }
    return $urlID;
}


function update_inverted_list($kwID, $urlID, $conn)
{
    $sql = "INSERT INTO www_index VALUES ( '$kwID', '$urlID' );";
    $conn->query($sql);
}

$time_start = microtime(true); 

$stopwords_array = explode("\n", file_get_contents('stopwords.txt'));

$url_stack = extract_urls($seed_url, $seed_url);

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

$crawl_time_start = microtime(true);

$crawled_url = [];
$crawled_url[$seed_url] = true;

while (!empty($url_stack)) {
    $url = array_pop($url_stack);
    #echo "processing $url\n";
    if (count($crawled_url) >= $max_pages) {
        echo "Reached max urls $max_pages\n";
        break;
    }
    # do not crawl some files
    if (empty($url) || in_array(substr($url, -4), $do_not_crawl)) {
        continue;
    }
    if (isset($crawled_url[$url])) {
        #echo "$url already crawled skipping\n";
        continue;
    }
    #extract_urls($url, $seed_url, $url_file_name);
    $next_url_array = extract_urls($url, $seed_url);
    #$next_url_array = explode("\n", file_get_contents($url_file_name));
    $url_stack = array_unique(array_merge($next_url_array, $url_stack));
    $crawled_url[$url] = true;
    #array_push($crawled_url, $url);
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

# Extract description from tags, truncate to 160 characters.
$url_meta_description = substr($tags['description'], 0, 160);

$n = 0;
foreach ($crawled_url as $url=>$val) {
    $n++;
    echo "[$n] Extracting source from $url \n";
    #extract_source($url, $lynx_file_name);

    $title = substr(extract_title($url), 0, 160);

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
echo 'Spider curl execution time in seconds: ' . (microtime(true) - $time_start);


?>