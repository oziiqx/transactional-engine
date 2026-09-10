# Transactional Engine

A multi-currency transactional engine for e-commerce / FinTech workloads, built to
demonstrate **Domain-Driven Design**, **Hexagonal (Ports & Adapters) Architecture** and
**CQRS** on a modern PHP 8.3 / Symfony 7.4 LTS stack.

> This is a portfolio codebase. It is deliberately over-engineered relative to its feature
> count — the point is to show how a large transactional system is *structured*, *tested*
> and *operated*, not to ship the smallest thing that works.

---

## Status

| Bounded context   | Responsibility                                                              | State |
|-------------------|----------------------------------------------------------------------------|-------|
| **Wallet**        | Multi-currency wallets, an append-only ledger, automated reconciliation.    | ✅ complete — domain, CQRS, persistence, REST + GraphQL, tests |
| **Payment**       | Idempotent payment processor with an explicit async state machine.          | 🔧 domain complete & tested; application/API in progress |
| **Pricing**       | Dynamic discount engine on a composable rule-chain.                         | 📋 designed |
| **Notification**  | Webhook dispatcher with HMAC signatures and exponential-backoff retries.    | 📋 designed |

The Wallet slice is the reference implementation: every other context follows the same
shape. CI-equivalent gates (`make qa`) are green — PHPStan level 10, EasyCodingStandard,
and 82 Pest tests across three suites.

---

## Core invariants

- **Money never leaks precision.** All amounts are integer minor units bound to an ISO-4217
  currency; cross-currency arithmetic is a type error, not a runtime bug.
- **The ledger is append-only.** A wallet's balance is a projection of immutable ledger
  entries; reconciliation re-derives it from the sum of entries and posts a system
  adjustment if the two ever diverge.
- **Writes are transactional; side effects are not.** Domain events are recorded inside the
  aggregate, persisted with the write, and only relayed to asynchronous consumers *after*
  the transaction commits.
- **Concurrent movements can't corrupt a balance.** The gap-free per-wallet ledger sequence
  is a unique index — two racing writes cannot both land.

---

## Architecture

Each bounded context is a vertical slice with the same three layers. Dependencies point
**inward only** — the domain knows nothing about Symfony, Doctrine, HTTP or messaging.

```
src/<Context>/
├── Domain/          Aggregates, entities, value objects, domain events, repository
│                    *interfaces*, domain services. Pure PHP. No framework. Unit-tested.
├── Application/     Command & query handlers (CQRS), the *ports* the domain needs from
│                    the outside world, read models, application-level DTOs.
└── Infrastructure/  Adapters: Doctrine repositories & XML mappings, Messenger wiring,
                     API Platform resources & state processors, HTTP middleware, clients.
```

The **Shared Kernel** (`src/Shared/`) holds cross-context primitives: `AggregateRoot`,
strongly-typed `EntityId` (UUIDv7), `DomainEvent`, `Money`, `Currency`, the
command/query/event bus ports and their Messenger adapters, the distributed-lock and
idempotency abstractions, and the RFC 7807 *Problem Details* error mapper.

### CQRS

- **Write side** — commands flow through `command.bus` (Messenger). Middleware chain:
  validation → domain-event relay → Doctrine transaction. Handlers load an aggregate, call
  a behaviour method, and persist. Invariants live in the aggregate, never in the handler.
- **Read side** — queries flow through `query.bus` and hit purpose-built read models through
  plain DBAL, bypassing the ORM.
- **Events** — a Doctrine listener harvests the events an aggregate recorded during a flush;
  a bus middleware relays them *after* commit. Synchronous subscribers update read models
  in-process; asynchronous work (invoices, webhooks) is translated into explicit integration
  messages and routed to RabbitMQ.

### API

[API Platform 4](https://api-platform.com) exposes the application layer as both **REST**
and **GraphQL**, with an auto-generated **OpenAPI 3.1** document. Resources are thin DTOs;
custom *state processors* translate them into CQRS commands and custom *state providers*
translate query results back. RFC 7807 `application/problem+json` is the single error format
for every transport, carrying the domain's stable error code and context.

---

## Tech stack

| Concern         | Choice                                                                   |
|-----------------|-------------------------------------------------------------------------|
| Language        | PHP 8.3 — `strict_types` everywhere, readonly classes, enums, attributes |
| Framework       | Symfony 7.4 LTS (kept in the infrastructure layer)                       |
| API             | API Platform 4 — REST + GraphQL + OpenAPI 3.1                            |
| Persistence     | PostgreSQL 16, Doctrine ORM 3 / DBAL 4, custom types, hand-written migrations |
| Messaging       | Symfony Messenger over RabbitMQ (async) + in-process (sync)              |
| Locking         | Symfony Lock (Redis) for reconciliation runs                            |
| Static analysis | PHPStan **level 10** (max) + strict rules + Doctrine/Symfony extensions  |
| Style           | EasyCodingStandard (PSR-12 + strict), Rector                            |
| Tests           | Pest 3 / PHPUnit 11 — unit, integration, functional                     |
| Runtime         | Docker Compose: php-fpm, nginx, PostgreSQL, Redis, RabbitMQ, workers     |

---

## Getting started

Requires Docker Desktop (or Docker Engine + Compose v2). Nothing else is installed on the
host.

```bash
cp .env .env.local              # optional: override anything locally
make build                      # build the PHP image
make up                         # start the stack, wait for health checks
make install                    # composer install inside the container
make db-create db-migrate       # provision PostgreSQL
```

Then:

- Swagger UI: <http://localhost:8081/api/docs>
- GraphiQL: <http://localhost:8081/api/graphql/graphiql>
- RabbitMQ management: <http://localhost:15672> (`app` / `app`)

> Ports 8081 (HTTP) and 5433 (PostgreSQL) are used to avoid clashing with anything already
> listening on the conventional 8080 / 5432.

### Day-to-day

```bash
make qa                 # coding standard + PHPStan level 10 + the whole test suite
make test-unit          # fast, framework-free domain tests
make test-integration   # integration + functional suites
make consume            # run the message workers in the foreground
make shell              # drop into the container
make help               # list every target
```

---

## Repository layout

```
.
├── bin/console                 Symfony console entrypoint
├── config/                     Framework configuration (an infrastructure concern)
│   ├── packages/               One file per bundle
│   └── routes/
├── docker/                     Image definition and per-service config
├── migrations/                 Doctrine migrations (hand-written, reviewed)
├── public/index.php            HTTP entrypoint
├── src/
│   ├── Shared/                 Shared Kernel
│   ├── Wallet/                 Bounded context (reference implementation)
│   ├── Payment/                Bounded context
│   ├── Pricing/                Bounded context
│   └── Notification/           Bounded context
└── tests/
    ├── Unit/                   Pure domain — no container, no I/O
    ├── Integration/            Real database + Messenger, rolled back per test
    └── Functional/             Full HTTP stack through API Platform
```

---

## Testing strategy

| Suite         | Boots kernel? | Touches DB?         | What it proves                                       |
|---------------|:-------------:|:-------------------:|-----------------------------------------------------|
| `unit`        | no            | no                  | Aggregate invariants, value-object algebra.          |
| `integration` | yes           | yes (rolled back)   | Handlers + Doctrine repositories + event relay.      |
| `functional`  | yes           | yes (rolled back)   | REST / GraphQL contracts, middleware, Problem Details.|

The domain layer has no framework dependency, so `unit` runs in milliseconds. Integration
and functional suites wrap every test in a transaction that is rolled back afterwards
(`dama/doctrine-test-bundle`), so they stay isolated and fast.

---

## License

[MIT](LICENSE).
