<?php

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new RuntimeException('Run composer install before this script');
}

require_once $autoloadPath;

include 'funcs.php';

$defaultBranch = getenv('DEFAULT_BRANCH');
$githubRepository = getenv('GITHUB_REPOSITORY');

$branches = branches($defaultBranch, $githubRepository);
echo implode(' ', $branches);
