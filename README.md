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

## Preventing merge-ups from specific major versions of a repository

Update `MetaData::DO_NOT_MERGE_UP_FROM_MAJOR` in the [silverstripe/supported-modules](https://github.com/silverstripe/supported-modules) repo. For example to prevent merging up from the `3` major version of `silverstripe/silverstripe-linkfield`:

```php
public const DO_NOT_MERGE_UP_FROM_MAJOR = [
    'silverstripe/silverstripe-linkfield' => '3',
];
```
