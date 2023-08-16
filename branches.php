<?php

include 'funcs.php';

$defaultBranch = getenv('DEFAULT_BRANCH');
$minimumCmsMajor = getenv('MINIMUM_CMS_MAJOR');
$githubRepository = getenv('GITHUB_REPOSITORY');

$branches = branches($defaultBranch, $minimumCmsMajor, $githubRepository);
echo implode(' ', $branches);
