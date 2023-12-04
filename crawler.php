<?php

$url = "https://www.w3schools.com/php/";
$depth = 2;


$output_dir = "fetched/";
$files = glob($output_dir . '*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}

$parsedUrl = parse_url($url);

if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    $robotsTxtUrl = rtrim($baseUrl, '/') . '/robots.txt';

    $ch = curl_init($robotsTxtUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $robotsTxtContent = curl_exec($ch);
    curl_close($ch);

    if ($robotsTxtContent === false) {
        $disallowedUrls = [];
    } else {
        $disallowedLinks = [];
        $lines = explode("\n", $robotsTxtContent);

        foreach ($lines as $line) {
            $line = trim($line);

            if (strpos($line, 'Disallow:') === 0) {
                $disallowedPath = trim(substr($line, strlen('Disallow:')));
                $disallowedLink = rtrim($baseUrl, '/') . $disallowedPath;
                $disallowedLinks[] = $disallowedLink;
            }
        }

        $disallowedUrls = $disallowedLinks;
    }
}

$orig_url = $url;
$urls_to_scrap = [$url];
$completed_urls = [];

while ($depth > 0 && !empty($urls_to_scrap)) {
    $current_url = array_pop($urls_to_scrap);
    if (in_array($current_url, $completed_urls)) {
        continue;
    }
    $ch = curl_init($current_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $htmlContent = curl_exec($ch);

    if ($htmlContent === false) {
        continue;
    }

    if (trim($htmlContent) == "" || stripos($htmlContent, 'PAGE NOT FOUND') !== false || curl_getinfo($ch, CURLINFO_HTTP_CODE) == 404) {
        continue;
    }

    curl_close($ch);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($htmlContent);
    libxml_use_internal_errors(false);

    $htmlContent = $dom->saveHTML();
    file_put_contents($output_dir . $depth . ".html", $htmlContent);

    $hrefs = array();

    $anchorTags = $dom->getElementsByTagName('a');

    foreach ($anchorTags as $anchor) {
        $href = $anchor->getAttribute('href');
        $hrefs[] = $href;
    }

    $more_urls = $hrefs;
    $more_urls_filtered = [];

    foreach ($more_urls as $url) {
        if (strpos($url, '#') === 0) {
            continue;
        }

        if (strpos($url, '/') === 0) {
            $url = rtrim($baseUrl, '/') . $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            continue;
        }

        if (in_array($url, $completed_urls)) {
            continue;
        }

        $allowed = true;
        foreach ($disallowedUrls as $disallowedLink) {
            $regexPattern = str_replace(['*', '/'], ['.*', '\/'], $disallowedLink);

            if (preg_match('/^' . $regexPattern . '$/', $url)) {
                $allowed = false;
                break;
            }
        }

        if (!$allowed) {
            continue;
        }

        $more_urls_filtered[] = $url;
    }

    $more_urls_filtered = array_unique($more_urls_filtered);
    $urls_to_scrap = array_merge($urls_to_scrap, $more_urls_filtered);
    $depth--;
    $completed_urls[] = $current_url;
}
?>
