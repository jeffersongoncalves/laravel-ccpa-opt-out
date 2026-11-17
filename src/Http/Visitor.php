<?php

namespace JeffersonGoncalves\CcpaOptOut\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Who is asking: the logged-in user, or a browser identified by a random id in an encrypted cookie.
 */
class Visitor
{
    public static function resolve(Request $request): Model|string
    {
        $user = $request->user();

        if ($user instanceof Model) {
            return $user;
        }

        $name = (string) config('ccpa-opt-out.route.cookie', 'ccpa_opt_out_id');
        $id = $request->cookie($name);

        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid();
            Cookie::queue($name, $id, 60 * 24 * 400);
        }

        return $id;
    }
}
