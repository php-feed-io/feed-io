# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

### Fixed

## [v6.1.0] - 2025-08-13
### Changed
- Add logic to set the node's link from the <guid> element when isPermaLink="true" and no link is present. (#12)
- Do no longer default to 1800-01-01 as date for fetching feeds (#15)
- Don't set if-modified-since header when discover feeds (#19)

### Fixed
- Analysis of relative links for the Atom feed (#10)
- Implicit null deprecations fixed (#17)
