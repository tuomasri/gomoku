<?php

Route::resource('game', 'GameController', ['only' => ['store']]);

Route::resource('game.moves', 'GameMoveController', ['only' => ['store', 'destroy']]);
