# Changelog

Notable changes are recorded here. Versioned entries use Semantic Versioning.

## [Unreleased]

### Added
- Responsive game menu and match screens, clear player turn indicators, themes, sound and animated winning lines.
- Server-backed local two-player mode, undo history and match restoration with per-browser scores.
- PHPUnit tests for game rules, bot tactics, user validation, session storage and routing.
- Exhaustive verification that the hard bot cannot lose as either symbol.
- HTTP regression checks for malformed requests, repeated starts and session persistence.
- Composer commands for formatting, PHPCS, PHPStan, Rector, ParaTest, coverage, Infection and dependency hygiene.
- GitHub Actions for PHP 8.5 quality checks, tests, reports, PR titles and tagged releases; no image build or deployment.

### Changed
- Require PHP 8.5 and use the official PHP-FPM image.
- Replace Nginx with Caddy in Docker for local development and production.
- Make the published HTTP address and port configurable, defaulting to 127.0.0.1:8080 behind the host Caddy proxy.
- Build application code into production images, mount source files in local mode and persist PHP sessions in a named volume.
- Easy uses random legal moves; normal wins or blocks immediate threats before choosing a random move; hard uses full minimax.

### Fixed
- Incorrect minimax scoring that mixed up deep wins, losses and draws.
- Out-of-bounds moves, moves after game over and stale bot user state.
- Repeated start requests granting an extra bot turn.
- Malformed JSON and invalid input types escaping validation.
- Missing difficulty in restored game responses and PHP implicit-nullability deprecations.
- Router state leaking between instances.

### Removed
- Broken legacy game.php API; it now returns HTTP 410 and directs clients to /api/v1/game.
- Obsolete Xdebug 2 configuration and PHP 7.4 configuration mount.
