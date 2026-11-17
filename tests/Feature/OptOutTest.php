<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutAction;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight;
use JeffersonGoncalves\CcpaOptOut\Events\OptOutRecorded;
use JeffersonGoncalves\CcpaOptOut\Facades\CcpaOptOut;
use JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord;
use JeffersonGoncalves\CcpaOptOut\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Mary']);
});

it('opts out and back in, keeping the full history', function () {
    Event::fake([OptOutRecorded::class]);

    expect($this->user->hasOptedOut(OptOutRight::SaleSharing))->toBeFalse();

    $this->user->optOut(OptOutRight::SaleSharing, 'form');

    expect(CcpaOptOut::allowsSaleOrSharing($this->user))->toBeFalse()
        ->and(CcpaOptOut::allowsSensitiveUse($this->user))->toBeTrue();

    $this->user->optIn(OptOutRight::SaleSharing, 'form');

    expect(CcpaOptOut::allowsSaleOrSharing($this->user))->toBeTrue()
        ->and(CcpaOptOut::history($this->user)->pluck('action')->all())->toBe([OptOutAction::OptedOut, OptOutAction::OptedIn]);

    Event::assertDispatchedTimes(OptOutRecorded::class, 2);
});

it('waits 12 months before asking to opt back in', function () {
    CcpaOptOut::optOut($this->user, OptOutRight::SaleSharing);

    expect(CcpaOptOut::canAskToOptIn($this->user, OptOutRight::SaleSharing))->toBeFalse();

    $this->travel(13)->months();

    expect(CcpaOptOut::canAskToOptIn($this->user, OptOutRight::SaleSharing))->toBeTrue()
        ->and(CcpaOptOut::canAskToOptIn($this->user, OptOutRight::LimitSensitive))->toBeTrue();
});

it('honors the Global Privacy Control signal once per browser', function () {
    Route::get('/page', fn () => 'ok')->middleware(['web', 'ccpa.gpc']);

    $response = $this->withHeader('Sec-GPC', '1')->get('/page')->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'ccpa_opt_out_id');

    expect(OptOutRecord::sole())->source->toBe('gpc')->right->toBe(OptOutRight::SaleSharing);

    $this->withHeader('Sec-GPC', '1')->withUnencryptedCookie('ccpa_opt_out_id', $cookie->getValue())->get('/page');
    $this->get('/page');

    expect(OptOutRecord::count())->toBe(1);
});

it('records the signal on the logged-in user', function () {
    Route::get('/page', fn () => 'ok')->middleware(['web', 'ccpa.gpc']);

    $this->actingAs($this->user)->withHeader('Sec-GPC', '1')->get('/page');

    expect(CcpaOptOut::hasOptedOut($this->user, OptOutRight::SaleSharing))->toBeTrue();
});

it('stores choices from the endpoint', function () {
    $this->actingAs($this->user)
        ->postJson('/ccpa/opt-out', ['right' => 'limit_sensitive', 'opted_out' => true])
        ->assertCreated()
        ->assertJson(['action' => 'opted_out']);

    expect(CcpaOptOut::allowsSensitiveUse($this->user))->toBeFalse();

    $this->postJson('/ccpa/opt-out', ['right' => 'nope', 'opted_out' => true])->assertUnprocessable();
});

it('claims a browser opt-out on login', function () {
    CcpaOptOut::optOut('browser-1', OptOutRight::SaleSharing, 'gpc');

    $this->withCookie('ccpa_opt_out_id', 'browser-1')->get('/');
    request()->cookies->set('ccpa_opt_out_id', 'browser-1');
    event(new Login('web', $this->user, false));

    expect(CcpaOptOut::latest($this->user, OptOutRight::SaleSharing))
        ->source->toBe('claimed')
        ->metadata->toMatchArray(['anonymous_id' => 'browser-1']);

    expect(CcpaOptOut::claim('browser-1', $this->user))->toBeEmpty();
});

it('hides sale tags from users who opted out or send GPC', function () {
    $view = '@ccpaSaleAllowed pixel @else none @endccpaSaleAllowed';
    $this->actingAs($this->user);

    expect(trim(Blade::render($view)))->toBe('pixel');

    $this->user->optOut(OptOutRight::SaleSharing);

    expect(trim(Blade::render($view)))->toBe('none');
});

it('is append-only but can be deidentified', function () {
    $record = CcpaOptOut::optOut($this->user, OptOutRight::SaleSharing);

    expect(fn () => $record->update(['action' => 'opted_in']))->toThrow(LogicException::class)
        ->and(fn () => $record->delete())->toThrow(LogicException::class);

    $fresh = $record->fresh();
    $fresh->anonymizePersonalData();

    expect($fresh->fresh()->ip_address)->toBeNull();
});
