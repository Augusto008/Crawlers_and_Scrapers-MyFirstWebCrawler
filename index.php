<?php

/* 

Run the program => use the command "php index.php > pages.json" and see the results at "pages.json".

*/

$start = "https://google.com.br/";

$already_crawled = array();
$crawling = array();

function get_details($url) {

    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: Myself/0.1\n"));

    $context = stream_context_create($options);

    $doc = new DOMDocument();
    
    @$doc->loadHTML(file_get_contents($url, false, $context));

    $title = $doc->getElementsByTagName('title');
    @$title = $title->item(0)->nodeValue;

    $description = "";
    $keywords = "";
    $metas = $doc->getElementsByTagName("meta");
    for ($i = 0; $i < $metas->length; $i++) {

        $meta = $metas->item( $i );

        if ($meta->hasAttribute("name") == strtolower("description"))
            $description = $meta->getAttribute("content");

        if ($meta->hasAttribute("name") == strtolower("keywords"))
            $description = $meta->getAttribute("content");

    }

    return '{ "title": "'.@str_replace("\n", "", $title).'", "description": "'.@str_replace("\n", "", $description).'", "keywords": '.@str_replace("\n", "", $keywords).'""}';

}

function follow_links($url) {

    global $already_crawled;
    global $crawling;

    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: Myself/0.1\n"));

    $context = stream_context_create($options);

    $doc = new DOMDocument();
    
    @$doc->loadHTML(file_get_contents($url, false, $context));

    $linklist = $doc->getElementsByTagName("a");

    foreach ($linklist as $link) {

        $l = $link->getAttribute("href");

        if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {

            // Add "<protocol>://<host>" before link
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
        
        } else if (substr($l, 0, 2) == "//") {
        
            // Add "<protocol>:" before link
            $l = parse_url($url)["scheme"].":".$l;
        
        } else if (substr($l, 0, 2) == "./") {
        
            // Add "<protocol>://<host>" before the link and remove the dot.
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
        
        } else if (substr($l, 0, 1) == "#") {

            // Add "<protocol>://<host>"
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;

        } else if (substr($l, 0, 3) == "../") {

            // Add "<protocol>://<host>/" before link
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;

        } else if (substr($l, 0, 11) == "javascript:") {

            continue;

        } else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {

            // Add "<protocol>://<host>/" before link
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;

        }

        if (!in_array($l, $already_crawled)) {

            $already_crawled[] = $l;
            $crawling[] = $l;
            echo '{ "URL" : "'.$l.'" },'."\n";
            echo get_details($l).",\n\n";

        }

        array_shift($crawling);

        foreach ($crawling as $site) {
            follow_links(json_encode($site));
        }

    }

}

echo "[\n\n";
follow_links($start);
echo "]\n\n";
// print_r(json_encode($already_crawled));