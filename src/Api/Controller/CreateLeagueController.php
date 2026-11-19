<?php

namespace ErnestDefoe\Fantasy\Api\Controller;

use ErnestDefoe\Fantasy\Service\Leagues;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Start a league.
 *
 * 🚨 The form does NOT ask for the franchise count, the roster size or the
 * lineup size, and that is deliberate rather than an omission.
 *
 * Those three are the forum's settings. Asking a commissioner for them at
 * creation time would mean every league carried whatever the form's own
 * defaults happened to be, and the admin screen would govern nothing — which
 * is precisely the state this extension was in before this controller existed:
 * three settings and no code path that could reach them.
 */
class CreateLeagueController implements RequestHandlerInterface
{
    use LeagueRequest;

    public function __construct(protected TranslatorInterface $translator, private Leagues $leagues, private ConnectionInterface $db)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        /*
         * 🚨 A permission, not a role check. Admins pass it for free — Flarum
         * short-circuits every check for them — so this is really asking "has
         * the operator let members do this", and out of the box the answer is
         * no without anything being broken.
         */
        $actor->assertCan('fantasy.createLeague');

        $body = (array) ($request->getParsedBody() ?? []);
        $name = trim((string) ($body['name'] ?? ''));

        $description = trim((string) ($body['description'] ?? ''));
        $seasonId = (int) ($body['seasonId'] ?? 0);

        if (($problem = $this->invalid($name, $description, $seasonId)) !== null) {
            return $this->problem($problem);
        }

        $league = $this->leagues->create([
            'name' => $name,
            'description' => $description,
            'season_id' => $seasonId,
        ], (int) $actor->id);

        return new JsonResponse([
            'league' => [
                'id' => (int) $league->id,
                'name' => (string) $league->name,
                'slug' => (string) $league->slug,
                'status' => (string) $league->status,
                'maxFranchises' => (int) $league->max_franchises,
                'rosterSize' => (int) $league->roster_size,
                'starters' => (int) $league->starters,
            ],
        ], 201);
    }

    /** Why this league can't be created, as a locale key, or null when it can. */
    private function invalid(string $name, string $description, int $seasonId): ?string
    {
        return match (true) {
            $name === '' => 'name_required',
            mb_strlen($name) > 189 => 'name_too_long',
            mb_strlen($description) > 2000 => 'description_too_long',
            // A league plays one Picks season; one that does not exist could never be drafted.
            $seasonId < 1, ! $this->db->table('picks_seasons')->where('id', $seasonId)->exists() => 'no_such_season',
            default => null,
        };
    }
}
