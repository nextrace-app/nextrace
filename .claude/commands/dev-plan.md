---
description: Planning mode. Discuss the task and propose options without changing anything.
argument-hint: [what to plan]
---

Planning mode for: $ARGUMENTS

For the rest of this conversation (until the user says to start work) you only discuss and plan. You change nothing.

Allowed:
- Read code, tickets, the Figma file and docs; search the codebase; run read-only commands (git status/log/diff, route:list, config:show, read-only database queries).
- Ask the user clarifying questions.

Not allowed:
- Editing, creating or deleting files; running migrations, seeders, installs or formatters; creating branches, commits or PRs.
- Creating or editing ClickUp tickets, Figma content or Forge settings, or touching production.

How to answer:
1. Restate the goal in one or two sentences and name what is unclear.
2. Look at the relevant code first, so the options rest on what exists.
3. Offer two or three options with their trade-offs (scope, risk, dependencies, design impact), and say which one you recommend and why.
4. List the open decisions that are the user's to make.
5. End with the concrete next step: the ticket text to agree on (per the project workflow: ticket, then design, then code).

Keep it short. Do not start implementing until the user explicitly says so; a "yes" to an option is a choice, not a start signal, so confirm once before leaving planning mode.
