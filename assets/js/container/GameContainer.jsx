import _ from 'lodash';
import axios from 'axios';
import React from 'react';
import Routing from '../../utils/Routing';
import NewGame from '../presentation/NewGame';
import GameBoard from '../presentation/GameBoard';
import { GAME_CONSTANTS } from '../../utils/Constants';

class GameContainer extends React.Component
{
    constructor(props)
    {
        super(props);

        this.state = {
            game: null,
            isLoading: false,
        };
    }

    getNextPlayer()
    {
        const moves = _.get(this.state.game, 'moves', []);
        const players = _.get(this.state.game, 'players', []);
        const lastMove = _.last(_.get(this.state.game, 'moves', {}));
        const isEmptyOfMoves = moves.length === 0;

        return isEmptyOfMoves // Uusi peli -> musta aloittaa
            ? _.find(players, player => player.color === GAME_CONSTANTS.PLAYER_COLOR_BLACK)
            : _.find(players, player => lastMove.playerId !== player.id);
    }

    startGame()
    {
        this.setState({isLoading: true});

        axios
            .post(Routing.API.game.create())
            .then(response => {
                const game = response.data;

                this.setState({game, isLoading: false});
            });
    }

    makeMove(x, y)
    {
        this.setState({isLoading: true});

        axios
            .post(
                Routing.API.gameMove.create(this.state.game.id),
                {x, y, player_id: this.getNextPlayer().id}
            )
            .then(response => {
                const game = response.data;

                this.setState({game, isLoading: false});
            });
    }

    undoLatestMove()
    {
        const latestMove = _.last(_.get(this.state.game, 'moves', {}));

        this.setState({isLoading: true});

        axios
            .delete(
                Routing.API.gameMove.deleteLatest(this.state.game.id)
            )
            .then(response => {
                const game = response.data;
                this.setState({game, isLoading: false});
            });
    }

    render()
    {
        return [
            <NewGame
                key="gomoku-game-new"
                game={this.state.game}
                startGame={() => this.startGame()}
            />,
            <GameBoard
                key="gomoku-game-board"
                game={this.state.game}
                makeMove={(x, y) => this.makeMove(x, y)}
                undoLatestMove={() => this.undoLatestMove()}
            />
        ];
    }
}

export default GameContainer;