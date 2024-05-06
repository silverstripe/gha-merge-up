<?php

use PHPUnit\Framework\TestCase;

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

    public function provideBranches()
    {
        // Note: Most scenarios are tested upstream in the supported-modules repo.
        // We just need to check here that we're passing stuff through in an expected way
        // and any logic/exception unique to this repo
        return [
            '5.1.0-beta1, CMS 6 branch detected on silverstripe/framework' => [
                'expected' => ['4.13', '4', '5.0', '5.1', '5', '6'],
                'defaultBranch' => '5',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^5.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "3"},
                    {"name": "3.6"},
                    {"name": "3.7"},
                    {"name": "4"},
                    {"name": "4.10"},
                    {"name": "4.11"},
                    {"name": "4.12"},
                    {"name": "4.13"},
                    {"name": "5"},
                    {"name": "5.0"},
                    {"name": "5.1"},
                    {"name": "6"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "5.1.0-beta1"},
                    {"name": "5.0.9"},
                    {"name": "4.13.11"},
                    {"name": "4.12.11"},
                    {"name": "4.11.11"},
                    {"name": "4.10.11"},
                    {"name": "3.7.4"}
                ]
                EOT,
            ],
            'More than 6 branches exception' => [
                'expected' => ['__exception__'],
                'defaultBranch' => '5',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^5.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "3"},
                    {"name": "3.6"},
                    {"name": "3.7"},
                    {"name": "4"},
                    {"name": "4.10"},
                    {"name": "4.11"},
                    {"name": "4.12"},
                    {"name": "4.13"},
                    {"name": "5"},
                    {"name": "5.0"},
                    {"name": "5.1"},
                    {"name": "5.2"},
                    {"name": "6"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "5.2.0-beta1"},
                    {"name": "5.1.0-beta1"},
                    {"name": "5.0.9"},
                    {"name": "4.13.11"},
                    {"name": "4.12.11"},
                    {"name": "4.11.11"},
                    {"name": "4.10.11"},
                    {"name": "3.7.4"}
                ]
                EOT,
            ],
        ];
    }
}
