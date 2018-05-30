import _ from 'lodash';
import React from 'react';
import PropTypes from 'prop-types';
import GameTile from './GameTile';
import GameStatusMessage from './GameStatusMessage';
import GameMoveCollection from '../../utils/GameMoveCollection';
import { GAME_SHAPE, GAME_CONSTANTS } from '../../utils/Constants';

class GameBoard extends React.Component
{
    constructor(props)
    {
        super(props);

        this.state = {
            gameMoves: new GameMoveCollection()
        };
    }

    static getDerivedStateFromProps(props, state)
    {
        const moves = _.get(props, 'game.moves', []);

        return {
            gameMoves: new GameMoveCollection(moves),
        };
    }

    createGameStatusMessage()
    {
        return (
            <div className="columns">
                <div className="column">
                    <GameStatusMessage game={this.props.game}/>
                </div>
            </div>
        );
    }

    createGameTiles()
    {
        if (! this.props.game) {
            return;
        }

        const rows = _.times(
            GAME_CONSTANTS.BOARD_SIZE,
            yPosition => {
                const columns = _.times(
                    GAME_CONSTANTS.BOARD_SIZE,
                    xPosition => {
                        const gameMoveInTile = this.state.gameMoves.getGameMoveInPosition(xPosition, yPosition);
                        const isLatestMove = this.state.gameMoves.isLastGameMove(gameMoveInTile);

                        return (
                            <div className="level" key={"column_" + xPosition}>
                                <GameTile
                                    x={xPosition}
                                    y={yPosition}
                                    move={gameMoveInTile}
                                    players={this.props.game.players}
                                    makeMove={(x, y) => this.props.makeMove(x, y)}
                                    undoMove={(x, y) => this.props.undoMove(x, y)}
                                    isLatestMove={isLatestMove}
                                />
                            </div>
                        );
                    });

                return (
                    <div key={'row_' + yPosition} className="column">
                        {columns}
                    </div>
                );
        });

        return (
            <div className="columns">
                {rows}
            </div>
        );
    }

    render()
    {
        return (
            <section className="section">
                <div className="container">
                    {this.createGameStatusMessage()}
                    {this.createGameTiles()}
                </div>
            </section>
        );
    }
}

GameBoard.propTypes = {
    game: GAME_SHAPE,
    makeMove: PropTypes.func.isRequired,
    undoMove: PropTypes.func.isRequired,
};

export default GameBoard;