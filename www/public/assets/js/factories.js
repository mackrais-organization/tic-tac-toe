const API_GAME_URL = 'api/v1/game';
app
  .factory('GameFactory', function ($http) {
  return {
    getInfo: () => $http.get(API_GAME_URL),
    restartGame: () => $http.delete(API_GAME_URL),
  }
})
  .factory('UserFactory', function ($http) {
    return {
      create: (user) => $http.post(API_GAME_URL, {
        'username': user.username,
        'symbol': user.symbol,
      }),
      makeMove: (rowIndex, columnIndex) => $http.put(API_GAME_URL, {
        'rowIndex': rowIndex,
        'columnIndex': columnIndex,
      }),
    }
  });
