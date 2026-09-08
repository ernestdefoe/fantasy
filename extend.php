<?php

namespace ErnestDefoe\Fantasy;

use ErnestDefoe\Fantasy\Api\Controller\LeagueController;
use ErnestDefoe\Fantasy\Api\Controller\LeaguesController;
use ErnestDefoe\Fantasy\Console\ScoreCommand;
use Flarum\Extend;

return [
    /*
     * 🚨 Registered on the FRONTEND as well as the API. Without the frontend
     * route a visitor opening /fantasy directly — from a link, a bookmark, a
     * search result — gets the discussion list, and only in-app navigation
     * works.
     */
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/resources/less/forum.less')
        ->route('/fantasy', 'fantasy.index')
        ->route('/fantasy/{slug}', 'fantasy.league'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Routes('api'))
        ->get('/fantasy/leagues', 'fantasy.api.leagues', LeaguesController::class)
        ->get('/fantasy/league', 'fantasy.api.league', LeagueController::class),

    (new Extend\Console())
        ->command(ScoreCommand::class)
        /*
         * 🚨 Hourly, not minutely. Scoring reads FINISHED games only, so there
         * is nothing to do between one game ending and the next — and a
         * franchise total that updates within the hour is indistinguishable
         * from one that updates within the minute to everyone except the
         * database.
         */
        ->schedule(ScoreCommand::class, function ($event) {
            $event->hourly()->withoutOverlapping();
        }),
];
