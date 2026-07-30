<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * Audit-flavoured wrapper around spatie's LogsActivity trait.
 *
 * Behaves exactly like spatie's trait (causer auto-capture, logOnly/logOnlyDirty
 * honoured via getActivitylogOptions) but additionally stamps the subject's
 * owner id into the activity properties, so a read API can scope entries by
 * owner without joining to the (polymorphic) subject row.
 *
 * Models exposing the owner key under a different column override
 * auditOwnerId().
 */
trait LogsAuditActivity
{
    use LogsActivity;

    /**
     * Resolve the owner id to stamp into the activity properties.
     *
     * Defaults to the model's `user_id`. Models keyed by a differently named
     * owner column override this.
     */
    public function auditOwnerId(): int|string|null
    {
        /** @var int|string|null $ownerId */
        $ownerId = $this->getAttribute('user_id');

        return $ownerId;
    }

    /**
     * Re-implements spatie's bootLogsActivity so we can attach the owner
     * `user_id` to every emitted activity's properties.
     */
    protected static function bootLogsActivity(): void
    {
        // Коллекция spatie объявлена как Collection<int|string, mixed>, поэтому
        // имя события приезжает нетипизированным — сужаем внутри замыкания,
        // а не в его сигнатуре (иначе не подходит по вариантности).
        static::eventsToBeRecorded()->each(function (mixed $rawEventName): void {
            if (! is_string($rawEventName)) {
                return;
            }

            $eventName = $rawEventName;

            if ($eventName === ActivityEvent::Updated->value) {
                static::updating(function (Model $model): void {
                    /** @var Model&self $model */
                    // Use the model's own newInstance() factory rather than
                    // `new static()`: the trait is mixed into many models with
                    // differing constructors, so PHPStan cannot prove a bare
                    // `new static()` is safe. newInstance() is the framework's
                    // sanctioned, correctly-typed way to spawn a sibling row.
                    $old = $model->newInstance()->setRawAttributes($model->getRawOriginal());
                    $model->oldAttributes = static::extractChanges($old);
                });
            }

            // registerModelEvent(), а не динамический `static::$eventName(...)`:
            // это штатное API Eloquent, оно принимает имя события ПАРАМЕТРОМ,
            // а не именем метода. Поведение то же, но вызов перестаёт быть
            // переменным — его снова может проверить статический анализ.
            static::registerModelEvent($eventName, function (Model $model) use ($eventName): void {
                /** @var Model&self $model */
                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $description = $model->getDescriptionForEvent($eventName);

                if ($description === '') {
                    return;
                }

                $changes = $model->buildChanges($eventName);

                if ($model->shouldSkipEmptyLog($changes)) {
                    return;
                }

                // buildChanges() у spatie объявлен как голый array, а
                // withChanges() ждёт array<string, mixed>. Нормализуем ключи
                // вместо inline-@var: разница видна в коде, а не в комментарии.
                $namedChanges = [];
                foreach ($changes as $attribute => $value) {
                    $namedChanges[(string) $attribute] = $value;
                }

                $logger = app(ActivityLogger::class)
                    ->useLog($model->getLogNameToUse())
                    ->event($eventName)
                    ->performedOn($model)
                    ->withChanges($namedChanges);

                $ownerId = $model->auditOwnerId();
                if ($ownerId !== null) {
                    $logger->withProperty('user_id', $ownerId);
                }

                $logger->log($description);

                $model->activitylogOptions = null;
            });
        });
    }
}
