# Issue tracker: GitHub

Issues and PRDs for this repo live as GitHub issues. Use the `gh` CLI for all operations.

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --comments`, filtering comments by `jq` and also fetching labels.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `gh issue comment <number> --body "..."`
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `gh issue close <number> --comment "..."`

Infer the repo from `git remote -v` — `gh` does this automatically when run inside a clone.

## Module-scoped labels

Issues should include a `mod:<module>` label to indicate which module they belong to:

- `mod:vault` — file storage, buckets, albums, API
- `mod:content-pipeline` — thumbnails, zip, antivirus
- `mod:security` — rate limiting, IP blocking, CAPTCHA, file reputation
- `mod:panel-app` — user panel, landing page
- `mod:panel-admin` — admin panel
- `mod:analytics` — counters, broadcasting, stats
- `mod:identity` — users, teams, roles, auth

When creating issues that span multiple modules, apply all relevant `mod:` labels.

## When a skill says "publish to the issue tracker"

Create a GitHub issue.

## When a skill says "fetch the relevant ticket"

Run `gh issue view <number> --comments`.
