<?php

namespace App\Enums;

/**
 * Sync state of a bank connection, as BuggCoin sees it (not Pluggy's item status).
 */
enum ConnectionStatus: string
{
    /** Registered, never synced. */
    case Pending = 'pending';

    /** A sync job is running. */
    case Syncing = 'syncing';

    /** Last sync finished without errors. */
    case Synced = 'synced';

    /** Last sync failed; see bank_connections.last_error. Nothing was deleted. */
    case Error = 'error';
}
