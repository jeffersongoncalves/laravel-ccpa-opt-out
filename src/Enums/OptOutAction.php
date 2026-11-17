<?php

namespace JeffersonGoncalves\CcpaOptOut\Enums;

enum OptOutAction: string
{
    case OptedOut = 'opted_out';
    case OptedIn = 'opted_in';
}
