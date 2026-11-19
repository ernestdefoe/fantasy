<?php

namespace ErnestDefoe\Fantasy\Api\Controller;

use ErnestDefoe\Fantasy\Franchise;
use ErnestDefoe\Fantasy\League;
use Flarum\User\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

/** The league a request names, and the actor's franchise in it. Needs $this->translator. */
trait LeagueRequest
{
    protected function league(ServerRequestInterface $request): ?League
    {
        $slug = (string) ($request->getQueryParams()['slug'] ?? '');

        return $slug === '' ? null : League::query()->where('slug', $slug)->first();
    }

    /** Joining, drafting and lineups: a member who holds `fantasy.play` (not suspended, email confirmed). */
    protected function assertPlayer(User $actor): void
    {
        $actor->assertRegistered();
        $actor->assertCan('fantasy.play');
    }

    protected function franchiseOf(League $league, int $userId): ?Franchise
    {
        return $userId < 1 ? null : Franchise::query()->where('league_id', $league->id)->where('user_id', $userId)->first();
    }

    protected function problem(string $key, int $status = 422): JsonResponse
    {
        return new JsonResponse(['errors' => [['status' => (string) $status, 'code' => $key,
            'detail' => $this->translator->trans('ernestdefoe-fantasy.api.'.$key)]]], $status);
    }
}
