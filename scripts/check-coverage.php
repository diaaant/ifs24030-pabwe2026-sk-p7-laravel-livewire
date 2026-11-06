<?php

/**
 * Quality gate: gagal (exit 1) jika coverage app/ kurang dari 100%
 * pada level Classes / Methods / Lines.
 *
 * Pemakaian: php scripts/check-coverage.php coverage.xml
 */
$file = $argv[1] ?? 'coverage.xml';

if (! is_file($file)) {
    fwrite(STDERR, "File clover tidak ditemukan: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);
$metrics = $xml->project->metrics;
$failures = [];

if ((int) $metrics['statements'] !== (int) $metrics['coveredstatements']) {
    $failures[] = sprintf('Lines: %d/%d tercover', (int) $metrics['coveredstatements'], (int) $metrics['statements']);
}

if ((int) $metrics['methods'] !== (int) $metrics['coveredmethods']) {
    $failures[] = sprintf('Methods: %d/%d tercover', (int) $metrics['coveredmethods'], (int) $metrics['methods']);
}

foreach ($xml->xpath('//class') as $class) {
    $m = $class->metrics;
    if ((int) $m['methods'] !== (int) $m['coveredmethods']) {
        $failures[] = sprintf('Class %s belum 100%% (%d/%d method)', $class['name'], (int) $m['coveredmethods'], (int) $m['methods']);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "COVERAGE GATE GAGAL (target 100%):\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "COVERAGE GATE LULUS: 100% Classes / Methods / Lines.\n";
