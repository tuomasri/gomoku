import React, { Component } from 'react';
import _ from 'lodash';
import PropTypes from 'prop-types';
import constants from './constants';
import classNames from 'classnames';
import moment from 'moment';

class GameTile extends Component
{
    constructor(props) {
        super(props);

        this.state = {
            isUndoable: false,
            undoInterval: null,
        };
    }

    componentWillReceiveProps(nextProps) {
        let move = nextProps.move;
        if (! move) {
            return;
        }

        let isLatestMove = nextProps.isLatestMove;
        let dateCreated = moment(move.dateCreated, 'YYYY-MM-DD hh:mm:ss');
        let isUndoable = isLatestMove && moment().diff(dateCreated, 'seconds') <= constants.MOVE_UNDO_THRESHOLD;
        let undoInterval = isUndoable
            ? setInterval(() => this.revokeUndoableStatus(), constants.MOVE_UNDO_THRESHOLD * 1000)
            : null;

        this.setState({isUndoable, undoInterval});
    }

    revokeUndoableStatus() {
        if (this.state.undoInterval) {
            clearInterval(this.state.undoInterval);

            this.setState({
                isUndoable: false,
                undoInterval: null,
            });
        }
    }

    getTileColor() {
        let move = this.props.move;
        if (! move) {
            return null;
        }

        let playerId = move.playerId;
        let player = _.find(this.props.players, (player) => playerId === player.id);

        if (! player) {
            throw new Error(`Unable to find player #${playerId}`);
        }

        return player.color;
    }

    renderBlankTile() {
        return (
            <a
                className="button is-outlined"
                onClick={() => this.props.handleMove(this.props.x, this.props.y)}
            >
            </a>
        );
    }

    render() {
        let myColor = this.getTileColor();
        if (! myColor) {
            return this.renderBlankTile();
        }

        let text = myColor === constants.PLAYER_COLOR_BLACK ? 'M' : 'V';
        let gametileClasses = classNames({
            'button': true,
            'is-black': myColor === constants.PLAYER_COLOR_BLACK,
            'is-outlined': myColor === constants.PLAYER_COLOR_WHITE,
            'is-loading is-medium': this.props.move.isWinningMove,
        });
        let clickHandler = this.state.isUndoable
            ? () => { this.props.undoLatestMove() }
            : () => { alert("Siirtoa ei voi enää perua");};

        return (
            <a disabled={! this.props.isLatestMove || this.props.move.isWinningMove}
               className={gametileClasses}
               onClick={() => clickHandler()}
            >
                <h1>{text}</h1>
            </a>
        );
    }
}

GameTile.propTypes = {
    x: PropTypes.number,
    y: PropTypes.number,
    move: constants.moveShape,
    isLatestMove: PropTypes.bool,
    handleMove: PropTypes.func,
    undoLatestMove: PropTypes.func,
    players: PropTypes.arrayOf(constants.playerShape),
};

export default GameTile;