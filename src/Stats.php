<?php

namespace ErnestDefoe\Mural;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;

/**
 * Numbers for the stats panel, cached for half a minute per season: one
 * busy canvas with a hundred viewers is still one count every 30 seconds.
 */
class Stats
{
    private const TTL = 30;

    public function __construct(private readonly ConnectionInterface $db, private readonly Cache $cache, private readonly Presenter $presenter)
    {
    }

    /** @return array<string, mixed> */
    public function for(Season $season, int $top = 10): array
    {
        $key = 'ernestdefoe-mural.stats.'.$season->id.'.'.$season->epoch.'.'.$top;

        return $this->cache->remember($key, $season->isOpen() ? self::TTL : 3600, fn () => $this->compute($season, $top));
    }

    /** @return array<string, mixed> */
    private function compute(Season $season, int $top): array
    {
        $now = Carbon::now();
        $since = $now->copy()->subHours(23)->startOfHour();
        $hours = array_fill(0, 24, 0);
        if ($season->isOpen()) {
            foreach (Placement::query()->where('season_id', $season->id)->where('created_at', '>=', $since)->toBase()->select('created_at')->cursor() as $row) {
                $index = (int) floor(($this->timestamp($row->created_at) - $since->getTimestamp()) / 3600);
                if ($index >= 0 && $index < 24) {
                    $hours[$index]++;
                }
            }
        }

        $leaders = $this->db->table('mural_painters')->where('season_id', $season->id)->where('tiles', '>', 0)
            ->orderByDesc('tiles')->orderBy('last_at')->limit($top)->get(['user_id', 'tiles']);
        $users = User::query()->whereIn('id', $leaders->pluck('user_id'))->get()->keyBy('id');

        return [
            'tiles' => $season->placements_count,
            'painters' => $season->painters_count,
            'last24' => array_sum($hours),
            'hours' => $hours,
            'hoursFrom' => $since->toIso8601String(),
            'active' => $season->isOpen()
                ? $this->db->table('mural_painters')->where('season_id', $season->id)->where('last_at', '>=', $now->copy()->subMinutes(15))->count()
                : 0,
            'top' => $leaders->filter(fn ($row) => $users->has($row->user_id))->map(fn ($row) => [
                'user' => $this->presenter->user($users->get($row->user_id)),
                'tiles' => (int) $row->tiles,
            ])->values()->all(),
        ];
    }

    private function timestamp(mixed $value): int
    {
        return $value instanceof \DateTimeInterface ? $value->getTimestamp() : (int) strtotime((string) $value.' UTC');
    }

    /**
     * The top painters of several seasons in one query (a window per season).
     *
     * @param list<int> $seasonIds
     * @return array<int, list<array{user: array<string, mixed>|null, tiles: int}>>
     */
    public function leaders(array $seasonIds, int $top): array
    {
        if (! $seasonIds) {
            return [];
        }
        $ranked = $this->db->table('mural_painters')->whereIn('season_id', $seasonIds)->where('tiles', '>', 0)
            ->select(['season_id', 'user_id', 'tiles'])
            ->selectRaw('row_number() over (partition by season_id order by tiles desc, last_at asc) as place');
        $rows = $this->db->query()->fromSub($ranked, 'ranked')->where('place', '<=', $top)->orderBy('season_id')->orderBy('place')->get();
        $users = User::query()->whereIn('id', $rows->pluck('user_id')->unique())->get()->keyBy('id');

        $leaders = [];
        foreach ($rows as $row) {
            if ($users->has($row->user_id)) {
                $leaders[(int) $row->season_id][] = ['user' => $this->presenter->user($users->get($row->user_id)), 'tiles' => (int) $row->tiles];
            }
        }

        return $leaders;
    }

    /** A member's own line: their tiles this season, their place and when they may paint again. @return array{tiles: int, rank: int|null, nextAt: string|null} */
    public function mine(Season $season, User $user): array
    {
        $row = $this->db->table('mural_painters')->where('season_id', $season->id)->where('user_id', $user->id)->first(['tiles', 'next_at']);
        $tiles = (int) ($row->tiles ?? 0);
        $next = $row && $row->next_at ? Carbon::parse($row->next_at) : null;

        return [
            'tiles' => $tiles,
            'nextAt' => $next && $next->isFuture() ? $next->toIso8601String() : null,
            'rank' => $tiles > 0 ? $this->db->table('mural_painters')->where('season_id', $season->id)->where('tiles', '>', $tiles)->count() + 1 : null,
        ];
    }
}
