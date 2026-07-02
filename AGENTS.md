# AGENTS.md

## Eleanor PHP Library

This document describes the development principles of Eleanor PHP Library.

The library intentionally follows its own architecture and conventions.
When contributing code, documentation, tests, or suggestions, preserve the
existing design instead of replacing it with currently popular practices.

---

# Philosophy

Eleanor PHP Library is designed to be:

- frameworkless;
- lightweight;
- fast;
- predictable;
- easy to debug;
- suitable for shared hosting.

Dependencies are avoided whenever possible.

The library should remain understandable after years of maintenance.

Simple solutions are preferred over sophisticated ones.

---

# Architecture

The project is intentionally independent of PSR, Composer, dependency injection
containers, routers, and other large ecosystems.

Do not introduce abstractions that solve problems the library does not have.

Prefer explicit code over hidden magic.

Lazy initialization is encouraged for expensive objects.

Public APIs should remain stable whenever possible.

---

# Compatibility

The library targets modern PHP.

Do not add compatibility code for obsolete PHP versions.

Avoid dependencies on extensions that are not commonly available.

---

# Performance

Performance matters.

However:

- readability is more important than micro-optimizations;
- optimizations should remain understandable;
- avoid unnecessary allocations;
- avoid unnecessary filesystem access;
- avoid unnecessary database queries.

When object creation can reasonably be delayed, prefer lazy initialization.

---

# Error handling

Errors should be explicit.

Unexpected situations should throw meaningful exceptions.

Diagnostic information is more valuable than silent failures.

Logging should remain useful for debugging production systems.

---

# Documentation

Documentation is part of the source code.

Comments and PHPDoc should explain behaviour, intent, or rationale rather
than repeat what is already obvious from the implementation.

Describe what the code does from the caller's perspective, not how it is
implemented internally.

When behaviour is non-trivial, describe it explicitly instead of using
generic phrases such as "Get value" or "Set property".

Keep comments concise.

Avoid redundant comments.

Use natural technical English.

Prefer:

- Get...
- Create...
- Return...
- Resolve...
- Initialize...
- Generate...

Avoid:

- Obtaining...
- Making...
- Setting...

Boolean descriptions should usually begin with "Whether...".

---

# Naming

Names should describe purpose rather than implementation.

Prefer clarity over brevity.

Avoid unnecessary abbreviations unless they are well established inside
the project (for example: A11N and L10N).

---

# Dependencies

Do not introduce external packages when the functionality can be
implemented cleanly inside the library.

The library should remain self-contained.

---

# Coding style

Prefer:

- strict typing;
- readonly properties;
- small focused methods;
- explicit control flow;
- descriptive exception messages.

Avoid:

- unnecessary inheritance;
- unnecessary interfaces;
- unnecessary design patterns;
- premature abstraction.

---

# AI contribution guidelines

When suggesting changes:

- preserve the existing architecture;
- preserve project terminology;
- preserve the public API whenever possible;
- improve readability without changing behaviour;
- prefer evolutionary improvements over rewrites.

Do not automatically suggest:

- Composer packages;
- PSR migrations;
- Symfony components;
- Laravel conventions;
- dependency injection containers;
- service locators;
- routers;
- event buses;
- unnecessary factories;
- unnecessary interfaces.

The goal is to improve Eleanor PHP Library, not to transform it into a
different project.