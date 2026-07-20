<?php

namespace App\Services;

use App\Models\Subscribers;
use Illuminate\Support\Facades\DB;

class SubscriberSentTimeUpdater
{
    /**
     * @param  array<int, string>  $subscriberUpdates
     */
    public function update(array $subscriberUpdates): void
    {
        if ($subscriberUpdates === []) {
            return;
        }

        $ids = array_map('intval', array_keys($subscriberUpdates));
        $caseSql = 'CASE id ';
        $bindings = [];

        foreach ($subscriberUpdates as $id => $timestamp) {
            $caseSql .= 'WHEN ? THEN ? ';
            $bindings[] = (int) $id;
            $bindings[] = $timestamp;
        }

        $caseSql .= 'END';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids);

        DB::statement(
            'UPDATE '.Subscribers::getTableName()
            ." SET timeSent = {$caseSql} WHERE id IN ({$placeholders})",
            $bindings
        );
    }
}
