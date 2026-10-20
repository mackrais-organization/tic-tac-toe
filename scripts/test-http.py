#!/usr/bin/env python3
"""Exercise the public API with isolated cookies, locally or against a supplied URL."""
import contextlib
import http.cookiejar
import json
import pathlib
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1]


def check_api(base):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(method, payload=None, expected=200, path='/api/v1/game'):
        body = payload.encode() if isinstance(payload, str) else json.dumps(payload).encode() if payload is not None else None
        req = urllib.request.Request(base + path, data=body, method=method, headers={'Content-Type': 'application/json'})
        try:
            response = opener.open(req, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            data = response.read()
            assert response.status == expected, (method, path, response.status, data)
            assert 'application/json' in response.headers.get('Content-Type', ''), response.headers
            return json.loads(data)

    # Each run has its own cookie jar and cannot modify another browser's game.
    data = request('GET')
    assert 'player' not in data['users']
    request('PUT', {'rowIndex': 0, 'columnIndex': 0}, 400)
    request('POST', '{invalid', 422)
    for body in ['null', '[]', '42', '"text"']:
        request('POST', body, 422)
    for body in [{}, {'username': [], 'symbol': 'X', 'level': 'normal'},
                 {'username': 'Player', 'symbol': 'Z', 'level': 'normal'},
                 {'username': 'Player', 'symbol': 'X', 'level': 'bad'}]:
        request('POST', body, 422)
    data = request('POST', {'username': 'HTTP audit', 'symbol': 'O', 'level': 'normal'})
    assert data['users']['player']['level'] == 'normal'
    assert sum(cell == 'X' for row in data['board'] for cell in row) == 1
    again = request('POST', {'username': 'Other', 'symbol': 'O', 'level': 'hard'})
    assert again == data, 'Repeated start must not give the bot an extra turn'
    for coordinates in [{'rowIndex': -1, 'columnIndex': 0}, {'rowIndex': 3, 'columnIndex': 0},
                        {'rowIndex': 0, 'columnIndex': 3}, {'rowIndex': '0', 'columnIndex': 0},
                        {'rowIndex': 1.5, 'columnIndex': 0}, {'rowIndex': True, 'columnIndex': 0}, {}]:
        request('PUT', coordinates, 422)
        assert request('GET')['board'] == data['board']
    occupied = next((r, c) for r, row in enumerate(data['board']) for c, cell in enumerate(row) if cell)
    request('PUT', {'rowIndex': occupied[0], 'columnIndex': occupied[1]}, 422)
    free = next((r, c) for r, row in enumerate(data['board']) for c, cell in enumerate(row) if cell is None)
    moved = request('PUT', {'rowIndex': free[0], 'columnIndex': free[1]})
    assert moved['board'][free[0]][free[1]] == 'O'
    assert sum(cell == 'X' for row in moved['board'] for cell in row) == 2
    request('PATCH', {}, 405)
    request('GET', expected=404, path='/missing')
    request('GET', expected=410, path='/game.php')
    reset = request('DELETE')
    assert reset['board'] == [[None] * 3 for _ in range(3)]
    assert 'player' not in reset['users']
    request('POST', {'username': 'Local', 'symbol': 'X', 'level': 'normal', 'mode': []}, 422)
    local = request('POST', {'username': 'Local', 'symbol': 'O', 'level': 'normal', 'mode': 'duo'})
    assert local['mode'] == 'duo' and local['turn'] == 'X'
    assert local['board'] == [[None] * 3 for _ in range(3)], 'Duo must not invoke bot'
    request('POST', {}, 422, '/api/v1/game/undo')
    first = request('PUT', {'rowIndex': 0, 'columnIndex': 0})
    assert first['turn'] == 'O' and first['board'][0][0] == 'X'
    assert request('GET') == first, 'Refresh must restore match and undo state'
    undone = request('POST', {}, path='/api/v1/game/undo')
    assert undone['board'] == local['board'] and not undone['canUndo']
    for r, c in [(0, 0), (1, 0), (0, 1), (1, 1), (0, 2)]:
        final = request('PUT', {'rowIndex': r, 'columnIndex': c})
    assert final['game']['winner'] == 'X'
    assert final['game']['coordinates'] == [[0, 0], [0, 1], [0, 2]]
    request('POST', {}, 422, '/api/v1/game/undo')
    request('PUT', {'rowIndex': 2, 'columnIndex': 2}, 400)
    request('DELETE')
    fresh = request('POST', {'username': 'Local', 'symbol': 'X', 'level': 'normal'})
    assert fresh['roundId'] != local['roundId'] and fresh['mode'] == 'solo'
    print('HTTP smoke tests passed: validation, sessions, repeated start, moves, reset, 404/405/410.')


@contextlib.contextmanager
def local_server():
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    with tempfile.TemporaryDirectory(prefix='tic-http-') as directory:
        with open(pathlib.Path(directory) / 'server.log', 'w+') as log:
            process = subprocess.Popen(['php', '-d', 'display_errors=0', '-d', f'session.save_path={directory}',
                                        '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'www/public')],
                                       stdout=log, stderr=log)
            try:
                for _ in range(100):
                    if process.poll() is not None:
                        log.seek(0)
                        raise RuntimeError(log.read())
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=0.1):
                            break
                    except OSError:
                        time.sleep(0.05)
                else:
                    raise RuntimeError('PHP server did not become ready')
                yield f'http://127.0.0.1:{port}'
            finally:
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait()


if __name__ == '__main__':
    if len(sys.argv) > 1:
        check_api(sys.argv[1].rstrip('/'))
    else:
        with local_server() as url:
            check_api(url)
