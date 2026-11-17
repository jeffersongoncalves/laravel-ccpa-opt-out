<?php

namespace JeffersonGoncalves\CcpaOptOut\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight;
use JeffersonGoncalves\CcpaOptOut\OptOutManager;

/** Endpoint for the "Do Not Sell or Share" and "Limit the Use of My Sensitive Personal Information" links. */
class OptOutController
{
    public function __invoke(Request $request, OptOutManager $optOuts): JsonResponse
    {
        $data = $request->validate([
            'right' => ['required', Rule::enum(OptOutRight::class)],
            'opted_out' => ['required', 'boolean'],
        ]);

        $subject = Visitor::resolve($request);
        $right = OptOutRight::from($data['right']);

        $record = $request->boolean('opted_out')
            ? $optOuts->optOut($subject, $right, 'form')
            : $optOuts->optIn($subject, $right, 'form');

        return response()->json(['right' => $right->value, 'action' => $record->action->value], 201);
    }
}
