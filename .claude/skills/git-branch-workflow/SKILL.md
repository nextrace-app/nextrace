---
name: git-branch-workflow
description: "Branch and merge discipline for this repository. ALWAYS activate before starting any code or docs change that will be committed, before committing, before pushing, before opening a PR and before merging one. Every task starts on a new branch cut from the latest origin/main, and the branch is synced with origin/main before it is pushed for review and again right before it is merged."
license: MIT
metadata:
  author: nextrace
---

# Git branch workflow

`main` is the only long-lived branch. Everything else is a short-lived branch that ends in a PR into `main`.

## Start of a task: new branch from main

1. Run `git status --short`. If the tree is dirty with someone else's work, stop and ask what to do with it; never stash, discard or commit it silently.
2. `git fetch origin main`
3. Create the branch from the remote tip, not from whatever is checked out:
   `git switch -c <branch-name> --no-track origin/main`
4. Name it in short kebab-case after the change: `remove-events-map`, `feature/roles-permissions`, `fix/registration-error`.
5. Never commit on `main` and never reuse a branch whose PR is merged or closed. A new, unrelated request gets a new branch even if another feature branch is checked out. Only a follow-up to the same change stays on its branch (and its open PR).

## Before pushing for review and before merging: sync with main

Do this before the first push of the branch, before `gh pr create`, and once more right before any merge of the PR.

1. `git fetch origin main`
2. `git rev-list --count HEAD..origin/main` — if it is `0`, the branch is up to date; nothing to do.
3. Otherwise merge main into the branch: `git merge origin/main`. Use a merge, not a rebase: the branch may already be pushed, and this repository does not force-push.
   - In a session worktree created by the desktop app, call the `sync_with_base_branch` tool instead of running `git merge` yourself.
4. Resolve conflicts by keeping both sides' intent, never by dropping main's changes. Re-run the tests that cover the touched code and `vendor/bin/pint --dirty --format agent`, then commit the merge and push.
5. For an open PR, confirm with `gh pr view --json mergeStateStatus,mergeable`: it must not be `BEHIND` or `DIRTY` at merge time. If it is, sync again first.

## Rules

- Merge a PR only when the user says so. Merging means adding the `merge-queue` label (`gh pr edit <n> --add-label merge-queue`): the `merge queue` workflow syncs the branch with main, waits for the required checks and merges PRs one at a time. A PR with conflicts or failed checks is removed from the queue with a comment. Do not use `gh pr merge` or `--admin` directly.
- Never force-push, never rewrite pushed history, never delete a branch the user did not ask to delete.
- Keep `public/robots.txt` as the closed version when syncing (see CLAUDE.md); if a merge touches it, restore it with `git checkout origin/main -- public/robots.txt`.
