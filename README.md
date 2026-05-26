[Русская версия](README.ru.md)

# Eleanor PHP Library

Modern PHP frameworks have gradually evolved into enormous ecosystems requiring installation procedures, server reconfiguration, routing layers, build pipelines, dependency graphs, and entire operational environments. For many projects this approach is unnecessarily heavyweight.

Eleanor PHP Library follows a different philosophy. The library is designed for developers who want lightweight, practical, transparent, framework-independent PHP code without hidden runtime magic or excessive abstraction layers.

No installation rituals.  
No mandatory router architecture.  
No framework-controlled application lifecycle.  
No dependency on complex server configuration.

Just download the library, connect it, and write PHP.

---

## Design principles

### 1. No installation required

The library works out of the box. You can simply copy the files into a project and start using them immediately.

Composer installation is optional:

```bash
composer require sunvas/eleanor-php-library
```

---

### 2. Shared hosting friendly

The library is intentionally designed to work in traditional hosting environments without requiring:

- rewrite engine configuration;
- CLI bootstrap scripts;
- daemon processes;
- containers;
- privileged server access.

---

### 3. Lightweight architecture

The goal of the library is not to replace PHP with another abstraction language. Eleanor PHP Library assists development while keeping the resulting code readable and predictable.

No hidden dependency injection containers.  
No opaque lifecycle management.  
No deeply nested framework internals.

---

### 4. Built-in core features

The library already includes:

- template engine;
- cache machines;
- MySQL driver;
- localization subsystem;
- delayed object initialization;
- structured error logging;
- coherent integration for third-party classes, interfaces, traits, and enums.

---

### 5. Delayed object creation

Objects may be initialized lazily. For example, a MySQL connection will not be established until it is actually required.

---

### 6. Structured error logging

The logging subsystem automatically organizes log files:

- similar errors are grouped together;
- old logs are archived automatically.

---

## Philosophy

Eleanor PHP Library does not attempt to dictate how applications must be structured.

It is a toolbox rather than a framework.

The main objective is simple:

- keep PHP lightweight;
- keep architecture understandable;
- keep the source code readable.

No puzzles under the hood.

---

## Requirements

- PHP 8.5 or higher

Code examples are available [here](https://github.com/Sunvas/eleanor-php-library-examples).