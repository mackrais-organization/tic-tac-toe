/* Game presentation. PHP owns moves, turns, undo and match results. */
'use strict';

const $ = (selector) => document.querySelector(selector);
const storageKey = 'mackrais-game-v1';
const freshScore = () => ({ wins: 0, draws: 0, losses: 0, history: [] });
let preferences = { mode: 'solo', difficulty: 'normal', symbol: 'X', theme: 'dark', sound: false, nickname: 'You' };
let scores = { solo: freshScore(), duo: freshScore() };
try {
  const saved = JSON.parse(localStorage.getItem(storageKey));
  if (saved) {
    for (const [key, values] of Object.entries({ mode: ['solo', 'duo'], difficulty: ['easy', 'normal', 'hard'], symbol: ['X', 'O'], theme: ['light', 'dark'] })) {
      if (values.includes(saved.preferences?.[key])) preferences[key] = saved.preferences[key];
    }
    preferences.sound = saved.preferences?.sound === true;
    if (typeof saved.preferences?.nickname === 'string') preferences.nickname = saved.preferences.nickname.trim().slice(0, 24) || 'You';
    for (const mode of ['solo', 'duo']) {
      const score = saved.scores?.[mode];
      if (score && ['wins', 'draws', 'losses'].every(key => Number.isSafeInteger(score[key]) && score[key] >= 0) && Array.isArray(score.history)) {
        scores[mode] = { wins: score.wins, draws: score.draws, losses: score.losses, history: score.history.filter(item => ['win', 'draw', 'loss'].includes(item)).slice(-5) };
      }
    }
  }
} catch { /* Storage is optional, including when previewed from file://. */ }

let board = Array(9).fill(null);
let moves = [];
let turn = 'X';
let result = null;
let thinking = false;
let activeMatch = false;
let canUndo = false;
let settingsChanged = false;
let countedRounds = [];
try { countedRounds = JSON.parse(localStorage.getItem('mackrais-counted-rounds') || '[]'); } catch {}
if (!Array.isArray(countedRounds)) countedRounds = [];
let audioContext;
let currentScreen = 'menu';
const cells = [];
const botSymbol = () => preferences.symbol === 'X' ? 'O' : 'X';
const save = () => { try { localStorage.setItem(storageKey, JSON.stringify({ preferences, scores })); } catch {} };

