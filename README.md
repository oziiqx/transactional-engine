# Transactional Engine

A production-grade, multi-currency transactional engine for e-commerce / FinTech workloads,
built to demonstrate **Domain-Driven Design**, **Hexagonal (Ports & Adapters) Architecture**
and **CQRS** on a modern PHP 8.3 / Symfony 7.2 stack.

> This is a portfolio codebase. It is deliberately over-engineered relative to its feature
> count: the point is to show how a large transactional system is *structured*, *tested* and
> *operated*, not to ship the smallest thing that works.

---

## What it does

| Bounded context   | Responsibility                                                                                  |
|-------------------|------------------------------------------------------------------------------------------------|
| **Wallet**        | Multi-currency wallets, an append-only ledger, and an automated reconciliation routine.         |
| **Payment**       | An idempotent payment processor driven by an explicit async state machine.                       |
| **Pricing**       | A dynamic discount engine built on a composable rule-chain.                                      |
| **Notification**  | A webhook dispatcher with HMAC signatures and exponential-backoff retries.                       |

### Core invariants

- **Money never leaks precision.** All amounts are integer minor units bound to an ISO-4217
  currency; cross-currency arithmetic is a type error, not a runtime bug.
- **The ledger is append-only.** Wallet balances are a projection of immutable ledger entries;
  reconciliation proves the projection against the sum of entries.
- **Payments are idempotent.** Every state transition is keyed by an idempotency token; a
  replayed request returns the original result and never double-charges.
- **Writes are transactional; side effects are not.** Domain events are recorded inside the
  aggregate, persisted with the write, and only then relayed to asynchronous consumers.

---

## Architecture

Each bounded context is a vertical slice with the same three layers. Dependencies point
**inward only** — the domain knows nothing about Symfony, Doctrine, HTTP or messaging.

```
src/<Context>/
├── Domain/          Entities, aggregates, value objects, domain events, repository *interfaces*,
│                    domain services. Pure PHP. No framework. 100% unit-testable.
├── Application/     Command & query handlers (CQRS), application services, DTOs, and the
│                    *ports* (interfaces) the domain needs from the outside world.
└── Infrastructure/  Adapters: Doctrine repositories & mappings, Messenger wiring, API Platform
                     resources & state processors, HTTP middleware, external clients.
```

The **Shared Kernel** (`src/Shared/`) holds cross-context primitives: `AggregateRoot`,
`DomainEvent`, `Money`, `Currency`, the command/query/event bus ports and their Messenger
adapters, the idempotency store, and the RFC 7807 error mapper.

### CQRS

- **Write side** — commands flow through `command.bus` (Messenger). Middleware chain:
  validation → domain-event relay → Doctrine transaction. Handlers load an aggregate, call a
  behaviour method, and persist. Invariants live in the aggregate.
- **Read side** — queries flow through `query.bus` and hit purpose-built read models
  (denormalised projections) through plain DBAL, bypassing the ORM for throughput.
- **Events** — domain events recorded during a write are relayed to `event.bus` after the
  transaction commits. Synchronous subscribers update read models in-process; asynchronous
  ones (PDF invoice generation, webhook delivery) are routed to RabbitMQ.

### API

[API Platform 4](https://api-platform.com) exposes the application layer as both **REST** and
**GraphQL**, with an auto-generated **OpenAPI 3.1** document at `/api/docs.json`. API Platform
resources are thin DTOs; custom *state processors* translate them into commands, and custom
*state providers* translate query results back. RFC 7807 *Problem Details* is the single error
format for every transport.

---

## Tech stack

| Concern            | Choice                                                              |
|--------------------|--------------------------------------------------------------------|
| Language           | PHP 8.3 (`strict_types` everywhere, readonly classes, enums, attributes) |
| Framework          | Symfony 7.2 (kept in the infrastructure layer)                      |
| API                | API Platform 4 — REST + GraphQL + OpenAPI 3.1                        |
| Persistence        | PostgreSQL 16, Doctrine ORM 3 / DBAL 4, custom types, Migrations     |
| Messaging          | Symfony Messenger over RabbitMQ (async) + in-process (sync)          |
| Locking            | Symfony Lock (Redis) for reconciliation runs                        |
| Static analysis    | PHPStan level 10, Psalm errorLevel 1                                 |
| Coding standard    | EasyCodingStandard (PSR-12 + strict + Symplify), Rector             |
| Tests              | Pest 3 / PHPUnit 11 — unit, integration, functional                 |
| Runtime            | Docker Compose: php-fpm, nginx, postgres, redis, rabbitmq, workers   |

---

## Getting started

Requires Docker Desktop (or Docker Engine + Compose v2). Nothing else is installed on the host.

```bash
cp .env .env.local            # adjust secrets if you like
make build                    # build the PHP image
make up                       # start the stack, wait for health checks
make install                  # composer install inside the container
make db-create db-migrate     # provision PostgreSQL
make db-fixtures              # optional: demo data
```

Then:

- API docs (Swagger UI): <http://localhost:8080/api/docs>
- GraphQL playground: <http://localhost:8080/api/graphql>
- RabbitMQ management: <http://localhost:15672> (`app` / `app`)

### Day-to-day

```bash
make qa                 # coding standard + PHPStan + Psalm + full test suite
make test-unit          # fast, framework-free domain tests
make consume            # run the message workers in the foreground
make shell              # drop into the container
make help               # list every target
```

---

## Repository layout

```
.
├── bin/console                 Symfony console entrypoint
├── config/                     Framework configuration (infrastructure concern)
│   ├── packages/               One file per bundle
│   └── routes/
├── docker/                     Image definitions and service config
├── migrations/                 Doctrine migrations (generated, reviewed, committed)
├── public/index.php            HTTP entrypoint
├── src/
│   ├── Shared/                 Shared Kernel
│   ├── Wallet/                 Bounded context
│   ├── Payment/                Bounded context
│   ├── Pricing/                Bounded context
│   └── Notification/           Bounded context
└── tests/
    ├── Unit/                   Pure domain, no container, no I/O
    ├── Integration/            Real database, real Messenger, rolled back per test
    └── Functional/             Full HTTP stack through API Platform
```

---

## Testing strategy

| Suite         | Boots kernel? | Touches DB? | What it proves                                            |
|---------------|:-------------:|:-----------:|----------------------------------------------------------|
| `unit`        | no            | no          | Aggregate invariants, value-object algebra, rule chains.  |
| `integration` | yes           | yes (rolled back) | Handlers + Doctrine repositories + event relay.     |
| `functional`  | yes           | yes (rolled back) | REST/GraphQL contracts, middleware, Problem Details.|

The domain layer carries no framework dependency, so `unit` runs in milliseconds and stays
at 100% line coverage. Integration and functional suites wrap every test in a transaction
that is rolled back afterwards (`dama/doctrine-test-bundle`).

---

## License

MIT — see [LICENSE](LICENSE).
