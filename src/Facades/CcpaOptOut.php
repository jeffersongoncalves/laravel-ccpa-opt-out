<?php

namespace JeffersonGoncalves\CcpaOptOut\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\CcpaOptOut\OptOutManager;

/**
 * @method static \JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord optOut(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right, ?string $source = null, array<string, mixed> $metadata = [])
 * @method static \JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord optIn(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right, ?string $source = null, array<string, mixed> $metadata = [])
 * @method static bool hasOptedOut(\Illuminate\Database\Eloquent\Model|string|null $subject, \JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right)
 * @method static bool allowsSaleOrSharing(\Illuminate\Database\Eloquent\Model|string|null $subject)
 * @method static bool allowsSensitiveUse(\Illuminate\Database\Eloquent\Model|string|null $subject)
 * @method static bool canAskToOptIn(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right)
 * @method static \JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord|null latest(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord> history(\Illuminate\Database\Eloquent\Model|string $subject, ?\JeffersonGoncalves\CcpaOptOut\Enums\OptOutRight $right = null)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\CcpaOptOut\Models\OptOutRecord> claim(string $anonymousId, \Illuminate\Database\Eloquent\Model $subject)
 *
 * @see OptOutManager
 */
class CcpaOptOut extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OptOutManager::class;
    }
}
