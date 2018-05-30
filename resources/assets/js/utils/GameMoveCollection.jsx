import _ from 'lodash';

class GameMoveCollection
{
    static createKey(x, y)
    {
        return '' + x + '.' + y;
    }

    constructor(gameMoves = [])
    {
        this.gameMoves = _.keyBy(gameMoves, move => GameMoveCollection.createKey(move.x, move.y));
        this.lastGameMove = _.last(_.values(gameMoves)) || null;
    }

    isLastGameMove(gameMove)
    {
        return this.lastGameMove === gameMove;
    }

    getGameMoveInPosition(x, y)
    {
        return _.get(this.gameMoves, GameMoveCollection.createKey(x, y));
    }
}

export default GameMoveCollection;