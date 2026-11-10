bats_require_minimum_version 1.7.0

setup() {
    # GitHub Actions runs bash steps with -o pipefail
    set -o pipefail
    source "$BATS_TEST_DIRNAME/../funcs.sh"
    cat > "$BATS_TEST_TMPDIR/repositories.json" <<'EOF'
{
    "supportedModules": [
        {"github": "silverstripe/silverstripe-admin", "packagist": "silverstripe/admin", "majorVersionMapping": {"4": ["1"], "5": ["2"], "6": ["3"]}},
        {"github": "silverstripe/silverstripe-framework", "packagist": "silverstripe/framework", "majorVersionMapping": {"4": ["4"], "5": ["5"], "6": ["6"]}},
        {"github": "silverstripe/silverstripe-future", "packagist": "silverstripe/future", "majorVersionMapping": {"7": ["1"]}},
        {"github": "silverstripe/silverstripe-graphql", "packagist": "silverstripe/graphql", "majorVersionMapping": {"4": ["3", "4"], "5": ["5"], "6": ["6"]}}
    ],
    "misc": [
        {"github": "silverstripe/silverstripe-lorem", "packagist": "silverstripe/lorem", "majorVersionMapping": {"5": ["1"], "6": ["2"]}}
    ]
}
EOF
    # admin module majors mapped to CMS majors, plus a module major 4 for a CMS 7 pre-release
    ADMIN_MAP='{"1":"4","2":"5","3":"6","4":"7"}'
}

tags_json() {
    local JSON="" TAG
    for TAG in "$@"; do
        JSON="$JSON{\"name\": \"$TAG\"},"
    done
    echo "[${JSON%,}]" > "$BATS_TEST_TMPDIR/tags.json"
}

# framework_highest_major

@test "framework_highest_major returns the highest CMS major for framework" {
    run framework_highest_major "$BATS_TEST_TMPDIR/repositories.json"
    [ "$status" -eq 0 ]
    [ "$output" = "6" ]
}

@test "framework_highest_major is empty when framework is missing" {
    echo '{"supportedModules": [{"github": "a/b", "packagist": "a/b", "majorVersionMapping": {"5": ["1"]}}], "misc": []}' > "$BATS_TEST_TMPDIR/repositories.json"
    run framework_highest_major "$BATS_TEST_TMPDIR/repositories.json"
    [ "$status" -eq 0 ]
    [ "$output" = "" ]
}

@test "framework_highest_major fails on invalid JSON" {
    echo 'not json' > "$BATS_TEST_TMPDIR/repositories.json"
    run framework_highest_major "$BATS_TEST_TMPDIR/repositories.json"
    [ "$status" -ne 0 ]
}

# framework_two_tag_exists

@test "framework_two_tag_exists returns 1 when the x.2.0 tag exists" {
    tags_json 6.2.1 6.2.0 6.1.0 5.4.0
    run framework_two_tag_exists 6 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -eq 0 ]
    [ "$output" = "1" ]
}

@test "framework_two_tag_exists returns 0 when only a x.2.0 pre-release exists" {
    tags_json 6.2.0-beta1 6.1.0 5.4.0
    run framework_two_tag_exists 6 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -eq 0 ]
    [ "$output" = "0" ]
}

@test "framework_two_tag_exists ignores x.2.0 tags for other majors" {
    tags_json 6.1.0 5.2.0
    run framework_two_tag_exists 6 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -eq 0 ]
    [ "$output" = "0" ]
}

@test "framework_two_tag_exists checks the major it is given" {
    tags_json 6.1.0 5.2.0
    run framework_two_tag_exists 5 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -eq 0 ]
    [ "$output" = "1" ]
}

@test "framework_two_tag_exists returns 0 when there are no tags" {
    tags_json
    run framework_two_tag_exists 6 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -eq 0 ]
    [ "$output" = "0" ]
}

@test "framework_two_tag_exists fails on invalid JSON" {
    echo 'not json' > "$BATS_TEST_TMPDIR/tags.json"
    run framework_two_tag_exists 6 "$BATS_TEST_TMPDIR/tags.json"
    [ "$status" -ne 0 ]
}

# module_map

@test "module_map maps module majors to CMS majors" {
    run module_map "$BATS_TEST_TMPDIR/repositories.json" silverstripe/silverstripe-admin
    [ "$status" -eq 0 ]
    [ "$output" = '{"1":"4","2":"5","3":"6"}' ]
}

