<?php

function branches(
    string $defaultBranch,
    string $minimumCmsMajor,
    string $githubRepository,
    // The following params are purely for unit testing, for the actual github action it will read json files instead
    string $composerJson = '',
    string $branchesJson = '',
    string $tagsJson = ''
) {
    if (!is_numeric($defaultBranch)) {
        throw new Exception('Default branch must be a number');
    }
    if (!ctype_digit($minimumCmsMajor)) {
        throw new Exception('Minimum CMS major must be an integer');
    }

    // work out default major
    preg_match('#^([0-9]+)+\.?[0-9]*$#', $defaultBranch, $matches);
    $defaultMajor = $matches[1];
    
    // read __composer.json of the current (default) branch
    $contents = $composerJson ?: file_get_contents('__composer.json');
    $json = json_decode($contents);
    if (is_null($json)) {
        $lastError = json_last_error();
        throw new Exception("Could not parse __composer.json - last error was $lastError");
    }
    $defaultCmsMajor = '';
    $matchedOnBranchThreeLess = false;
    $version = '';
    if ($githubRepository === 'silverstripe/developer-docs') {
        $version = $defaultBranch;
    }
    if (!$version) {
        $version = preg_replace('#[^0-9\.]#', '', $json->require->{'silverstripe/framework'} ?? '');
    }
    if (!$version) {
        $version = preg_replace('#[^0-9\.]#', '', $json->require->{'silverstripe/cms'} ?? '');
    }
    if (!$version) {
        $version = preg_replace('#[^0-9\.]#', '', $json->require->{'silverstripe/mfa'} ?? '');
    }
    if (!$version) {
        $version = preg_replace('#[^0-9\.]#', '', $json->require->{'silverstripe/assets'} ?? '');
        $matchedOnBranchThreeLess = true;
    }
    if (preg_match('#^([0-9]+)+\.?[0-9]*$#', $version, $matches)) {
        $defaultCmsMajor = $matches[1];
        if ($matchedOnBranchThreeLess) {
            $defaultCmsMajor += 3;
        }
    } else {
        $phpVersion = $json->require->{'php'} ?? '';
        if (substr($phpVersion,0, 4) === '^7.4') {
            $defaultCmsMajor = 4;
        } elseif (substr($phpVersion,0, 4) === '^8.1') {
            $defaultCmsMajor = 5;
        }
    }
    if ($defaultCmsMajor === '') {
        throw new Exception('Could not work out what the default CMS major version this module uses');
    }
    // work out major diff e.g for silverstripe/admin for CMS 5 => 5 - 2 = 3
    $majorDiff = $defaultCmsMajor - $defaultMajor;

    $minorsWithStableTags = [];
    $contents = $tagsJson ?: file_get_contents('__tags.json');
    foreach (json_decode($contents) as $row) {
        $tag = $row->name;
        if (!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)$#', $tag, $matches)) {
            continue;
        }
        $major = $matches[1];
        $minor = $major. '.' . $matches[2];
        $minorsWithStableTags[$major][$minor] = true;
    }

    $branches = [];
    $contents = $branchesJson ?: file_get_contents('__branches.json');
    foreach (json_decode($contents) as $row) {
        $branch = $row->name;
        // filter out non-standard branches
        if (!preg_match('#^([0-9]+)+\.?[0-9]*$#', $branch, $matches)) {
            continue;
        }
        // filter out majors that are too old
        $major = $matches[1];
        if (($major + $majorDiff) < $minimumCmsMajor) {
            continue;
        }
        // suffix a temporary .999 minor version to major branches so that it's sorted correctly later
        if (preg_match('#^[0-9]+$#', $branch)) {
            $branch .= '.999';
        }
        $branches[] = $branch;
    }
    
    // sort so that newest is first
    usort($branches, 'version_compare');
    $branches = array_reverse($branches);
    
    // remove the temporary .999
    array_walk($branches, function(&$branch) {
        $branch = preg_replace('#\.999$#', '', $branch);
    });
    
    // remove all branches except:
    // - the latest major branch in each release line
    // - the latest minor branch with a stable tag in each release line
    // - any minor branches without stable tags with a higher minor version than the latest minor with a stable tag
    $foundMinorInMajor = [];
    $foundMinorBranchWithStableTag = [];
    foreach ($branches as $i => $branch) {
        // only remove minor branches, leave major branches in
        if (!preg_match('#^([0-9]+)\.[0-9]+$#', $branch, $matches)) {
            continue;
        }
        $major = $matches[1];
        if (isset($foundMinorBranchWithStableTag[$major]) && isset($foundMinorInMajor[$major])) {
            unset($branches[$i]);
            continue;
        }
        // for developer-docs which has no tags, pretend that every branch has a tag
        if (isset($minorsWithStableTags[$major][$branch]) || $githubRepository === 'silverstripe/developer-docs') {
            $foundMinorBranchWithStableTag[$major] = true;
        }
        $foundMinorInMajor[$major] = true;
    }

    // reverse the array so that oldest is first
    $branches = array_reverse($branches);
    
    return $branches;
}
