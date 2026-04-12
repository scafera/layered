# scafera/layered

Scafera Layered is an opinionated architecture package for the Scafera framework. It defines and enforces a strict, layered approach for building applications.

## Philosophy

In Scafera, architecture is not a guideline — it is an installed package.

This package enforces every convention at validation time via `scafera validate`, designed to run in CI. Violations are caught before code ships, not after.

All execution is explicit: no event subscribers, no listeners, no auto-discovered side effects. If behavior is not visible in the code being read, it does not happen.

## Architecture model

The package organizes application code into five layers:

| Layer | Purpose |
|-------|---------|
| `Controller/` | Single-action invokables — delegate to services, no business logic |
| `Service/` | All business logic lives here |
| `Repository/` | Data access repositories |
| `Entity/` | Domain data |
| `Command/` | CLI entry points via `#[AsCommand]` |

## Validators

Eleven validators enforce the layered conventions:

| Validator | Rule |
|-----------|------|
| **Tests directory** | Tests must be in `tests/` only |
| **Controller location** | Controllers must live in `src/Controller/` |
| **Controller naming** | No `Controller` suffix; single-word names at root, multi-word in subfolders |
| **Single-action controllers** | Must use `__invoke()`, no other public methods (except `__construct`) |
| **Controller test parity** | Every controller must have a matching test |
| **Command test parity** | Every command must have a matching test |
| **Service location** | Only recognized directories under `src/` |
| **Service final** | All services must be declared `final` |
| **Namespace conventions** | PSR-4 namespace must match file path |
| **Layer dependencies** | Enforces downward-only dependency flow (Controller → Service → Repository → Entity) |
| **No implicit execution** | No `EventSubscriberInterface` or `#[AsEventListener]` in userland |

## Advisors

Non-blocking hints that never affect the exit code:

| Advisor | What it checks |
|---------|---------------|
| **Test sync** | Warns when a controller or command is modified in git but its test is not |

The test sync advisor requires git and gracefully skips with a reason when prerequisites are not met.

## Generators

Scaffold new files with conventions baked in:

| Generator | Command | What it creates |
|-----------|---------|-----------------|
| **Controller** | `scafera make controller <Name>` | Single-action controller + test |
| **Service** | `scafera make service <Name>` | Final service class + test |
| **Command** | `scafera make command <Name>` | Console command + test |

All generators support nested names (e.g. `Order/Create`, `Report/Generate`) and reject convention-violating suffixes like `Controller` or `Command`.

## Project structure

```
src/
├── Controller/    ← single-action, attribute routing
│   ├── Home.php
│   └── Order/
│       ├── Create.php
│       └── List.php
├── Command/       ← #[AsCommand], delegate to services
├── Service/       ← all business logic
├── Repository/    ← data access
└── Entity/        ← domain data
tests/
├── Controller/    ← one test per controller
├── Command/       ← one test per command
└── Service/       ← unit tests where needed
```

## When to use

Use this package when you want:

- Strong conventions and predictable structure
- Clear separation of responsibilities
- Automated enforcement of architectural rules

Avoid this package if you require a flexible or custom architecture with minimal constraints.

## Installation

```bash
composer require scafera/layered
```

The kernel discovers it automatically via the `scafera-architecture` Composer extra field.

## Requirements

- PHP >= 8.4
- scafera/kernel

## License

MIT
