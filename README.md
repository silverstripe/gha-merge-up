# GitHub Actions - Merge-up

Merge-up supported branches in a repository

## Usage

**.github/workflows/merge-up.yml**
```yml
steps:
  - name: Merge-up
    uses: silverstripe/gha-merge-up@v1
```

This action has no inputs

## Preventing merge-ups from specific major versions of a repository

update `DO_NOT_MERGE_UP_FROM_MAJOR` in `funcs.php`. For example to prevent merging up from the `3` major version of `silverstripe/silverstripe-linkfield`:

```php
const DO_NOT_MERGE_UP_FROM_MAJOR = [
    'silverstripe/silverstripe-linkfield' => '3',
];
```
