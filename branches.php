<?php

include 'funcs.php';

$defaultBranch = getenv('DEFAULT_BRANCH');
$minimumCmsMajor = getenv('MINIMUM_CMS_MAJOR');

$branches = branches($defaultBranch, $minimumCmsMajor);
echo implode(' ', $branches);
