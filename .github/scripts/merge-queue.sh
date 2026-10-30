#!/usr/bin/env bash
# Serial merge queue for `main`.
#
# PRs enter the queue with the `merge-queue` label and are processed one at a time, oldest label first.
# For each PR: bring the branch up to date with main (merge commit), wait for the required checks on
# that exact commit, re-check right before merging that the branch is still in sync and unchanged, then merge.
# A PR leaves the queue (label removed, comment posted) on merge conflicts, failed checks or a timeout.
set -euo pipefail

: "${GH_TOKEN:?GH_TOKEN is required}"
: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is required}"

REPO="$GITHUB_REPOSITORY"
export GH_REPO="$REPO"

BASE=main
LABEL=merge-queue
# Keep in sync with the required status checks of the `main` branch protection.
REQUIRED_CHECKS=("ci (8.4)" "ci (8.5)" "quality")
CHECKS_TIMEOUT=2400
DISPATCH_GRACE=90
POLL_INTERVAL=20
MAX_ATTEMPTS=3

log() {
    echo "[queue] $*" >&2
}

leave_queue() {
    local pr=$1 reason=$2

    gh pr edit "$pr" --remove-label "$LABEL" >/dev/null || true
    gh pr comment "$pr" --body "Removed from the merge queue: $reason" >/dev/null || true
    log "#$pr left the queue: $reason"
}

next_queued_pr() {
    local number labeled_at

    gh pr list --state open --base "$BASE" --label "$LABEL" --json number --jq '.[].number' |
        while read -r number; do
            labeled_at=$(gh api "repos/$REPO/issues/$number/events" --paginate \
                --jq "[.[] | select(.event == \"labeled\" and .label.name == \"$LABEL\")] | last | .created_at" |
                grep -v null | tail -n1 || true)
            echo "${labeled_at:-9999} $number"
        done | sort | head -n1 | cut -d' ' -f2
}

behind_by() {
    gh api "repos/$REPO/compare/$BASE...$1" --jq .behind_by
}

# Prints the new head sha once main has been merged into the PR branch.
sync_branch() {
    local pr=$1 sha=$2 new

    gh api -X PUT "repos/$REPO/pulls/$pr/update-branch" -f expected_head_sha="$sha" >/dev/null || return 1

    for _ in $(seq 1 30); do
        new=$(gh pr view "$pr" --json headRefOid --jq .headRefOid)
        if [[ $new != "$sha" ]]; then
            echo "$new"
            return 0
        fi
        sleep 5
    done

    return 1
}

# Prints ok, failed, pending or missing for the required checks of a commit.
checks_state() {
    local runs name line status conclusion failed=0 pending=0 missing=0

    runs=$(gh api "repos/$REPO/commits/$1/check-runs?per_page=100" --paginate \
        --jq '.check_runs[] | [.name, .status, (.conclusion // "")] | @tsv')

    for name in "${REQUIRED_CHECKS[@]}"; do
        line=$(awk -F'\t' -v n="$name" '$1 == n { print; exit }' <<<"$runs")

        if [[ -z $line ]]; then
            missing=1
            continue
        fi

        IFS=$'\t' read -r _ status conclusion <<<"$line"

        if [[ $status != completed ]]; then
            pending=1
        elif [[ $conclusion != success && $conclusion != skipped && $conclusion != neutral ]]; then
            failed=1
        fi
    done

    if ((failed)); then
        echo failed
    elif ((pending)); then
        echo pending
    elif ((missing)); then
        echo missing
    else
        echo ok
    fi
}

# Prints ok, failed or timeout. Commits pushed with GITHUB_TOKEN do not trigger pull_request workflows,
# so when no checks show up the CI workflows are dispatched on the branch instead.
wait_for_checks() {
    local branch=$1 sha=$2 waited=0 dispatched=0 state

    while ((waited <= CHECKS_TIMEOUT)); do
        state=$(checks_state "$sha")

        case $state in
            ok | failed)
                echo "$state"
                return 0
                ;;
            missing)
                if ((!dispatched && waited >= DISPATCH_GRACE)); then
                    log "no checks on ${sha:0:7}, dispatching CI on $branch"
                    gh workflow run tests.yml --ref "$branch" >&2
                    gh workflow run lint.yml --ref "$branch" >&2
                    dispatched=1
                fi
                ;;
        esac

        sleep "$POLL_INTERVAL"
        waited=$((waited + POLL_INTERVAL))
    done

    echo timeout
}

process_pr() {
    local pr=$1 attempt info state draft cross branch sha current

    for ((attempt = 1; attempt <= MAX_ATTEMPTS; attempt++)); do
        info=$(gh pr view "$pr" --json state,isDraft,isCrossRepository,headRefName,headRefOid \
            --jq '[.state, .isDraft, .isCrossRepository, .headRefName, .headRefOid] | @tsv')
        IFS=$'\t' read -r state draft cross branch sha <<<"$info"

        if [[ $state != OPEN ]]; then
            log "#$pr is $state, skipping"
            return 0
        fi
        if [[ $draft == true ]]; then
            leave_queue "$pr" "the PR is a draft."
            return 0
        fi
        if [[ $cross == true ]]; then
            leave_queue "$pr" "PRs from forks are not supported."
            return 0
        fi

        if (($(behind_by "$sha") > 0)); then
            log "#$pr is behind $BASE, syncing"
            if ! sha=$(sync_branch "$pr" "$sha"); then
                leave_queue "$pr" "it could not be brought up to date with \`$BASE\` automatically (merge conflicts?). Resolve it and add the \`$LABEL\` label again."
                return 0
            fi
        fi

        log "#$pr waiting for checks on ${sha:0:7}"
        case $(wait_for_checks "$branch" "$sha") in
            failed)
                leave_queue "$pr" "required checks failed on \`${sha:0:7}\`."
                return 0
                ;;
            timeout)
                leave_queue "$pr" "required checks did not finish in time."
                return 0
                ;;
        esac

        current=$(gh pr view "$pr" --json headRefOid --jq .headRefOid)
        if [[ $current != "$sha" ]] || (($(behind_by "$sha") > 0)); then
            log "#$pr changed or fell behind $BASE while checks ran, retrying"
            continue
        fi

        if gh pr merge "$pr" --merge --match-head-commit "$sha" >&2; then
            log "#$pr merged"
            return 0
        fi
        log "#$pr merge was rejected, retrying"
    done

    leave_queue "$pr" "it could not be merged after $MAX_ATTEMPTS attempts."
}

while pr=$(next_queued_pr) && [[ -n $pr ]]; do
    log "processing #$pr"
    process_pr "$pr"
    # Whatever happened, the PR must not stay queued, or this loop would pick it up again.
    gh pr edit "$pr" --remove-label "$LABEL" >/dev/null || true
done

log "queue is empty"
