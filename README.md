# GitHub Actions - Merge-up

Merge-up supported branches in a repository

## Usage

**.github/workflows/merge-up.yml**
```yml

permissions: {}

jobs:
  mergeup:
    # ...
    permissions:
      contents: write
      actions: write
    steps:
      - name: Merge-up
        uses: silverstripe/gha-merge-up@v1
```

This action has no inputs

## CMS major version detection

In cases where the major version is indeterminable, this action will fail. This is most likely if the module is not intended as an addon for a website.

To work around this, specify an arbitrary CMS major version by setting the required PHP version in `composer.json` to `^8.1` or any other minimum PHP version that aligns with a CMS major version.

## Which CMS major release lines are merged up

Only CMS major release lines in full support are merged up:

- The highest CMS major that `silverstripe/framework` maps to in [`repositories.json`](https://github.com/silverstripe/supported-modules/blob/main/repositories.json), and any newer CMS major (e.g. a pre-release), is always merged up.
- The previous CMS major is merged up until `silverstripe/silverstripe-framework` has a `<highest major>.2.0` tag. For example, CMS 5 stops being merged up once framework `6.2.0` is tagged.
- Older CMS majors are never merged up.

Once a CMS major stops, every merge-up involving its branches stops. That covers merge-ups within the major (e.g. `5.4` into `5`) and from it into the next major (e.g. `5` into `6.3`). Those branches only receive security fixes, which are merged up manually, or released separately.

Branches that cannot be mapped to a CMS major, e.g. in a repository not listed in `repositories.json`, are always merged up.

## Preventing merge-ups from specific major versions of a repository

Update `MetaData::DO_NOT_MERGE_UP_FROM_MAJOR` in the [silverstripe/supported-modules](https://github.com/silverstripe/supported-modules) repo. For example to prevent merging up from the `3` major version of `silverstripe/silverstripe-linkfield`:

```php
public const DO_NOT_MERGE_UP_FROM_MAJOR = [
    'silverstripe/silverstripe-linkfield' => '3',
];
```

## Running the bats tests

The merge-up support logic in `funcs.sh` is tested with [bats](https://github.com/bats-core/bats-core) 1.7 or newer, which needs `jq`.

```bash
bats tests/funcs.bats
```
