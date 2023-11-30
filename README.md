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

## CMS major version detection

This action will identify the CMS major version which is determined by parsing the contents on the modules `composer.json` using logic in the `funcs.php` file. In cases where the major version remains indeterminable, the action will fail. This is most likely if the module is not intended as an addon for a website.

To work around this, specify an arbitrary CMS major version by setting the required PHP version in `composer.json` to `^8.1` or any other minimum PHP version that aligns with a CMS major version.
