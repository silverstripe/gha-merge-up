<?php

use PHPUnit\Framework\TestCase;
use SilverStripe\SupportedModules\MetaData;

class BranchesTest extends TestCase
{
    /**
     * @dataProvider provideBranches
     */
    public function testBranches(
        array $expected,
        string $defaultBranch,
        string $githubRepository,
        string $composerJson = '',
        string $branchesJson = '[]',
        string $tagsJson = '[]'
    ) {
        $expectException = $expected === ['__exception__'];
        if ($expectException) {
            $this->expectException(Exception::class);
        }
        try {
            if ($composerJson) {
                file_put_contents('__composer.json', $composerJson);
            }
            file_put_contents('__branches.json', $branchesJson);
            file_put_contents('__tags.json', $tagsJson);
            $actual = branches(
                $defaultBranch,
                $githubRepository,
                $branchesJson,
                $tagsJson
            );
            if (!$expectException) {
                $this->assertSame($expected, $actual);
            }
        } finally {
            if ($composerJson) {
                unlink('__composer.json');
            }
            unlink('__branches.json');
            unlink('__tags.json');
        }
    }

    public static function provideBranches()
    {
        $lowestMajor = MetaData::LOWEST_SUPPORTED_CMS_MAJOR;
        $highestMajor = MetaData::HIGHEST_STABLE_CMS_MAJOR;
        $EOLMajor = (string)($lowestMajor - 1);
        $nextMajor = (string)($highestMajor + 1);
        // Note: Most scenarios are tested upstream in the supported-modules repo.
        // We just need to check here that we're passing stuff through in an expected way
        // and any logic/exception unique to this repo
        return [
            'highest major beta, next major branch detected on silverstripe/framework' => [
                'expected' => [$lowestMajor . '.13', $lowestMajor, $highestMajor . '.0', $highestMajor . '.1', $highestMajor, $nextMajor],
                'defaultBranch' => $highestMajor,
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^$highestMajor.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "$EOLMajor"},
                    {"name": "$EOLMajor.6"},
                    {"name": "$EOLMajor.7"},
                    {"name": "$lowestMajor"},
                    {"name": "$lowestMajor.10"},
                    {"name": "$lowestMajor.11"},
                    {"name": "$lowestMajor.12"},
                    {"name": "$lowestMajor.13"},
                    {"name": "$highestMajor"},
                    {"name": "$highestMajor.0"},
                    {"name": "$highestMajor.1"},
                    {"name": "$nextMajor"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "$highestMajor.1.0-beta1"},
                    {"name": "$highestMajor.0.9"},
                    {"name": "$lowestMajor.13.11"},
                    {"name": "$lowestMajor.12.11"},
                    {"name": "$lowestMajor.11.11"},
                    {"name": "$lowestMajor.10.11"},
                    {"name": "$EOLMajor.7.4"}
                ]
                EOT,
            ],
            'next major alpha with all final CMS supported branches on silverstripe/framework' => [
                'expected' => [$lowestMajor . '.13', $lowestMajor, $highestMajor . '.3', $highestMajor . '.4', $highestMajor, $nextMajor . '.0', $nextMajor],
                'defaultBranch' => $highestMajor,
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^$highestMajor.4"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "$EOLMajor"},
                    {"name": "$EOLMajor.6"},
                    {"name": "$EOLMajor.7"},
                    {"name": "$lowestMajor"},
                    {"name": "$lowestMajor.10"},
                    {"name": "$lowestMajor.11"},
                    {"name": "$lowestMajor.12"},
                    {"name": "$lowestMajor.13"},
                    {"name": "$highestMajor"},
                    {"name": "$highestMajor.0"},
                    {"name": "$highestMajor.1"},
                    {"name": "$highestMajor.2"},
                    {"name": "$highestMajor.3"},
                    {"name": "$highestMajor.4"},
                    {"name": "$nextMajor"},
                    {"name": "$nextMajor.0"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "$nextMajor.0.0-alpha1"},
                    {"name": "$highestMajor.4.0-beta1"},
                    {"name": "$highestMajor.3.0"},
                    {"name": "$highestMajor.2.0"},
                    {"name": "$highestMajor.1.0"},
                    {"name": "$highestMajor.0.9"},
                    {"name": "$lowestMajor.13.11"},
                    {"name": "$lowestMajor.12.11"},
                    {"name": "$lowestMajor.11.11"},
                    {"name": "$lowestMajor.10.11"},
                    {"name": "$EOLMajor.7.4"}
                ]
                EOT,
            ],
            'More than 7 branches exception' => [
                'expected' => ['__exception__'],
                'defaultBranch' => $highestMajor,
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^$highestMajor.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "$EOLMajor"},
                    {"name": "$EOLMajor.6"},
                    {"name": "$EOLMajor.7"},
                    {"name": "$lowestMajor"},
                    {"name": "$lowestMajor.10"},
                    {"name": "$lowestMajor.11"},
                    {"name": "$lowestMajor.12"},
                    {"name": "$lowestMajor.13"},
                    {"name": "$highestMajor"},
                    {"name": "$highestMajor.0"},
                    {"name": "$highestMajor.1"},
                    {"name": "$highestMajor.2"},
                    {"name": "$nextMajor"},
                    {"name": "$nextMajor.0"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "$nextMajor.0.0-alpha1"},
                    {"name": "$highestMajor.2.0-beta1"},
                    {"name": "$highestMajor.1.0-beta1"},
                    {"name": "$highestMajor.0.9"},
                    {"name": "$lowestMajor.13.11"},
                    {"name": "$lowestMajor.12.11"},
                    {"name": "$lowestMajor.11.11"},
                    {"name": "$lowestMajor.10.11"},
                    {"name": "$EOLMajor.7.4"}
                ]
                EOT,
            ],
        ];
    }
}
