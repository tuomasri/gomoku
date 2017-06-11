
import React, { Component } from 'react';
import _ from 'lodash';
import PropTypes from 'prop-types';
import constants from './constants';
import GameTile from "./GameTile";

class GameBoard extends Component
{
    constructor(props) {
        super(props);
    }

    createKey(x, y) {
        return '' + x + '.' + y;
    }

    createHashmapFromMoves() {
        return _.reduce(
            this.props.moves,
            (moveHashmap, gameMove) => {
                let key = this.createKey(gameMove.x, gameMove.y);

                moveHashmap[key] = gameMove;

                return moveHashmap;
            },
            {}
        );
    }

    createColumns(gameMoves, lastGameMove, column) {
        return _.times(
            constants.BOARD_SIZE,
            (row) => {
                let x = column;
                let y = row;
                let key = this.createKey(x, y);

                // Löytyy hashmapista jos tileen on tehty siirto
                let gameMoveInTile = _.get(gameMoves, key, null);

                // TRUE jos kyseessä pelin viimeisin siirto (käytetään siirron perumista varten)
                let isLatestMove = gameMoveInTile && _.isEqual(gameMoveInTile, lastGameMove);

                return (
                    <div className="level" key={key}>
                        <GameTile
                            x={x}
                            y={y}
                            move={gameMoveInTile}
                            players={this.props.players}
                            handleMove={this.props.handleMove}
                            isLatestMove={isLatestMove}
                            undoLatestMove={this.props.undoLatestMove}
                        />
                    </div>
                );
            }
        );
    }

    render() {
        let moves = this.createHashmapFromMoves();
        let lastGameMove = _.last(_.values(moves));
        let rows = _.times(constants.BOARD_SIZE, (row) => {
            let key = 'row_' + row;

            return (
                <div key={key} className="column">
                    {this.createColumns(moves, lastGameMove, row)}
                </div>
            );
        });

        return (
            <div className="columns">
                {rows}
            </div>
        );
    }
}

GameBoard.propTypes = {
    moves: PropTypes.arrayOf(constants.moveShape),
    players: PropTypes.arrayOf(constants.playerShape),
    handleMove: PropTypes.func,
    undoLatestMove: PropTypes.func,
};

export default GameBoard;