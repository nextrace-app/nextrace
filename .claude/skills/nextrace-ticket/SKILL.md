---
name: nextrace-ticket
description: "How to create a NextRace ticket in ClickUp. ALWAYS activate when the user asks for any change to the site, design, content or infrastructure (add, change, fix, remove, improve, 'let's make...'), before touching Figma or code. A change request starts a discussion, not work: clarify details, agree dependencies and approach, decide whether the design changes, then write the ticket."
license: MIT
metadata:
  author: nextrace
---

# NextRace tickets

Flow for every change: **discussion -> ticket -> design (if the UI changes) -> development**. This skill covers the first two steps. The design step is the `nextrace-figma-design` skill; the branch and PR steps are `git-branch-workflow`.

ClickUp list: **NextRace**, id `1500530000060970` (https://app.clickup.com/1500530000009189/v/l/li/1500530000060970).

## 1. Treat the request as the start of a discussion

Any request to change something is not yet a task. Do not open Figma or the code, and do not create the ticket straight away. First talk it through with the user. Read the relevant code and the Figma page first so the questions are specific, then ask (use `AskUserQuestion` for choices with a clear set of options, plain chat for open questions). Ask in the user's language.

Cover, as far as they apply:

- **Goal and scope.** What problem does it solve, who sees it, what is explicitly out of scope.
- **Behaviour and details.** States (empty, loading, error, logged out), edge cases, both locales (English and Greek strings), mobile and tablet behaviour.
- **Dependencies.** Which new packages, services, env vars or API keys it would need. Say which existing ones could do the job instead; do not change dependencies without approval (see CLAUDE.md).
- **Approach.** The realistic options with trade-offs and a recommendation, not a survey. Name the files and areas that would change (controller, Inertia page, component, migration, lang files, Wayfinder routes).
- **Data and SEO.** New fields or migrations, seeders and factories, indexing impact (`SITE_INDEXABLE`), sitemap.
- **Tests.** What must be covered (happy path, failure paths, edge cases).
- **Design impact.** Does any screen, component or token in Figma change? If yes, the ticket gets a design part (see below).
- **Priority and size.** Whether it blocks something, and whether it should be split.

Stop asking when the answers no longer change what will be built. Summarise what was agreed and get the user's explicit OK on the summary before creating the ticket.

## 2. Create the ticket

Create it with `clickup_create_task` in the list above only after the user confirms the summary. One ticket is one change that can be reviewed in one PR; split larger work into subtasks (`parent`) or separate tickets.

**Name:** a short imperative phrase in the user's working language, e.g. "Главная: секция Upcoming events". Prefix with the area when it helps (Header, Footer, Home, Events, Auth).

**Description (markdown), in this order:**

1. **Why**: the problem or goal in one or two sentences.
2. **What changes**: the agreed behaviour, including states and both languages.
3. **Design**: Figma frames and components to add or change (link them once the design exists), or "No design change".
4. **Approach**: agreed approach, files or areas touched, dependencies (or "no new dependencies").
5. **Out of scope**: what was deliberately left out.
6. **Acceptance criteria**: a checklist that can be verified, including the tests that must pass.

**Fields:** tag `design-sync` when the code is being aligned to an existing design; tag `design` when the ticket includes new design work; tag `bug`, `feature` or `chore` for the kind of change. Set a priority. Leave the assignee empty unless the user names one.

## 3. Move the ticket through the flow

1. Ticket created with status to do and "Design" set to either "No design change" or "Design needed".
2. If design is needed, run the `nextrace-figma-design` skill. When it is finished, update the ticket: add the Figma links under **Design** and a short comment saying what changed in the file (and whether the Style guide changed).
3. Take the ticket into development only when its description matches the design. Use the ticket id in the branch name or the PR description so they link to each other, and follow `git-branch-workflow`.
4. When the PR is merged, comment on the ticket with the PR link and close it. If the work turned up follow-ups, create new tickets for them instead of widening this one.

## Rules

- Never start design or code work from a chat message alone. If the work is already in progress and no ticket exists, stop, write the ticket, and get it confirmed.
- Do not create tickets for things the user only asked a question about.
- If the ticket text and the user's later instructions disagree, update the ticket first, then continue.
- A ticket is never silently edited after development starts: add a comment explaining the change in scope.
