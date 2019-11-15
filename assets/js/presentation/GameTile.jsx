import _ from 'lodash';
import moment from 'moment';
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { PLAYER_SHAPE, MOVE_SHAPE, GAME_CONSTANTS } from '../../utils/Constants';

class GameTile extends React.Component
{
    constructor(props)
    {
        super(props);
    }

    isMoveUndoable()
    {
        const dateCreated = moment(this.props.move.dateCreated, 'YYYY-MM-DD hh:mm:ss');

        return moment().diff(dateCreated, 'seconds') < GAME_CONSTANTS.MOVE_UNDO_THRESHOLD;
    }

    getPlayerColor()
    {
        const playerId = this.props.move.playerId;
        const player = _.find(this.props.players, player => playerId === player.id);

        return player.color;
    }

    createEmptyGameTile()
    {
        return (
            <button
                className="bg-grey hover:bg-grey-darker text-white font-bold py-4 px-4
                           rounded m-3 border border-1 border-solid border-grey"
                onClick={() => this.props.makeMove(this.props.x, this.props.y)}
            >
            </button>
        );
    }

    createOccupiedGameTile()
    {
        const playerColor = this.getPlayerColor();

        const gametileClasses = classNames({
            'rounded-full': true,
            'shadow-md': true,
            'font-bold py-4 px-4 rounded m-3': true,
            'bg-black': playerColor === GAME_CONSTANTS.PLAYER_COLOR_BLACK,
            'bg-grey-lightest': playerColor === GAME_CONSTANTS.PLAYER_COLOR_WHITE,
            'border border-1 border-solid border-black': true,
            'opacity-50': this.props.isTerminatedGame && ! this.props.move.isWinningMove,
            'opacity-100': this.props.isTerminatedGame && this.props.move.isWinningMove,
        });
        const isDisabled = ! this.props.isLatestMove || this.props.move.isWinningMove;

        return isDisabled
            ? <button className={gametileClasses}></button>
            : (<button
                    className={gametileClasses}
                    onClick={() => this.isMoveUndoable()
                        ? this.props.undoMove(this.props.x, this.props.y)
                        : alert("Siirtoa ei voi enää perua")}
                >
                </button>
            );
    }

    render()
    {
        return this.props.move ? this.createOccupiedGameTile() : this.createEmptyGameTile();
    }
}

GameTile.propTypes = {
    x: PropTypes.number.isRequired,
    y: PropTypes.number.isRequired,
    makeMove: PropTypes.func.isRequired,
    undoMove: PropTypes.func.isRequired,
    isLatestMove: PropTypes.bool.isRequired,
    isTerminatedGame: PropTypes.bool.isRequired,
    move: MOVE_SHAPE,
    players: PropTypes.arrayOf(PLAYER_SHAPE),
};

export default GameTile;