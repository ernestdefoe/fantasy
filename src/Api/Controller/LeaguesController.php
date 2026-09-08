<?php

namespace ErnestDefoe\Fantasy\Api\Controller;

use ErnestDefoe\Fantasy\Franchise;
use ErnestDefoe\Fantasy\League;
use ErnestDefoe\Fantasy\Service\Sports\Scoring as SportScoring;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Every league on this forum, with its standings. */
class LeaguesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $leagues = League::query()->orderByDesc('id')->get();
        $out = [];

        foreach ($leagues as $league) {
            $franchises = Franchise::query()
                ->where('league_id', $league->id)
                ->with('user')
                ->get();

            $out[] = [
                'id' => (int) $league->id,
                'name' => (string) $league->name,
                'slug' => (string) $league->slug,
                'description' => (string) $league->description,
                'status' => (string) $league->status,
                'sport' => SportScoring::sportOf($league->competition()),
                'franchises' => $franchises->count(),
                'maxFranchises' => (int) $league->max_franchises,
            ];
        }

        return new JsonResponse(['leagues' => $out]);
    }
}
