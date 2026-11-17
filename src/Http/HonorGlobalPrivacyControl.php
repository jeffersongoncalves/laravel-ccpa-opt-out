<?php

namespace JeffersonGoncalves\CcpaOptOut\Http;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight;
use JeffersonGoncalves\CcpaOptOut\OptOutManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Global Privacy Control signal is a valid request to opt out of sale and sharing
 * (CCPA Regulations §7025). Records it once for the user, or the browser, sending it.
 */
class HonorGlobalPrivacyControl
{
    public function __construct(protected OptOutManager $optOuts) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('ccpa-opt-out.honor_gpc', true) && $request->header('Sec-GPC') === '1') {
            $subject = Visitor::resolve($request);

            if (! $this->optOuts->hasOptedOut($subject, OptOutRight::SaleSharing)) {
                $this->optOuts->optOut($subject, OptOutRight::SaleSharing, 'gpc');
            }

            $request->attributes->set('ccpa_gpc', true);
        }

        return $next($request);
    }
}