@test "module_map includes repositories in misc" {
    run module_map "$BATS_TEST_TMPDIR/repositories.json" silverstripe/silverstripe-lorem
    [ "$status" -eq 0 ]
    [ "$output" = '{"1":"5","2":"6"}' ]
}

@test "module_map only maps the first module major for a CMS major" {
    run module_map "$BATS_TEST_TMPDIR/repositories.json" silverstripe/silverstripe-graphql
    [ "$status" -eq 0 ]
    [ "$output" = '{"3":"4","5":"5","6":"6"}' ]
}

@test "module_map is empty for an unknown repository" {
    run module_map "$BATS_TEST_TMPDIR/repositories.json" lorem/ipsum
    [ "$status" -eq 0 ]
    [ "$output" = "" ]
}

@test "module_map fails on invalid JSON" {
    echo 'not json' > "$BATS_TEST_TMPDIR/repositories.json"
    run module_map "$BATS_TEST_TMPDIR/repositories.json" silverstripe/silverstripe-admin
    [ "$status" -ne 0 ]
}

# filter_supported_branches

@test "previous major is merged up before the current major has a x.2.0 tag" {
    run --separate-stderr filter_supported_branches "2.4 2 3.1 3" "$ADMIN_MAP" 6 0
    [ "$status" -eq 0 ]
    [ "$output" = "2.4 2 3.1 3" ]
    [ "$stderr" = "" ]
}

@test "previous major is not merged up at all once the current major has a x.2.0 tag" {
    run --separate-stderr filter_supported_branches "2.4 2 3.3 3" "$ADMIN_MAP" 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "3.3 3" ]
    [ "${stderr_lines[0]}" = "Branch 2.4 is for CMS 5 which is no longer in full support, it will not be merged up" ]
    [ "${stderr_lines[1]}" = "Branch 2 is for CMS 5 which is no longer in full support, it will not be merged up" ]
}

@test "majors older than the previous major are never merged up" {
    run --separate-stderr filter_supported_branches "1.13 1 2.4 2 3.1 3" "$ADMIN_MAP" 6 0
    [ "$status" -eq 0 ]
    [ "$output" = "2.4 2 3.1 3" ]
    [ "${stderr_lines[0]}" = "Branch 1.13 is for CMS 4 which is no longer in full support, it will not be merged up" ]
    [ "${stderr_lines[1]}" = "Branch 1 is for CMS 4 which is no longer in full support, it will not be merged up" ]
}

@test "next major pre-release branches are merged up" {
    run --separate-stderr filter_supported_branches "2.4 2 3.4 3 4.0 4" "$ADMIN_MAP" 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "3.4 3 4.0 4" ]
}

@test "branches that cannot be mapped to a CMS major are merged up" {
    run --separate-stderr filter_supported_branches "1.13 1 2.4 2" "" 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "1.13 1 2.4 2" ]
    [ "$stderr" = "" ]
}

@test "mapped and unmapped branches are filtered independently" {
    run --separate-stderr filter_supported_branches "2.4 2 3.3 3 9" '{"2":"5","3":"6"}' 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "3.3 3 9" ]
}

@test "nothing is merged up when every branch is out of full support" {
    run --separate-stderr filter_supported_branches "1.13 1 2.4 2" "$ADMIN_MAP" 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "" ]
}

@test "multi-digit module majors are mapped by their full major" {
    run --separate-stderr filter_supported_branches "9.1 9 10.0 10" '{"9":"5","10":"6"}' 6 1
    [ "$status" -eq 0 ]
    [ "$output" = "10.0 10" ]
    [ "${stderr_lines[0]}" = "Branch 9.1 is for CMS 5 which is no longer in full support, it will not be merged up" ]
    [ "${stderr_lines[1]}" = "Branch 9 is for CMS 5 which is no longer in full support, it will not be merged up" ]
}

@test "previous major is only merged up when the x.2.0 flag is exactly 0" {
    run --separate-stderr filter_supported_branches "2.4 2" "$ADMIN_MAP" 6 ""
    [ "$status" -eq 0 ]
    [ "$output" = "" ]
}

@test "an empty framework major drops every mapped branch without failing" {
    run --separate-stderr filter_supported_branches "2.4 2" '{"2":"5"}' "" 1
    [ "$status" -eq 0 ]
    [ "$output" = "" ]
    [[ "$stderr" == *"Branch 2.4 is for CMS 5 which is no longer in full support, it will not be merged up"* ]]
}

@test "filter_supported_branches fails on an invalid module map" {
    run filter_supported_branches "2.4 2" "not json" 6 1
    [ "$status" -ne 0 ]
}
