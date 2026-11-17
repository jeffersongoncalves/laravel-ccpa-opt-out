<?php

namespace JeffersonGoncalves\CcpaOptOut;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutAction;
use JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight;
use JeffersonGoncalves\CcpaOptOut\Events\OptOutRecorded;
use JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord;

/**
 * A subject is an Eloquent model (a consumer with an account) or a string anonymous id (a browser).
 */
class OptOutManager
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function optOut(Model|string $subject, OptOutRight $right, ?string $source = null, array $metadata = []): OptOutRecord
    {
        return $this->record($subject, $right, OptOutAction::OptedOut, $source, $metadata);
    }

    /**
     * The consumer opts back in. Only ask for it after the reask_after period (§1798.135(c)(4)).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function optIn(Model|string $subject, OptOutRight $right, ?string $source = null, array $metadata = []): OptOutRecord
    {
        return $this->record($subject, $right, OptOutAction::OptedIn, $source, $metadata);
    }

    public function hasOptedOut(Model|string|null $subject, OptOutRight $right): bool
    {
        return $subject !== null && $this->latest($subject, $right)?->action === OptOutAction::OptedOut;
    }

    /** Whether you may sell or share this consumer's personal information. */
    public function allowsSaleOrSharing(Model|string|null $subject): bool
    {
        return ! $this->hasOptedOut($subject, OptOutRight::SaleSharing);
    }

    /** Whether you may use sensitive personal information beyond the purposes of §1798.121(a). */
    public function allowsSensitiveUse(Model|string|null $subject): bool
    {
        return ! $this->hasOptedOut($subject, OptOutRight::LimitSensitive);
    }

    /**
     * Whether you may ask the consumer to opt back in: never asked before, or the opt-out is
     * older than reask_after (12 months).
     */
    public function canAskToOptIn(Model|string $subject, OptOutRight $right): bool
    {
        $latest = $this->latest($subject, $right);

        if ($latest === null || $latest->action === OptOutAction::OptedIn) {
            return true;
        }

        return $latest->created_at->copy()
            ->add(CarbonInterval::make((string) config('ccpa-opt-out.reask_after', '12 months')))
            ->isPast();
    }

    public function latest(Model|string $subject, OptOutRight $right): ?OptOutRecord
    {
        return $this->query($subject)->where('right', $right->value)->latest('id')->first();
    }

    /**
     * @return Collection<int, OptOutRecord>
     */
    public function history(Model|string $subject, ?OptOutRight $right = null): Collection
    {
        return $this->query($subject)
            ->when($right !== null, fn (Builder $query) => $query->where('right', $right?->value))
            ->oldest('id')
            ->get();
    }

    /**
     * @return Builder<OptOutRecord>
     */
    public function query(Model|string $subject): Builder
    {
        $query = OptOutRecord::query();

        if (is_string($subject)) {
            return $query->where('anonymous_id', $subject);
        }

        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey());
    }

    /**
     * Carry a browser's choices over to the account it logs into. Append-only: for each right,
     * the browser's latest choice is copied as a new record (source "claimed") unless the account
     * already decided after it.
     *
     * @return Collection<int, OptOutRecord> the records created
     */
    public function claim(string $anonymousId, Model $subject): Collection
    {
        $claimed = new Collection;

        foreach ($this->query($anonymousId)->orderByDesc('id')->get()->unique(fn (OptOutRecord $r): string => $r->right->value) as $record) {
            $own = $this->latest($subject, $record->right);
            $ownOrder = $own === null ? null
                : ($own->source === 'claimed' ? (int) ($own->metadata['claimed_record_id'] ?? $own->getKey()) : (int) $own->getKey());

            if ($ownOrder !== null && $ownOrder >= (int) $record->getKey()) {
                continue;
            }

            $claimed->push($this->record($subject, $record->right, $record->action, 'claimed', [
                'anonymous_id' => $anonymousId,
                'claimed_record_id' => $record->getKey(),
            ], [
                'ip_address' => $record->getAttribute('ip_address'),
                'user_agent' => $record->getAttribute('user_agent'),
            ]));
        }

        return $claimed;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $attributes
     */
    protected function record(Model|string $subject, OptOutRight $right, OptOutAction $action, ?string $source, array $metadata, array $attributes = []): OptOutRecord
    {
        $request = config('ccpa-opt-out.store_request', true) ? request() : null;

        $record = OptOutRecord::create([
            ...(is_string($subject)
                ? ['anonymous_id' => $subject]
                : ['subject_type' => $subject->getMorphClass(), 'subject_id' => (string) $subject->getKey()]),
            'right' => $right,
            'action' => $action,
            'source' => $source,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
            ...$attributes,
        ]);

        event(new OptOutRecorded($record));

        return $record;
    }
}
