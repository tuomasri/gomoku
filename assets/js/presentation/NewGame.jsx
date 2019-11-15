import React from 'react';
import PropTypes from 'prop-types';
import { GAME_CONSTANTS, GAME_SHAPE, isTerminatedGame } from '../../utils/Constants';
import Modal from "./Modal";

class NewGame extends React.Component
{
    constructor(props)
    {
        super(props);

        this.state = {
            confirmNewGameStart: false,
        };
    }

    static getDerivedStateFromProps(nextProps, prevState)
    {
        const confirmNewGameStart = (
            nextProps.game &&
            isTerminatedGame(nextProps.game) &&
            ! prevState.confirmNewGameStart
        );

        return {confirmNewGameStart};
    }

    createNewGameButton()
    {
        return (
            <button
                onClick={() => this.props.startGame()}
                className="bg-blue-500 hover:bg-blue-dark py-2 px-2 text-white font-bold rounded"
            >
                Uusi peli
            </button>
        );
    }

    createBodyText()
    {
        const winnerColor = _.get(this.props.game, 'winner.color', null);

        if (! winnerColor) {
            return "..tasapeliin.";
        }

        return winnerColor === GAME_CONSTANTS.PLAYER_COLOR_BLACK
            ? "..musta pelaaja voitti."
            : "..valkoinen pelaaja voitti.";
    }

    render()
    {
        if (! this.props.game) {
            return (
                <div className="flex h-screen items-center justify-center">
                    <div className="w-1/1">
                        {this.createNewGameButton()}
                    </div>
                </div>
            );
        }

        return (
            <Modal
                isOpen={this.state.confirmNewGameStart}
                headerText="Peli päättyi"
                bodyText={this.createBodyText()}
                successButton={this.createNewGameButton()}
            />
        );
    }
}

NewGame.propTypes = {
    game: GAME_SHAPE,
    startGame: PropTypes.func.isRequired,
};

export default NewGame;
