<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Http\Requests\Concerns;

/**
 * Срезает разметку со свободного текста до валидации.
 *
 * Через API проходит несколько полей, которые человек пишет руками и которые
 * потом читает другой человек: метка счёта для вывода, ФИО и адрес в анкете
 * KYC, имя в профиле. React и Filament экранируют вывод сами, поэтому дыры тут
 * нет — но хранить `<script>` в имени клиента незачем: строка утекает в письма,
 * CSV-экспорт и логи, где экранирования уже никто не гарантирует.
 *
 * Порядок важен: чистим в prepareForValidation(), то есть ДО правил. Иначе
 * `max:100` считает длину вместе с тегами, и клиент получает отказ за текст,
 * которого он не писал.
 */
trait SanitizesFreeText
{
    /**
     * Ключи, с которых снимается разметка. Переопредели в конкретном запросе.
     *
     * @return list<string>
     */
    abstract protected function freeTextKeys(): array;

    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach ($this->freeTextKeys() as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $value = $this->input($key);

            if (! is_string($value)) {
                continue;
            }

            $clean[$key] = trim(strip_tags($value));
        }

        if ($clean !== []) {
            $this->merge($clean);
        }
    }
}
