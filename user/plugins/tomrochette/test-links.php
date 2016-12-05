<?php

// TODO(tom@tomrochette.com): Check if reachable from given entry points

$pagesDirectory = realpath(__DIR__ . '/../../pages');
$agiDirectory = realpath($pagesDirectory.'/agi');

$startPages = [
    $agiDirectory => true,
];

$pattern = '/\.md$/';
$directoryIterator = new RecursiveDirectoryIterator($agiDirectory);
$iterator = new RecursiveIteratorIterator($directoryIterator);
$files = new RegexIterator($iterator, $pattern, RegexIterator::MATCH);

$redBackground = "\033[41m";
$greenBackground = "\033[42m";
$endColor = "\033[0m";

$countBroken = 0;
$references = [];
$articles = [];

// Currently ignores assets, might be nice to test them at some point
$articleRegex = '/\[[^\]]+\]\((?!.*\/\/)(?<path>[^)]+)\)/';
foreach ($files as $file) {
    echo 'Scanning ' . $file . PHP_EOL;
    $fileDirectory = dirname($file);
    $articles[$fileDirectory] = true;
    $content = file_get_contents($file);
    preg_match_all($articleRegex, $content, $matches);
    foreach ($matches['path'] as $path) {
        $directory = realpath($fileDirectory.'/'.$path);
        $file = file_exists($directory); // If an asset, check the file exists
        $files = glob($directory.'/{item,article}.md', GLOB_BRACE);
        $fileExists = $file || count($files) > 0;
        $countBroken = $fileExists ? $countBroken : ++$countBroken;
        $color = $fileExists ? $greenBackground : $redBackground;
        echo "\t" . $color . $path . ' ' . ($fileExists ? 'OK' : 'BROKEN') . $endColor . PHP_EOL;
        $references[$directory][$fileDirectory] = true;
    }
}

echo 'Scan done.'.PHP_EOL.PHP_EOL;

echo 'References'.PHP_EOL;
foreach ($references as $reference => $referrers) {
    echo $reference . PHP_EOL;
    foreach ($referrers as $reference => $dontCare) {
        echo "\t" . $reference . PHP_EOL;
    }
}

echo PHP_EOL.PHP_EOL;
echo 'Articles not referred'.PHP_EOL;

$notReferred = array_diff_key($articles, $references, $startPages);
$countNotReferred = count($notReferred);
foreach ($notReferred as $article => $dontCare) {
    echo $article.PHP_EOL;
}

echo PHP_EOL;
$color = $countBroken === 0 ? $greenBackground : $redBackground;
echo $color . 'Broken: ' . $countBroken . $endColor . PHP_EOL;
$color = $countNotReferred === 0 ? $greenBackground : $redBackground;
echo $color . 'Not referred: ' . $countNotReferred . $endColor . PHP_EOL;
