import React, { Component } from 'react';
import _ from 'lodash';
import axios from 'axios';
import GameBoard from './GameBoard';
import constants from './constants';

class Gomoku extends Component
{
    constructor(props) {
        super(props);

        this.state = {
            game: {},
            isLoading: false,
        };
    }

    isTerminatedGame() {
        return this.state.game.state === constants.GAME_TERMINATED;
    }

    handleMove(x, y) {
        if (this.isTerminatedGame()) {
            alert('Peli on päättynyt');

            return;
        }

        this.setState({isLoading: true});

        let route = `/api/game/${this.state.game.id}/moves`;
        let requestData = {
            x: x,
            y: y,
            player_id: this.getNextPlayer().id,
        };

        axios
            .post(route, requestData)
            .then(
                (response) => {
                    this.refreshStateFromResponse(response);
                },
                (error) => {
                    alert(error);

                    this.setState({isLoading: false});
                }
            );
    }

    undoLatestMove() {
        let latestMove = _.last(_.get(this.state.game, 'moves', []));
        let route = `/api/game/${this.state.game.id}/moves/${latestMove.id}`;

        this.setState({isLoading: true});

        axios
            .delete(route)
            .then(
                (response) => {
                    this.refreshStateFromResponse(response);
                },
                (error) => {
                    alert("Siirtoa ei voi perua");

                    this.setState({isLoading: false});
                }
            );
    }

    startGame() {
        this.setState({isLoading: true});

        let route = '/api/game';

        axios
            .post(route)
            .then(
                (response) => {
                    this.refreshStateFromResponse(response);
                },
                (error) => {
                    alert(error);

                    this.setState({isLoading: false});
                }
            )
    }

    getNextPlayer() {
        if (this.isTerminatedGame()) {
            return null;
        }

        let nextPlayer = null;
        let game = this.state.game;
        let players = _.get(game, 'players', []);

        // Uusi peli -> musta aloittaa
        if (game.state === constants.GAME_STARTED) {
            nextPlayer = _.find(players, (player) => player.color === constants.PLAYER_COLOR_BLACK);
        } else {
            let lastMove = _.last(game.moves);
            nextPlayer = _.find(players, (player) => lastMove.playerId !== player.id)
        }

        if (! nextPlayer) {
            throw new Error('Unable to resolve next player');
        }

        return nextPlayer;
    }

    refreshStateFromResponse(response) {
        let game = response.data;

        this.setState({
            game,
            isLoading: false,
        });
    }

    renderWinningNotice() {
        if (! this.isTerminatedGame()) {
            return;
        }

        let game = this.state.game;
        let winnerColor = _.get(game, 'winner.color', null);
        let message = 'Peli päättyi ';
        if (winnerColor) {
            message = message
                .concat(
                    (winnerColor === constants.PLAYER_COLOR_BLACK
                        ? 'mustan pelaajan voittoon. '
                        : 'valkoisen pelaajan voittoon. '
                    )
                )
                .concat(
                    'Häviäjää kehottaisin skarppaamaan :]'
                );
        } else {
            message = message.concat('tasapeliin. Skarpatkaa molemmat.');
        }

        return (
            <article className="message is-info">
                <div className="message-header">
                    <p>{message}</p>
                </div>
                <div className="message-body">
                    {this.renderNewGameButton()}
                </div>
            </article>
        );
    }

    renderNewGameButton() {
        let buttonText = this.state.isLoading ? 'Ladataan' : 'Uusi peli';

        return (
            <a
                onClick={() => this.startGame()}
                className="button is-success is-fullwidth"
            >
                {buttonText}
            </a>
        );
    }

    renderStartScreen() {
        let game = this.state.game;
        if (! _.isEmpty(game)) {
            return;
        }

        return (
            <section className="hero">
                <div className="hero-body">
                    <div className="container">
                        <h1 className="title">Gomoku</h1>
                    </div>
                    <div className="container">
                        <div className="columns">
                            <div className="column is-half is-offset-one-quarter">
                                {this.renderNewGameButton()}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            );
    }

    renderGameBoard() {
        let game = this.state.game;
        if (_.isEmpty(game)) {
            return;
        }

        return (
            <section className="section">
                <div className="container">
                    <div className="columns">
                        <div className="column">

                            {this.renderWinningNotice()}

                            <GameBoard
                                moves={game.moves}
                                players={game.players}
                                handleMove={(x, y) => {this.handleMove(x, y)}}
                                undoLatestMove={() => this.undoLatestMove()}
                            />
                        </div>
                    </div>
                </div>
            </section>
        );
    }

    render() {
        return (
            <div>
                {this.renderStartScreen()}

                {this.renderGameBoard()}
            </div>
        );
    }
}

export default Gomoku;