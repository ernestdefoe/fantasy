<?php

namespace ErnestDefoe\Fantasy\Api\Controller;

use ErnestDefoe\Fantasy\League;
use ErnestDefoe\Fantasy\Service\Sports\Scoring as SportScoring;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Every league on this forum, with its standings. */
class LeaguesController implements RequestHandlerInterface
{
    private const PER_PAGE = 50;

    /*
     * 🚨 The connection is injected, not reached for through the DB facade.
     * Flarum boots the container without setting the facade root, so
     * `DB::table(...)` throws — quietly, inside a controller, as a 500 with
     * nothing useful in it.
     */
    public function __construct(private ConnectionInterface $db, private TranslatorInterface $translator)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /*
         * 🚨 The franchise count and each league's sport arrive in two
         * queries for the whole list. They were three queries PER league — the
         * franchises and their members loaded only to be counted, and a season
         * lookup each — so the page cost grew with every league created.
         */
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('viewForum');

        // A page at a time, newest first; the page asks for the next one with `offset`.
        $offset = max(0, (int) ($request->getQueryParams()['offset'] ?? 0));
        $leagues = League::query()->withCount('franchises')->orderByDesc('id')
            ->skip($offset)->take(self::PER_PAGE + 1)->get();
        $more = $leagues->count() > self::PER_PAGE;
        $leagues = $leagues->take(self::PER_PAGE);
        $competitions = League::competitionsFor($leagues->pluck('season_id')->all());
        $out = [];

        foreach ($leagues as $league) {
            $out[] = [
                'id' => (int) $league->id,
                'name' => (string) $league->name,
                'slug' => (string) $league->slug,
                'description' => (string) $league->description,
                'status' => (string) $league->status,
                'sport' => SportScoring::sportOf($competitions[(int) $league->season_id] ?? ''),
                'franchises' => (int) $league->franchises_count,
                'maxFranchises' => (int) $league->max_franchises,
            ];
        }

        /*
         * 🚨 Seasons and the permission travel WITH the list, rather than
         * being two more requests the page has to make before it can draw a
         * button. The page needs all three to render once; fetching them
         * separately is three round trips and two intermediate states where
         * the button flickers into existence.
         */
        return new JsonResponse([
            'leagues' => $out,
            'more' => $more,
            'seasons' => $this->seasons(),
            'canCreate' => $actor->isGuest() ? false : (bool) $actor->can('fantasy.createLeague'),
        ]);
    }

    /** The seasons a league can be attached to, newest first. */
    private function seasons(): array
    {
        $rows = $this->db->table('picks_seasons')
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'name', 'year']);

        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: $this->translator->trans('ernestdefoe-fantasy.api.unnamed_season', ['year' => $row->year ?? $row->id])),
            ];
        }

        return $out;
    }
}
