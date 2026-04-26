# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Add removeMedia() method to ItemInterface (#35)

### Fixed
- fix: handle invalid JSON in loadJsonAsArray to prevent TypeError (#33)

## Released
## [v6.2.0] - 2025-11-13
### Changed
- HTTP redirect handling (301, 302, 303, 307, 308) with loop protection and relative URL support (#29)
- Remove deprecated Factory usage from documentation and examples (#30)
- Make nextupdatetime more reliable (#14)

## [v6.1.2] - 2025-09-29
## Fixed
- Trim whitespace from URLs in Link properties and add tests for whitespace handling and relative URLs (#26)
- bin/feedio was not working anymore (#25)

## [v6.1.1] - 2025-08-16
### Changed
- Code quality improvements (#21)

## [v6.1.0] - 2025-08-13
### Changed
- Add logic to set the node's link from the <guid> element when isPermaLink="true" and no link is present. (#12)
- Do no longer default to 1800-01-01 as date for fetching feeds (#15)
- Don't set if-modified-since header when discover feeds (#19)

### Fixed
- Analysis of relative links for the Atom feed (#10)
- Implicit null deprecations fixed (#17)
