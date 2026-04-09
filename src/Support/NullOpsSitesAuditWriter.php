<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;

final class NullOpsSitesAuditWriter implements OpsSitesAuditWriter
{
    public function record(object $event): void {}
}