function sound(frequency = 440, duration = .07) {
  if (!preferences.sound) return;
  try {
    audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
    audioContext.resume();
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();
    oscillator.connect(gain); gain.connect(audioContext.destination);
    oscillator.type = 'sine'; oscillator.frequency.value = frequency;
    gain.gain.setValueAtTime(.05, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(.001, audioContext.currentTime + duration);
    oscillator.start(); oscillator.stop(audioContext.currentTime + duration);
  } catch { /* Audio support is optional. */ }
}
async function request(method, body, suffix = '') {
  const response = await fetch(`/api/v1/game${suffix}`, {
    method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
    ...(body ? { body: JSON.stringify(body) } : {})
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.message || 'Unable to complete the request.');
  return data;
}
function applyState(data) {
  activeMatch = Boolean(data.users.player);
  board = data.board.flat();
  moves = board.flatMap((value, index) => value ? [index] : []);
  turn = data.turn;
  canUndo = data.canUndo;
  result = data.game ? { winner: data.game.winner, line: (data.game.coordinates || []).map(([r, c]) => r * 3 + c) } : null;
  if (activeMatch) {
    preferences.mode = data.mode;
    preferences.symbol = data.users.player.symbol;
    preferences.difficulty = data.users.player.level;
    preferences.nickname = data.users.player.userName;
    syncSettings();
  }
  if (result && data.roundId && !countedRounds.includes(data.roundId)) {
    const key = result.winner === 'draw' ? 'draws' : result.winner === preferences.symbol ? 'wins' : 'losses';
    const score = scores[preferences.mode];
    score[key]++;
    score.history = [...score.history, key === 'wins' ? 'win' : key === 'draws' ? 'draw' : 'loss'].slice(-5);
    countedRounds = [...countedRounds, data.roundId].slice(-100);
    try { localStorage.setItem('mackrais-counted-rounds', JSON.stringify(countedRounds)); } catch {}
    if (result.winner !== 'draw') sound(660, .2);
  }
  save();
}
function syncSettings() {
  for (const name of ['mode', 'difficulty', 'symbol']) $(`input[name="${name}"][value="${preferences[name]}"]`).checked = true;
  $('#nickname').value = preferences.nickname;
}
async function perform(action) {
  if (thinking) return;
  thinking = true;
  $('#api-error').hidden = true;
  render();
  try { applyState(await action()); }
  catch (error) {
    $('#api-error').textContent = `${error.message} Please try again.`;
    $('#api-error').hidden = false;
    // A lost response may still have committed a move. Recover before allowing another.
    try { applyState(await request('GET')); } catch {}
  } finally { thinking = false; render(); }
}
async function play(index) {
  if (thinking || result || board[index] || !activeMatch) return;
  sound(turn === 'X' ? 480 : 340);
  await perform(() => request('PUT', { rowIndex: Math.floor(index / 3), columnIndex: index % 3 }));
}
async function newRound() {
  if (thinking) return;
  const params = { username: preferences.nickname, symbol: preferences.symbol, level: preferences.difficulty, mode: preferences.mode };
  await perform(async () => {
    await request('DELETE');
    return request('POST', params);
  });
}
function render() {
  const isSolo = preferences.mode === 'solo';
  const humanTurn = turn === preferences.symbol;
  const art = $('#opponent-art');
  if (art.dataset.mode !== preferences.mode) {
    art.innerHTML = isSolo
      ? '<rect x="12" y="17" width="40" height="34" rx="10"/><path d="M32 9v8M8 30v8m48-8v8M24 30v5m16-5v5M25 43h14"/>'
      : '<circle cx="32" cy="23" r="10"/><path d="M14 53v-5a18 18 0 0 1 36 0v5"/>';
    art.dataset.mode = preferences.mode;
  }
  $('#human-name').textContent = isSolo ? preferences.nickname : 'Player 1';
  $('#human-caption').textContent = `Playing as ${preferences.symbol}`;
  $('#opponent-name').textContent = isSolo ? 'MiniMax' : 'Player 2';
  $('#opponent-caption').textContent = isSolo ? `Computer · ${preferences.difficulty[0].toUpperCase() + preferences.difficulty.slice(1)}` : `Playing as ${botSymbol()}`;
  $('#human-avatar').className = `mark mark-${preferences.symbol.toLowerCase()}`;
  $('#bot-avatar').className = `mark mark-${botSymbol().toLowerCase()}`;
  $('#human-player').classList.toggle('active', !result && humanTurn);
  $('#bot-player').classList.toggle('active', !result && !humanTurn);
  $('.game-card').classList.toggle('thinking', thinking);
  $('.game-card').classList.toggle('turn-o', !result && turn === 'O');
  for (const [id, active] of [['#human-player', humanTurn], ['#bot-player', !humanTurn]]) {
    const indicator = $(`${id} .active-dot`);
    indicator.textContent = !result && active ? (thinking ? 'WAIT…' : 'YOUR TURN') : '';
  }
  let status = thinking ? 'Updating game…' : isSolo ? 'Your move' : `Player ${humanTurn ? '1' : '2'}’s turn — ${turn}`;
  let caption = thinking ? 'Waiting for the game server…' : 'Select an empty square.';
  if (result) {
    if (result.winner === 'draw') { status = 'Draw'; caption = 'No empty squares remain. Start a new round to play again.'; }
    else if (result.winner === preferences.symbol) { status = isSolo ? 'You win' : 'Player 1 wins'; caption = 'Three in a row. Start a new round to play again.'; }
    else { status = isSolo ? 'MiniMax wins' : 'Player 2 wins'; caption = 'Three in a row. Start a new round to play again.'; }
  }
  $('#status-text').textContent = status;
  $('#board-caption').textContent = caption;
  $('#round-message').textContent = result ? 'Round complete' : isSolo ? `${turn} to play` : 'Place your mark';
  $('#menu-difficulty').textContent = `${preferences.difficulty[0].toUpperCase() + preferences.difficulty.slice(1)} difficulty`;
  $('#match-type').textContent = isSolo ? 'VS COMPUTER' : 'LOCAL MULTIPLAYER';
  $('#game-level').textContent = isSolo ? preferences.difficulty.toUpperCase() : '2 PLAYERS';
  const firstAvailable = board.findIndex(cell => cell === null);
  cells.forEach((cell, index) => {
    const value = board[index];
    if (cell.dataset.symbol !== (value ?? '')) {
      cell.replaceChildren();
      if (value) { const mark = document.createElement('span'); mark.className = `mark mark-${value.toLowerCase()}`; mark.setAttribute('aria-hidden', 'true'); cell.append(mark); }
      cell.dataset.symbol = value ?? '';
    }
    cell.disabled = Boolean(value || result || thinking || !activeMatch);
    cell.tabIndex = index === firstAvailable && !cell.disabled ? 0 : -1;
    cell.dataset.preview = turn === 'X' ? '×' : '○';
    cell.classList.toggle('winning', result?.line.includes(index) ?? false);
    cell.setAttribute('aria-label', `Row ${Math.floor(index / 3) + 1}, column ${index % 3 + 1}: ${value ?? 'empty'}`);
  });
  $('#undo').disabled = Boolean(result || thinking || !canUndo);
  $('#difficulty-field').disabled = !isSolo;
  $('#difficulty-badge').textContent = `${{ easy: '01', normal: '02', hard: '03' }[preferences.difficulty]} / 03`;
  $('#difficulty-description').textContent = { easy: 'Chooses random squares. Good for a first game.', normal: 'Blocks immediate threats. Can miss forks.', hard: 'Looks ahead through every move. Plays without mistakes.' }[preferences.difficulty];
  const score = scores[preferences.mode];
  for (const key of ['wins', 'draws', 'losses']) $(`#${key}`).textContent = score[key];
  $('#wins-label').textContent = isSolo ? 'You' : 'Player 1';
  $('#losses-label').textContent = isSolo ? 'MiniMax' : 'Player 2';
  const total = score.wins + score.draws + score.losses;
  $('#score-message').textContent = total ? `${total} round${total === 1 ? '' : 's'} played` : 'No rounds played';
  $('#history').replaceChildren(...score.history.map(item => { const dot = document.createElement('i'); dot.className = item; dot.title = item; return dot; }));
  $('#history').setAttribute('aria-label', score.history.length ? `Recent results: ${score.history.join(', ')}` : 'No completed rounds yet');
  $('#game-wins').textContent = score.wins;
  $('#game-losses').textContent = score.losses;
  $('#game-draws').textContent = score.draws;
  $('#continue-game').hidden = !activeMatch;
  $('#round-result').hidden = !result;
  $('#board-caption').hidden = Boolean(result);
  $('#new-round-label').textContent = result ? 'PLAY AGAIN' : 'NEW ROUND';
  if (result) {
    $('#result-label').textContent = result.winner === 'draw' ? 'DRAW' : result.winner === preferences.symbol ? (isSolo ? 'YOU WIN!' : 'PLAYER 1 WINS!') : (isSolo ? 'MINIMAX WINS' : 'PLAYER 2 WINS!');
    $('#result-detail').textContent = result.winner === 'draw' ? 'No winner this round.' : 'Three in a row.';
    $('#round-result').classList.toggle('loss', result.winner === botSymbol());
  }
  for (const id of ['#new-round', '#play-computer', '#play-friend', '#continue-game', '#open-settings', '#menu-settings']) $(id).disabled = thinking;
  drawWinningLine();
}

for (let index = 0; index < 9; index++) {
  const cell = document.createElement('button');
  cell.type = 'button'; cell.className = 'cell'; cell.dataset.symbol = '';
  cell.addEventListener('click', () => play(index));
  cell.addEventListener('keydown', event => {
    const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -3, ArrowDown: 3 }[event.key];
    if (!step) return;
    event.preventDefault();
    let target = index;
    for (let tries = 0; tries < 9; tries++) {
      target = (target + step + 9) % 9;
      if (!cells[target].disabled) { cells[target].focus(); break; }
    }
  });
  cells.push(cell); $('#board').append(cell);
}
for (const name of ['mode', 'difficulty', 'symbol']) {
  $(`input[name="${name}"][value="${preferences[name]}"]`).checked = true;
  document.querySelectorAll(`input[name="${name}"]`).forEach(input => input.addEventListener('change', () => {
    preferences[name] = input.value; settingsChanged = true; save(); render();
  }));
}
$('#new-round').addEventListener('click', newRound);
$('#undo').addEventListener('click', () => perform(() => request('POST', {}, '/undo')));
$('#reset-score').addEventListener('click', () => $('#reset-dialog').showModal());
$('#reset-dialog').addEventListener('close', () => {
  if ($('#reset-dialog').returnValue === 'reset') { scores[preferences.mode] = freshScore(); save(); render(); }
});
function applyAppearance() {
  document.documentElement.classList.toggle('dark', preferences.theme === 'dark');
  $('#theme-toggle').setAttribute('aria-pressed', String(preferences.theme === 'light'));
  $('#theme-toggle').setAttribute('aria-label', `Switch to ${preferences.theme === 'dark' ? 'light' : 'dark'} theme`);
  $('#sound-toggle').setAttribute('aria-pressed', String(preferences.sound));
  $('#sound-toggle').setAttribute('aria-label', `Turn sound ${preferences.sound ? 'off' : 'on'}`);
  $('meta[name="theme-color"]').content = preferences.theme === 'dark' ? '#101d31' : '#e8eef6';
}
$('#theme-toggle').addEventListener('click', () => { preferences.theme = preferences.theme === 'light' ? 'dark' : 'light'; save(); applyAppearance(); });
$('#sound-toggle').addEventListener('click', () => { preferences.sound = !preferences.sound; save(); applyAppearance(); sound(); });
$('#show-rules').addEventListener('click', () => $('#rules-dialog').showModal());
function showScreen(screen) {
  currentScreen = screen;
  $('#menu-screen').hidden = screen !== 'menu';
  $('#play-screen').hidden = screen !== 'play';
  $('#back-to-menu').hidden = screen !== 'play';
  $('#footer-menu').hidden = screen !== 'play';
  $('#footer-credit').hidden = screen === 'play';
  render();
  requestAnimationFrame(drawWinningLine);
}
async function startMatch(mode) {
  if (thinking) return;
  preferences.mode = mode;
  $(`input[name="mode"][value="${mode}"]`).checked = true;
  save();
  showScreen('play');
  await newRound();
}
$('#play-computer').addEventListener('click', () => startMatch('solo'));
$('#play-friend').addEventListener('click', () => startMatch('duo'));
$('#continue-game').addEventListener('click', () => showScreen('play'));
$('#back-to-menu').addEventListener('click', () => showScreen('menu'));
$('#footer-menu').addEventListener('click', () => showScreen('menu'));
for (const id of ['#open-settings', '#menu-settings']) $(id).addEventListener('click', () => $('#settings-dialog').showModal());
$('#nickname').value = preferences.nickname;
$('#nickname').addEventListener('input', event => { preferences.nickname = event.target.value.trim().slice(0, 24) || 'You'; settingsChanged = true; save(); render(); });
$('#settings-dialog').addEventListener('close', () => {
  if (settingsChanged && activeMatch) newRound();
  settingsChanged = false;
});
function drawWinningLine() {
  const svg = $('#win-line');
  const winning = Boolean(result && result.winner !== 'draw');
  svg.classList.toggle('is-visible', winning);
  $('.board-shell').classList.toggle('o-wins', result?.winner === 'O');
  $('#victory-burst').classList.toggle('active', winning);
  if (!winning || currentScreen !== 'play') return;
  const rect = $('#board').getBoundingClientRect();
  if (!rect.width) return;
  const [first, , last] = result.line;
  const center = index => {
    const cell = cells[index].getBoundingClientRect();
    return [(cell.x + cell.width / 2 - rect.x) / rect.width * 300, (cell.y + cell.height / 2 - rect.y) / rect.height * 300];
  };
  const a = center(first), b = center(last);
  const dx = Math.sign(b[0] - a[0]), dy = Math.sign(b[1] - a[1]);
  const line = svg.querySelector('line');
  for (const [key, value] of Object.entries({ x1: a[0] - dx * 20, y1: a[1] - dy * 20, x2: b[0] + dx * 20, y2: b[1] + dy * 20 })) line.setAttribute(key, value);
}
window.addEventListener('resize', drawWinningLine);
applyAppearance();
showScreen('menu');
perform(() => request('GET'));
