<?php

namespace ErnestDefoe\Fantasy\Service;

use ErnestDefoe\Fantasy\Franchise;
use ErnestDefoe\Fantasy\League;
use ErnestDefoe\Fantasy\Service\Sports\Scoring as SportScoring;
use Illuminate\Support\Str;

/** Creating and joining leagues. */
class Leagues
{
    public function create(array $input, int $userId): League
    {
        $name = trim((string) ($input['name'] ?? ''));
        $roster = min(25, max(1, (int) ($input['roster_size'] ?? 8)));
        $seasonId = (int) ($input['season_id'] ?? 0);

        $league = new League();
        $league->fill([
            'name' => Str::limit($name, 189, ''),
            'slug' => $this->uniqueSlug($name),
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'season_id' => $seasonId,
            'commissioner_id' => $userId,
            'status' => 'setup',
            'max_franchises' => min(32, max(2, (int) ($input['max_franchises'] ?? 12))),
            'roster_size' => $roster,
            /*
             * 🚨 Never more than the roster it is chosen from, or every lineup
             * screen is a form that cannot be completed.
             */
            'starters' => min($roster, max(1, (int) ($input['starters'] ?? 4))),
        ]);

        /*
         * 🚨 The scoring starts from the SPORT this season is played in. The
         * rules travel between sports; the numbers do not. A gridiron team
         * scores about thirty points a game, a basketball team a hundred and
         * ten, a football team one and a half — so 1.0 per point is a sensible
         * week in one, an absurd 110-point week in another and rounding error
         * in the third.
         *
         * Anything the form actually sent still wins; this only decides what a
         * commissioner starts from.
         */
        $league->season_id = $seasonId;
        $defaults = SportScoring::defaultsFor($league->competition());

        foreach (League::SCORING as $column) {
            $league->{$column} = array_key_exists($column, $input)
                ? (float) $input[$column]
                : ($defaults[$column] ?? 0.0);
        }

        $league->save();

        $this->join($league, $userId, '');

        return $league;
    }

    public function join(League $league, int $userId, string $name): Franchise
    {
        $existing = Franchise::query()
            ->where('league_id', $league->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Franchise::query()->create([
            'league_id' => $league->id,
            'user_id' => $userId,
            'name' => Str::limit(trim($name) ?: 'Franchise', 119, ''),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'league';
        $slug = $base;
        $n = 2;

        while (League::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return Str::limit($slug, 189, '');
    }
}
