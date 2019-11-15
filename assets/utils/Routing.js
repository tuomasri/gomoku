import _ from 'lodash';

const joinRouteParts = (...args) => _.reduce(
    args,
    (url, urlPart) => url + "/" + urlPart,
    ""
);

const ROUTE_API_PREFIX  = 'api';
const ROUTE_GAME        = 'game';
const ROUTE_GAME_MOVE   = 'moves';

const Routing = {
    API: {
        game: {
            create: () => joinRouteParts(ROUTE_API_PREFIX, ROUTE_GAME),
        },
        gameMove: {
            create: gameId => joinRouteParts(
                ROUTE_API_PREFIX,
                ROUTE_GAME,
                gameId,
                ROUTE_GAME_MOVE
            ),
            delete: (gameId, moveId) => joinRouteParts(
                ROUTE_API_PREFIX,
                ROUTE_GAME,
                gameId,
                ROUTE_GAME_MOVE,
                moveId
            )
        }
    },
};

export default Routing;