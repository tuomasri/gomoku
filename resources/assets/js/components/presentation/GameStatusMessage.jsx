import React from 'react';
import { GAME_SHAPE, GAME_CONSTANTS, isTerminatedGame } from '../../utils/Constants';

class GameStatusMessage extends React.Component
{
    constructor(props)
    {
        super(props);
    }

    getWinningText()
    {
        const winnerColor = _.get(this.props.game, 'winner.color', null);

        if (! winnerColor) {
            return "tasapeli";
        }

        return winnerColor === GAME_CONSTANTS.PLAYER_COLOR_BLACK
            ? "musta pelaaja voitti"
            : "valkoinen pelaaja voitti";
    }

    render()
    {
        if (! isTerminatedGame(this.props.game)) {
            return null;
        }

        const message = `Peli päättyi: ${this.getWinningText()}`;

        return (
            <article className="message is-info">
                <div className="message-header">
                    <p>{message}</p>
                </div>
            </article>
        );
    }
}

GameStatusMessage.propTypes = {
    game: GAME_SHAPE,
};

export default GameStatusMessage;
