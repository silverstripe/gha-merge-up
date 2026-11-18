# Merge-up support logic used by action.yml - sourced rather than executed
# Requires jq

# Prints the highest CMS major that silverstripe/framework is mapped to in repositories.json
# $1 - path to repositories.json
framework_highest_major() {
    jq -r '.supportedModules[] | select(.packagist == "silverstripe/framework") | .majorVersionMapping | keys[]' "$1" | tail -n 1
}

# Prints 1 if framework has a <major>.2.0 tag, otherwise 0
# $1 - framework major e.g. 6
# $2 - path to a JSON array of tags from the GitHub API
framework_two_tag_exists() {
    local TWO_TAG
    TWO_TAG=$(jq -r --arg framework_highest_major "$1" '.[] | select(.name == $framework_highest_major + ".2.0")' "$2") || return
    if [[ $TWO_TAG != "" ]]; then
        echo 1
    else
        echo 0
    fi
}

# Prints a module map of { module_major: cms_major } as compact JSON
# e.g. for silverstripe/admin it will be { "1": "4", "2": "5", "3": "6" }
# Note that unlike gha-dispatch-ci this includes .misc, because merge-up also runs on
# repositories in that category
# $1 - path to repositories.json
# $2 - github repository e.g. silverstripe/silverstripe-admin
module_map() {
    jq -r -c --arg github_repository "$2" '(.supportedModules + .misc)[] | select(.github == $github_repository) | .majorVersionMapping | to_entries | map({key: .value[0], value: .key}) | from_entries' "$1"
}

# Removes branches for CMS major release lines which are no longer in full support
# Those branches only receive security fixes, which are merged up manually, or released separately
# Prints the remaining branches space separated to stdout, and a line for each removed branch to stderr
# $1 - space separated branches, oldest first e.g. "2.4 2 3.3 3"
# $2 - module map from module_map()
# $3 - framework highest major from framework_highest_major()
# $4 - 1 or 0 from framework_two_tag_exists()
filter_supported_branches() {
    local BRANCHES=$1 MODULE_MAP=$2 FRAMEWORK_HIGHEST_MAJOR=$3 FRAMEWORK_TWO_TAG_EXISTS=$4
    local SUPPORTED_BRANCHES="" BRANCH BRANCH_MAJOR CMS_MAJOR
    for BRANCH in $BRANCHES; do
        BRANCH_MAJOR=$(echo "$BRANCH" | cut -d'.' -f1)
        CMS_MAJOR=$(echo "$MODULE_MAP" | jq -r --arg branch_major "$BRANCH_MAJOR" '.[$branch_major] // ""') || return
        if [[ $CMS_MAJOR == "" ]]; then
            # Keep branches we cannot map to a CMS major e.g. repositories without meta-data
            SUPPORTED_BRANCHES="$SUPPORTED_BRANCHES $BRANCH"
        elif (( $CMS_MAJOR >= $FRAMEWORK_HIGHEST_MAJOR )); then
            SUPPORTED_BRANCHES="$SUPPORTED_BRANCHES $BRANCH"
        elif (( $CMS_MAJOR == $FRAMEWORK_HIGHEST_MAJOR - 1 )) && [[ $FRAMEWORK_TWO_TAG_EXISTS == "0" ]]; then
            # The previous major leaves full support when the current major gets its x.2.0 release
            SUPPORTED_BRANCHES="$SUPPORTED_BRANCHES $BRANCH"
        else
            echo "Branch $BRANCH is for CMS $CMS_MAJOR which is no longer in full support, it will not be merged up" >&2
        fi
    done
    echo $SUPPORTED_BRANCHES
}
