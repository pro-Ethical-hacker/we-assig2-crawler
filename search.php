<?php


$query = "php";
$n = 100;


$output_dir = "fetched/";
$domObjects = [];

$htmlFiles = glob($output_dir . '/*.html');
foreach ($htmlFiles as $htmlFile) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTMLFile($htmlFile);
    libxml_clear_errors();
    $domObjects[] = $dom;
}

$domData = [];

foreach ($domObjects as $dom) {
    $titleElement = $dom->getElementsByTagName('title')->item(0);
    $headings = $dom->getElementsByTagName('h1');
    $paragraphs = $dom->getElementsByTagName('p');
    $spans = $dom->getElementsByTagName('span');
    $anchors = $dom->getElementsByTagName('a');
    $listItems = $dom->getElementsByTagName('li');
    $tableCells = $dom->getElementsByTagName('td');
    $labels = $dom->getElementsByTagName('label');
    $buttons = $dom->getElementsByTagName('button');

    $title = $titleElement ? $titleElement->textContent : 'No title found';
    $headingTexts = [];
    foreach ($headings as $heading) {
        $headingTexts[] = $heading->textContent;
    }
    $paragraphTexts = [];
    foreach ($paragraphs as $paragraph) {
        $paragraphTexts[] = $paragraph->textContent;
    }
    $spanTexts = [];
    foreach ($spans as $span) {
        $spanTexts[] = $span->textContent;
    }
    $anchorTexts = [];
    foreach ($anchors as $anchor) {
        $anchorTexts[] = $anchor->textContent;
    }
    $listItemTexts = [];
    foreach ($listItems as $listItem) {
        $listItemTexts[] = $listItem->textContent;
    }
    $cellTexts = [];
    foreach ($tableCells as $cell) {
        $cellTexts[] = $cell->textContent;
    }
    $labelTexts = [];
    foreach ($labels as $label) {
        $labelTexts[] = $label->textContent;
    }
    $buttonTexts = [];
    foreach ($buttons as $button) {
        $buttonTexts[] = $button->textContent;
    }

    $domData = array_merge($domData, $headingTexts, $paragraphTexts, $spanTexts, $anchorTexts, $listItemTexts, $cellTexts, $labelTexts, $buttonTexts);
}

$domData = array_filter(array_map('trim', $domData), function($value) {
    return $value !== '';
});

$queryVector = array_count_values(str_split($query));

$documentVectors = [];

foreach ($domData as $document) {
    $documentVector = array_count_values(str_split($document));
    $documentVectors[$document] = $documentVector;
}

$scores = [];

foreach ($documentVectors as $document => $documentVector) {
    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    foreach ($queryVector as $key => $value) {
        if (isset($documentVector[$key])) {
            $dotProduct += $value * $documentVector[$key];
        }

        $magnitudeA += pow($value, 2);
    }

    foreach ($documentVector as $value) {
        $magnitudeB += pow($value, 2);
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    $score = ($magnitudeA == 0 || $magnitudeB == 0) ? 0 : $dotProduct / ($magnitudeA * $magnitudeB);
    $scores[$document] = $score;
}

arsort($scores);
$topN = array_slice($scores, 0, $n, true);

$answer = implode('. ', array_keys($topN));
$lowercaseAnswer = strtolower($answer);
$lowercaseQuery = strtolower($query);

$highlightedAnswer = preg_replace_callback(
    "/$lowercaseQuery/",
    function ($match) use ($query) {
        return '<u><i>' . substr($match[0], 0, strlen($query)) . '</i></u>' . substr($match[0], strlen($query));
    },
    $answer
);

print_R($highlightedAnswer);

?>
