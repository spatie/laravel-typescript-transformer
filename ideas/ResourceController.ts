import { createAction } from './support';

type TeamParams = {
    team: string | number;
};

/**
 * App\Teams\SettingsController
 *
 * @see app/Http/Controllers/Teams/SettingsController.php
 */
const ResourceController = {
    index: createAction<TeamParams>('teams/{team}/settings', 'get'),

    update: createAction<TeamParams>('teams/{team}/settings', 'patch'),
} as const;

namespace ResourceController {
    export namespace index {
        export type Request = {
            id: number;
        };

        export type Response = {
            id: number;
        };
    }

    export namespace update {
        export type Request = {
            id: number;
        };

        export type Response = {
            id: number;
        };
    }
}

export { ResourceController };
