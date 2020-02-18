app
  .factory('GameFactory', function ($http) {
  return {
    getInfo: () => $http.get('game.php', {params: {'action': 'game-info'}}),
    restartGame: () => $http.post('game.php', {
      'action': 'restart',
    }),
  }
})
  .factory('UserFactory', function ($http) {
    return {
      create: (user) => $http.post('game.php', {
        'action': 'create-user',
        'username': user.username,
        'symbol': user.symbol,
      }),
      makeMove: (rowIndex, columnIndex) => $http.post('game.php', {
        'action': 'make-a-move',
        'rowIndex': rowIndex,
        'columnIndex': columnIndex,
      }),
    }
  })
