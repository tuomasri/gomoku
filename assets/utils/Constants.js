import PropTypes from 'prop-types';

const GAME_CONSTANTS = {
    BOARD_SIZE: 15,
    PLAYER_COLOR_BLACK: 1,
    PLAYER_COLOR_WHITE: 2,
    MOVE_UNDO_THRESHOLD: 5,
};

const MOVE_SHAPE = PropTypes.shape({
    id: PropTypes.number,
    x: PropTypes.number,
    y: PropTypes.number,
    dateCreated: PropTypes.string,
    isWinningMove: PropTypes.bool,
    playerId: PropTypes.number,
});

const PLAYER_SHAPE = PropTypes.shape({
    id: PropTypes.number,
    color: PropTypes.number,
});

const GAME_SHAPE = PropTypes.shape({
    id: PropTypes.number,
    moves: PropTypes.arrayOf(MOVE_SHAPE).isRequired,
    players: PropTypes.arrayOf(PLAYER_SHAPE).isRequired,
    winner: PropTypes.shape(PLAYER_SHAPE),
    isTerminated: PropTypes.bool.isRequired,
});

export {
    GAME_CONSTANTS,
    GAME_SHAPE,
    MOVE_SHAPE,
    PLAYER_SHAPE,
};