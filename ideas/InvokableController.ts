import {createInvokableAction} from './support';

/**
 * Front\HomeController
 *
 * @see app/Http/Controllers/Front/HomeController.php
 */
const InvokableController = createInvokableAction('/', 'get');


namespace InvokableController {
    export type Request = {
        id: number;
    };

    export type Response = {
        id: number;
    };
}

export {InvokableController};
