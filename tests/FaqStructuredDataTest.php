<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/helpers/SchemaHelper.php';

use app\helpers\SchemaHelper;

$longAnswer = str_repeat('Полный ответ на вопрос. ', 120);
$markup = SchemaHelper::renderFaqPageJsonLd([
    ['question' => '<strong>Как выбрать шины?</strong>', 'answer' => $longAnswer],
    ['question' => '', 'answer' => 'Строка без вопроса не должна попасть в схему'],
]);

if (!preg_match('~^<script type="application/ld\+json">(.*)</script>$~s', $markup, $match)) {
    fwrite(STDERR, "FAILED: FAQPage script was not rendered\n");
    exit(1);
}

$data = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
if (($data['@type'] ?? '') !== 'FAQPage' || count($data['mainEntity'] ?? []) !== 1) {
    fwrite(STDERR, "FAILED: invalid FAQPage structure\n");
    exit(1);
}

if (($data['mainEntity'][0]['name'] ?? '') !== 'Как выбрать шины?') {
    fwrite(STDERR, "FAILED: FAQ question was not normalized\n");
    exit(1);
}

if (($data['mainEntity'][0]['acceptedAnswer']['text'] ?? '') !== trim($longAnswer)) {
    fwrite(STDERR, "FAILED: FAQ answer must not be truncated\n");
    exit(1);
}

$categoryView = (string)file_get_contents($root . '/app/views/itscenter/Category/view.php');
if (strpos($categoryView, "'@type' => 'FAQPage'") !== false) {
    fwrite(STDERR, "FAILED: Category view must use the shared FAQPage renderer\n");
    exit(1);
}

$completeIndex = (string)file_get_contents($root . '/app/views/itscenter/Complete/index.php');
if (preg_match('~\b(?:itemscope|itemtype|itemprop|typeof|property)\s*=~i', $completeIndex)) {
    fwrite(STDERR, "FAILED: /complete index must not mix Microdata or RDFa with JSON-LD\n");
    exit(1);
}

echo "FAQ structured data tests passed\n";
