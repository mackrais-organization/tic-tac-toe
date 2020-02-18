# MackRais Tic Tac Toe (AngularJs + PHP) 

## Description
A good old game of tic tac toe.
Try your hand against our wonderful bot. ***MackRais MiniMax Bot v1.0***.
The bot uses the [Minimax Algorithm] to decide its moves. You can see it on [TicTacToe\User\MiniMaxBot].
You can try game here [http://tic-tac-toe.mackrais.com](http://tic-tac-toe.mackrais.com)

## Getting Started

### Use docker 

```bash
$ docker-compose build 
$ docker-compose up -d 
```

Start the project with composer:
```bash
$ composer install
```

#### Running with PHP's Built-in web server

After installing the packages, start PHP's built-in web server:
```bash
$ composer run --timeout=0 serve
```
You can then browse to [http://127.0.0.1:4000](http://127.0.0.1:4000)

If you want to start the serve using port different of 4000, you can start the server manually:
```bash
$ php -S 0.0.0.0:_YOU_PORT_ -t public/
```

If you need Xdebug 
```bash
$ composer run --timeout=0 serve-xdebug
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
            "userName": "MackRais MiniMax Bot v1.0",
            "symbol": "O"
        },
        "player": { // if user not sign up yet then null
            "userName": "Player",
            "symbol": "X"
        }
    }
}
```
