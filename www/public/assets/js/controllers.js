app
  .controller('baseCtrl', ($scope, GameFactory) => {
  $scope.DRAW = 'draw';
  $scope.user = {
    'username': 'Player',
    'symbol': 'X'
  };
  $scope.gameStarted = false;
  $scope.board = [];
  $scope.winner = null;
  $scope.bot = {};
  $scope.winnerCoordinates = [];

  $scope.intData = (data) => {
    $scope.board = data.board || [];
    if (data && data.hasOwnProperty('users')) {
      if (data.users.hasOwnProperty('player')) {
        $scope.user.username = data.users.player.userName;
        $scope.user.symbol = data.users.player.symbol;
      }
      if (data.users.hasOwnProperty('bot')) {
        $scope.bot.username = data.users.bot.userName;
        $scope.bot.symbol = data.users.bot.symbol;
      }
    }
    if (data.game && data.game.winner) {
      $scope.winner = data.game.winner;
      $scope.winnerCoordinates = data.game.coordinates || [];
    } else {
      $scope.winner = null;
    }
  };

  GameFactory.getInfo().then((res) => {
    const data = res.data || null;
    $scope.checkGameStarted(data);
    $scope.intData(data)
  });

  $scope.checkGameStarted = (data) => {
    if (data && data.hasOwnProperty('users')) {
      $scope.gameStarted = !!data.users.player
    }
  }

})
  .controller('startGameCtrl', ($scope, $rootScope, UserFactory) => {

    $scope.start = () => {
      UserFactory.create($scope.$parent.user)
        .then(
          (res) => {
            $scope.$parent.checkGameStarted(res.data);
            $scope.$parent.intData(res.data)
          }
        )
    }

  })
  .controller('boardCtrl', ($scope, $rootScope, UserFactory, GameFactory) => {

    $scope.makeMove = (rowIndex, columnIndex) => {

      if ($scope.$parent.winner) {
        return false;
      }

      if($scope.$parent.board[rowIndex][columnIndex]){
        return  false;
      }

      UserFactory.makeMove(rowIndex, columnIndex)
        .then(
          (res) => {
            $scope.$parent.checkGameStarted(res.data);
            $scope.$parent.intData(res.data)
          }
        )
    };

    $scope.restartGame = () => {
      GameFactory.restartGame()
        .then(
          (res) => {
            $scope.$parent.checkGameStarted(res.data);
            $scope.$parent.intData(res.data)
          }
        )
    };

    $scope.isWinCell = (coordinates) => {
      if($scope.$parent.winner){
        return !!$scope.$parent.winnerCoordinates.filter((item) => JSON.stringify(item) === JSON.stringify(coordinates)).length;
      }
      return  false;
    }
  });
