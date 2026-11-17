<?php

namespace JeffersonGoncalves\CcpaOptOut\Events;

use JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord;

/**
 * A consumer opted out or back in. On an opt-out of sale/sharing, stop selling or sharing their
 * personal information and tell the third parties you sold or shared it to (CCPA Regulations §7026(f)).
 */
class OptOutRecorded
{
    public function __construct(public OptOutRecord $record) {}
}
