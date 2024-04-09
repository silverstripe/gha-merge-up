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
        string $minimumCmsMajor,
        string $githubRepository,
        string $composerJson = '',
        string $branchesJson = '',
        string $tagsJson = ''
    ) {
        $expectException = $expected === ['__exception__'];
        if ($expectException) {
            $this->expectException(Exception::class);
        }
        $actual = branches(
            $defaultBranch,
            $minimumCmsMajor,
            $githubRepository,
            $composerJson,
            $branchesJson,
            $tagsJson
        );
        if (!$expectException) {
            $this->assertSame($expected, $actual);
        }
    }

    public function provideBranches()
    {
        return [
            '5.1.0-beta1, CMS 6 branch detected on silverstripe/framework' => [
                'expected' => ['4.13', '4', '5.0', '5.1', '5', '6'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
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
            '5.1.0 stable and match on silverstripe/cms' => [
                'expected' => ['4.13', '4', '5.1', '5'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/cms": "^5.1"
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
                    {"name": "5.1"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "5.1.0"},
                    {"name": "5.0.9"},
                    {"name": "4.13.11"},
                    {"name": "4.12.11"},
                    {"name": "4.11.11"},
                    {"name": "4.10.11"},
                    {"name": "3.7.4"}
                ]
                EOT,
            ],
            'match on silverstripe/assets' => [
                'expected' => ['4.13', '4', '5.1', '5'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/assets": "^2.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "4"},
                    {"name": "4.10"},
                    {"name": "4.11"},
                    {"name": "4.12"},
                    {"name": "4.13"},
                    {"name": "5"},
                    {"name": "5.0"},
                    {"name": "5.1"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "5.1.0"},
                    {"name": "5.0.9"},
                    {"name": "4.13.11"},
                    {"name": "4.12.11"},
                    {"name": "4.11.11"},
                    {"name": "4.10.11"}
                ]
                EOT,
            ],
            'match on silverstripe/mfa' => [
                'expected' => ['4.13', '4', '5.1', '5'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/mfa": "^5.0"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "4"},
                    {"name": "4.12"},
                    {"name": "4.13"},
                    {"name": "5"},
                    {"name": "5.0"},
                    {"name": "5.1"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "5.1.0"},
                    {"name": "5.0.9"},
                    {"name": "4.13.11"}
                ]
                EOT,
            ],
            'Missing `1` branch and match on php version in composer.json' => [
                'expected' => ['1.13', '2.0', '2.1', '2'],
                'defaultBranch' => '2',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "php": "^8.1"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "1.10"},
                    {"name": "1.11"},
                    {"name": "1.12"},
                    {"name": "1.13"},
                    {"name": "2"},
                    {"name": "2.0"},
                    {"name": "2.1"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "2.1.0-beta1"},
                    {"name": "2.0.9"},
                    {"name": "1.13.11"},
                    {"name": "1.12.11"},
                    {"name": "1.11.11"},
                    {"name": "1.10.11"}
                ]
                EOT,
            ],
            'Two minor branches without stable tags in composer.json' => [
                'expected' => ['1.13', '1', '2.1', '2.2', '2.3', '2'],
                'defaultBranch' => '2',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "php": "^8.1"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "2"},
                    {"name": "2.0"},
                    {"name": "2.1"},
                    {"name": "2.2"},
                    {"name": "2.3"},
                    {"name": "1"},
                    {"name": "1.13"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "2.3.0-alpha1"},
                    {"name": "2.2.0-beta1"},
                    {"name": "2.1.0"},
                    {"name": "2.0.9"},
                    {"name": "1.13.11"}
                ]
                EOT,
                ],
            'Module where default branch has not been changed from CMS 4 and there is a new CMS 6 branch' => [
                'expected' => ['5.9', '5', '6.0', '6', '7'],
                'defaultBranch' => '5', // this repo has a `5` branch for CMS 4 and a '6' branch for CMS 5
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "php": "^7.4",
                        "silverstripe/framework": "^4.11"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "7"},
                    {"name": "6"},
                    {"name": "6.0"},
                    {"name": "5"},
                    {"name": "5.9"},
                    {"name": "5.8"},
                    {"name": "5.7"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "6.0.0"},
                    {"name": "5.9.1"},
                    {"name": "4.0.1"}
                ]
                EOT,
            ],
            'developer-docs' => [
                'expected' => ['4.13', '4', '5.0', '5'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'silverstripe/developer-docs',
                'composerJson' => <<<EOT
                {
                    "no-require": {}
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "5"},
                    {"name": "5.0"},
                    {"name": "4.13"},
                    {"name": "4.12"},
                    {"name": "4"},
                    {"name": "3"}
                ]
                EOT,
                'tagsJson' => '[]',
            ],
            'More than 6 branches exception' => [
                'expected' => ['__exception__'],
                'defaultBranch' => '5',
                'minimumCmsMajor' => '4',
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
            'cwp-watea-theme' => [
                'expected' => ['3.2', '3', '4.0', '4'],
                'defaultBranch' => '4',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'lorem/ipsum',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "cwp/starter-theme": "^4"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "1"},
                    {"name": "1.0"},
                    {"name": "2"},
                    {"name": "2.0"},
                    {"name": "3"},
                    {"name": "3.0"},
                    {"name": "3.1"},
                    {"name": "3.2"},
                    {"name": "4"},
                    {"name": "4.0"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "4.0.0"},
                    {"name": "5.0.9"},
                    {"name": "3.2.0"},
                    {"name": "3.1.0"},
                    {"name": "3.0.0"}
                ]
                EOT,
            ],
            'gha-ci' => [
                'expected' => ['1.4', '1'],
                'defaultBranch' => '1',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'silverstripe/gha-ci',
                'composerJson' => '',
                'branchesJson' => <<<EOT
                [
                    {"name": "1"},
                    {"name": "1.0"},
                    {"name": "1.1"},
                    {"name": "1.2"},
                    {"name": "1.3"},
                    {"name": "1.4"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "1.4.0"},
                    {"name": "1.3.0"},
                    {"name": "1.2.0"},
                    {"name": "1.1.0"},
                    {"name": "1.0.0"}
                ]
                EOT,
            ],
            'silverstripe-linkfield beta' => [
                'expected' => ['4.0', '4', '5'],
                'defaultBranch' => '4',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'silverstripe/silverstripe-linkfield',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^5"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "1"},
                    {"name": "2"},
                    {"name": "3"},
                    {"name": "4"},
                    {"name": "4.0"},
                    {"name": "5"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "3.0.0-beta1"},
                    {"name": "2.0.0"},
                    {"name": "1.0.0"}
                ]
                EOT,
            ],
            'silverstripe-linkfield stable' => [
                'expected' => ['4.0', '4', '5'],
                'defaultBranch' => '4',
                'minimumCmsMajor' => '4',
                'githubRepository' => 'silverstripe/silverstripe-linkfield',
                'composerJson' => <<<EOT
                {
                    "require": {
                        "silverstripe/framework": "^5"
                    }
                }
                EOT,
                'branchesJson' => <<<EOT
                [
                    {"name": "1"},
                    {"name": "2"},
                    {"name": "3"},
                    {"name": "3.0"},
                    {"name": "3.1"},
                    {"name": "3.999"},
                    {"name": "4"},
                    {"name": "4.0"},
                    {"name": "5"}
                ]
                EOT,
                'tagsJson' => <<<EOT
                [
                    {"name": "4.0.0"},
                    {"name": "3.0.0"},
                    {"name": "2.0.0"},
                    {"name": "1.0.0"}
                ]
                EOT,
            ],
        ];
    }
}
