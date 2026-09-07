<?php

declare(strict_types=1);

$workflowPath = dirname(__DIR__) . '/.github/workflows/deploy-beget.yml';
$workflow = (string) file_get_contents($workflowPath);

$requiredFilters = [
    "--filter='protect public/images/contents/baseimg/***'",
    "--filter='protect public/images/contents/mini/***'",
    "--filter='protect public/images/contents/tmp/***'",
];

foreach ($requiredFilters as $filter) {
    if (strpos($workflow, $filter) === false) {
        fwrite(STDERR, "FAILED: deploy workflow does not protect runtime content images: {$filter}\n");
        exit(1);
    }
}

if (strpos($workflow, '--delete') === false) {
    fwrite(STDERR, "FAILED: test is no longer exercising a deleting rsync deployment\n");
    exit(1);
}

echo "Deploy content images protection test passed\n";
