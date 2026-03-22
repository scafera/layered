# scafera/layered

Layered architecture conventions for the Scafera framework. Defines the standard folder structure and service discovery for layered applications.

## What it provides

- `LayeredArchitecture` — implements `ArchitecturePackageInterface` from `scafera/kernel`
- Standard folder structure: `Controller/`, `Service/`, `Entity/`, `Command/`
- Autowired service discovery with `App\` namespace
- Attribute-based route loading from `src/Controller/`

## Requirements

- PHP >= 8.4
- scafera/kernel

## Usage

Require this package (or the `scafera/web-layered` metapackage) in your project. The kernel discovers it automatically via the `scafera-architecture` Composer extra field.

```json
{
    "require": {
        "scafera/layered": "^0.9"
    }
}
```

## License

MIT
