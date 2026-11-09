<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ordering with sparse integer positions.
 *
 * Items sit GAP apart, so placing one between two neighbours takes the midpoint and writes a single row. When two
 * neighbours have no room left between them, the siblings are renumbered first. Callers run this inside a
 * transaction that holds a lock on the board, and name the neighbour to land before (not an index), so a move
 * stays correct even when other moves happened since the client last loaded the board.
 */
final class Positions
{
    public const int GAP = 1024;

    /**
     * The position that puts an item right before the sibling $beforeId, or after the last sibling when null.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $siblings  the item's future siblings, without the item itself
     */
    public static function before(Builder $siblings, ?int $beforeId): int
    {
        if ($beforeId === null) {
            return self::last($siblings) + self::GAP;
        }

        $next = self::int((clone $siblings)->whereKey($beforeId)->value('position'));
        $previous = self::int((clone $siblings)->where('position', '<', $next)->max('position'));

        if ($next - $previous > 1) {
            return intdiv($previous + $next, 2);
        }

        self::reindex($siblings);

        return self::before($siblings, $beforeId);
    }

    /**
     * The highest position among the siblings, 0 when there are none.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $siblings
     */
    public static function last(Builder $siblings): int
    {
        return self::int((clone $siblings)->max('position'));
    }

    /**
     * Renumbers the siblings GAP apart, keeping their order.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $siblings
     */
    public static function reindex(Builder $siblings): void
    {
        $ids = (clone $siblings)->orderBy('position')->orderBy('id')->pluck('id');

        foreach ($ids->values() as $index => $id) {
            (clone $siblings)->whereKey($id)->update(['position' => ($index + 1) * self::GAP]);
        }
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
