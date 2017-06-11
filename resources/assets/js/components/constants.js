import PropTypes from 'prop-types';

export default {
    BOARD_SIZE: 15,
    GAME_STARTED: 1,
    GAME_ONGOING: 2,
    GAME_TERMINATED: 3,
    PLAYER_COLOR_BLACK: 1,
    PLAYER_COLOR_WHITE: 2,
    MOVE_UNDO_THRESHOLD: 5,
    moveShape: PropTypes.shape({
        id: PropTypes.number,
        x: PropTypes.number,
        y: PropTypes.number,
        dateCreated: PropTypes.string,
        isWinningMove: PropTypes.bool,
        playerId: PropTypes.number,
    }),
    playerShape: PropTypes.shape({
        id: PropTypes.number,
        color: PropTypes.number,
    }),
};

