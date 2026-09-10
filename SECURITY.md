# Security Policy

This is a portfolio project, not a deployed service — but it is written as if it were one.

## Reporting a vulnerability

If you spot a security issue in this codebase, please open a
[GitHub Security Advisory](https://github.com/oziiqx/transactional-engine/security/advisories/new)
or email the address on the commit history. Please do not open a public issue for anything
exploitable.

## What is in scope

- Domain-logic flaws that let money be created, lost or double-spent.
- Idempotency or concurrency bugs in the payment / ledger paths.
- Injection, auth-bypass or information-disclosure in the API layer.

## What is not

- The Docker Compose stack ships with development credentials (`app` / `app`) and a
  placeholder `APP_SECRET`. It is for local use only and is not hardened for exposure to a
  network.
- Rate limiting, request signing and webhook signatures are implemented as building blocks,
  not as a complete threat model.
