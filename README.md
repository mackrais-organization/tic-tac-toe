# MackRais Tic Tac Toe (JavaScript + PHP)

## Description
A good old game of tic tac toe.
Try your hand against our wonderful bot. ***MackRais MiniMax Bot v1.0.3***.
The bot uses the [Minimax Algorithm] to decide its moves. You can see it on [TicTacToe\User\MiniMaxBot].
You can try game here [http://tic-tac-toe.mackrais.com](http://tic-tac-toe.mackrais.com)

## Game interface

The responsive game has separate menu and match screens, local two-player mode,
three computer difficulties, explicit turn indicators and animated winning lines.
PHP owns the board, bot moves, results and undo history for both modes. Reloading
restores the current session through Continue. Preferences and match scores are
stored in this browser; they are not an account or a competitive leaderboard.

`POST /api/v1/game` accepts optional `mode` (`solo` or `duo`, default `solo`).
`POST /api/v1/game/undo` undoes the last player turn (including the computer reply
in solo mode). Completed rounds cannot be undone. Responses include `mode`,
`roundId`, `turn` and `canUndo` alongside the existing board, users and result.

## Getting Started

### Docker: local development and production

Requires Docker Engine and Docker Compose v2 or newer. The application targets PHP 8.5.
Copy `.env.example` to `.env` on a fresh checkout, or edit your existing `.env`:

```dotenv
HTTP_BIND_ADDRESS=127.0.0.1
HTTP_PORT=8080
```

The host port is configurable: if 8080 is occupied, use 8081 or another available port.
Port 80 is used only inside the Caddy container. PHP-FPM is not published to the host.
There are no fixed container names; use a different `COMPOSE_PROJECT_NAME` and HTTP port
for each independent installation.

Local development (source files are mounted, so edits appear immediately):

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Open http://127.0.0.1:8080. Rebuild after changing Composer dependencies or Dockerfiles.
The local configuration uses PHP error logs rather than displaying errors inside JSON responses.
Xdebug is not installed in these images.

Production (application code and Composer autoload files are built into images):

```bash
docker compose build --pull
docker compose up -d
```

On this project's VPS, Caddy runs directly on the host and owns ports 80/443.
Keep `HTTP_BIND_ADDRESS=127.0.0.1` and configure Caddy to proxy to the selected
`HTTP_PORT`. Add this site block to the existing host Caddyfile (adjust the domain):

```caddyfile
tic-tac-toe.mackrais.com {
    reverse_proxy 127.0.0.1:8080
}
```

Validate the host configuration before reloading it:

```bash
sudo caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
sudo systemctl reload caddy
```

Caddy manages public HTTPS. The request path is:
`browser -> host Caddy (443) -> 127.0.0.1:8080 -> container Caddy (80) -> PHP-FPM (9000)`.
The Caddy inside Docker serves static files and forwards PHP requests via FastCGI; it does not
bind host ports 80/443. See the [Caddy reverse_proxy documentation](https://caddyserver.com/docs/caddyfile/directives/reverse_proxy).

This localhost upstream assumes Caddy runs on the host. A containerized proxy would
need a shared Docker network instead of its own localhost.
For direct external access without a reverse proxy, set `HTTP_BIND_ADDRESS=0.0.0.0`
and choose the desired `HTTP_PORT`.

```bash
docker compose logs -f
docker compose down
```

PHP sessions persist in the `php-sessions` named volume across container replacements.
`docker compose down -v` also removes those sessions. Use the same `-f` arguments as
above when managing the local development stack.

### Running without Docker

Requires PHP 8.5 and Composer:

```bash
cd www
composer install
composer run --timeout=0 serve
```

Open http://127.0.0.1:4000. To use another port:

```bash
php -S 127.0.0.1:4001 -t public/
```

### Response 

```js
{
    "board": [
        ["O","O","X"],
        [null,"X",null],
        ["X",null,"O"]
    ],
    "game": { // if game not finished then null
        "winner": "X",
        "coordinates": [
            [0,2],
            [1,1],
            [2,0]
        ]
    },
    "users": {
        "bot": { 
            "userName": "MackRais MiniMax Bot v1.0.3",
            "symbol": "O"
        },
        "player": { // omitted until a player starts a game
            "userName": "Player",
            "symbol": "X",
            "level": "normal"
        }
    }
}
```

### Game difficulty

- **Easy:** random legal moves.
- **Normal:** take an immediate win, otherwise block an immediate loss, otherwise choose a random legal move. Forks can beat it.
- **Hard:** full minimax with depth-aware scoring; cannot lose when starting from a valid game.

### Tests and quality checks

Run from `www/` using PHP 8.5 with `dom`, `mbstring`, `xml`, `xmlwriter` and Xdebug for coverage:

```bash
composer install
composer ci:quality       # validation, audit, unused dependencies, PSR-12, PHPStan, Rector dry run
composer test             # PHPUnit, including exhaustive hard-bot verification
composer test:parallel    # same suite in two isolated workers
composer ci:coverage     # HTML, Clover, XML and JUnit in var/coverage
composer ci:infection    # mutation tests for Board, Game, User and Storage
composer ci:http         # HTTP regression tests (requires Python 3)
composer ci:pack          # complete local quality gate
composer format          # apply PSR-12 formatting
```

From the repository root, `python3 scripts/test-http.py` starts a temporary PHP server
and checks the API. `python3 scripts/test-http.py http://127.0.0.1:8080` checks Docker
with an isolated cookie jar. It does not touch games in other browser sessions.

PHPStan runs at level 5 without a baseline; Rector checks PHP 8.5 compatibility.
Psalm is omitted because this small application already has PHPStan.
Infection targets the game core; HTTP orchestration is verified by the smoke tests.
The mutation gates require at least 80% MSI and 85% covered MSI.
The exhaustive search is excluded from repeated mutation runs to keep them practical.
All caches/reports are under `www/var/` and are ignored by Git.
Dev tools run on the host or GitHub runner; production images install no dev packages.

### GitHub workflows and releases

`php-ci.yml` runs quality checks, coverage, parallel tests, mutations and HTTP checks on
pull requests and pushes to main/master/develop. It uploads reports for 14 days and
validates Compose files. There are no image build, push or deployment jobs.
PR titles use Conventional Commits, for example `fix(bot): block immediate losses`.

Record changes under `[Unreleased]` in `CHANGELOG.md`. Before a release, move those
entries into `## [1.1.0] - YYYY-MM-DD` (using the intended version), commit, and push
the matching `v1.1.0` tag. The release workflow reruns CI and publishes a GitHub release
from that exact changelog section only after checks pass. It uses `GITHUB_TOKEN` and
requires no deployment or registry secrets. Keep `package.json` version aligned when
preparing a release; no historical version numbers are fabricated here.
