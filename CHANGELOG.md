# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-11-18

### Changed

- Requires the 2.0 of the suite core, which runs on [laravel-privacy](https://github.com/jeffersongoncalves/laravel-privacy), the engine shared by the privacy suites. No change to this package's API, config or tables.

## 1.0.0 - 2026-11-17

First release: CCPA/CPRA opt-out ledger for Laravel, part of the laravel-ccpa suite.

- Append-only opt-outs and opt-ins for Do Not Sell or Share (Cal. Civ. Code §1798.120) and Limit the Use of Sensitive Personal Information (§1798.121), with source, IP address and user agent.
- The `ccpa.gpc` middleware treats a Global Privacy Control signal (`Sec-GPC: 1`) as an opt-out of sale and sharing, recorded once per user or browser (CCPA Regulations §7025).
- Works for logged-in consumers and for browsers, through a random id in an encrypted cookie. A browser's choices move to the account on login.
- `canAskToOptIn()` enforces the 12-month wait before asking to opt back in (§1798.135(c)(4)).
- An optional POST endpoint for your opt-out links, the `@ccpaSaleAllowed` Blade condition for ad tags, and the `OptOutRecorded` event.

## [Unreleased]
