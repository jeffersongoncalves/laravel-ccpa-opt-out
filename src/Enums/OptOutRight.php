<?php

namespace JeffersonGoncalves\CcpaOptOut\Enums;

enum OptOutRight: string
{
    /** Right to opt out of the sale or sharing of personal information (Cal. Civ. Code §1798.120). */
    case SaleSharing = 'sale_sharing';

    /** Right to limit the use and disclosure of sensitive personal information (§1798.121). */
    case LimitSensitive = 'limit_sensitive';
}
