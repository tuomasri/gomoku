import React from 'react';
import PropTypes from 'prop-types';
import { GAME_SHAPE,isTerminatedGame } from '../../utils/Constants';

class NewGame extends React.Component
{
    constructor(props)
    {
        super(props);
    }

    createNewGameButton()
    {
        return (
            <a
                onClick={() => this.props.startGame()}
                className="button is-success is-fullwidth"
            >
                Uusi peli
            </a>
        );
    }

    render()
    {
        if (this.props.game && ! isTerminatedGame(this.props.game)) {
            return null;
        }

        return (
            <section className="hero">
                <div className="hero-body">
                    <div className="container">
                        <div className="columns">
                            <div className="column is-half">
                                <h1 className="title">Gomoku</h1>
                            </div>
                            <div className="column is-half">
                                {this.createNewGameButton()}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        );
    }
}

NewGame.propTypes = {
    game: GAME_SHAPE,
    startGame: PropTypes.func.isRequired,
};

export default NewGame;
