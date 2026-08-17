<?php

use SilverStripe\SupportedModules\BranchLogic;
use SilverStripe\SupportedModules\MetaData;

/**
 * The path to the composer.json file - note that the action explicitly downloads the composer.json
 * file for the default branch of the repository and saves it to this path, rather than using
 * the composer.json file in the "current" branch.
 */
const COMPOSER_JSON_PATH = '__composer.json';

function branches(
    string $defaultBranch,
    string $githubRepository,
): array {
    if (file_exists(COMPOSER_JSON_PATH)) {
        $contents = file_get_contents(COMPOSER_JSON_PATH);
        $composerJson = json_decode($contents);
        if (is_null($composerJson)) {
            $lastError = json_last_error();
            throw new Exception('Could not parse ' . COMPOSER_JSON_PATH . " - last error was $lastError");
        }
    } else {
        $composerJson = null;
    }

    $repoMetaData = MetaData::getMetaDataForRepository($githubRepository);
    $allRepoTags = array_map(fn($x) => $x->name, json_decode(file_get_contents('__tags.json')));
    if (file_exists('__tags2.json')) {
        $allRepoTags = array_merge($allRepoTags, array_map(fn($x) => $x->name, json_decode(file_get_contents('__tags2.json'))));
    }
    $allRepoBranches = array_map(fn($x) => $x->name, json_decode(file_get_contents('__branches.json')));

    $branches = BranchLogic::getBranchesForMergeUp($githubRepository, $repoMetaData, $defaultBranch, $allRepoTags, $allRepoBranches, $composerJson);
    // max of 7 branches - also update action.yml if you need to increase this limit
    if (count($branches) > 7) {
        $branchesString = implode(', ', $branches);
        throw new Exception("More than 7 branches to merge up: $branchesString. Aborting.");
    }
    return $branches;
}

