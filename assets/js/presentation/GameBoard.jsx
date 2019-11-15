import _ from 'lodash';
import React from 'react';
import PropTypes from 'prop-types';
import GameTile from './GameTile';
import { GAME_SHAPE, GAME_CONSTANTS, isTerminatedGame } from '../../utils/Constants';

class GameBoard extends React.Component
{
    static createKey(x, y)
    {
        return '' + x + '.' + y;
    }

    static createGameMoveMap(gameMoves)
    {
        return _.keyBy(gameMoves, move => GameBoard.createKey(move.x, move.y));
    }

    constructor(props)
    {
        super(props);
    }

    createGameTiles()
    {
        if (! this.props.game) {
            return;
        }

        const gameMoves = _.get(this.props.game, 'moves', []);
        const gameMoveMap = GameBoard.createGameMoveMap(gameMoves);
        const lastGameMove = _.last(gameMoves);

        const rows = _.times(
            GAME_CONSTANTS.BOARD_SIZE,
            y => {
                const columns = _.times(
                    GAME_CONSTANTS.BOARD_SIZE,
                    x => {
                        const key = GameBoard.createKey(x, y);
                        const gameMoveInTile = _.get(gameMoveMap, key, null);
                        const isLatestMove = gameMoveInTile === lastGameMove;

                        return (
                            <GameTile
                                x={x}
                                y={y}
                                key={key}
                                move={gameMoveInTile}
                                players={this.props.game.players}
                                makeMove={(x, y) => this.props.makeMove(x, y)}
                                undoMove={(x, y) => this.props.undoMove(x, y)}
                                isLatestMove={isLatestMove}
                                isTerminatedGame={isTerminatedGame(this.props.game)}
                            />
                        );
                    });

                return (
                    <div key={'row_' + y} className="flex justify-between p-0 m-0">
                        {columns}
                    </div>
                );
        });

        return (
            <div>
                {rows}
            </div>
        );
    }

    render()
    {
        if (! this.props.game) {
            return null;
        }

        const classNames = isTerminatedGame(this.props.game)
            ? "flex mb-4 my-4 mx-4 justify-center opacity-25"
            : "flex mb-4 my-4 mx-4 justify-center";

        return (
            <div className={classNames}>
                {this.createGameTiles()}
            </div>
        );
    }
}

GameBoard.propTypes = {
    game: GAME_SHAPE,
    makeMove: PropTypes.func.isRequired,
    undoMove: PropTypes.func.isRequired,
};

export default GameBoard;