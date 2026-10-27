<?php

namespace App\Services;

class PageRevisionDiff
{
    public function compare(array $before, array $after): array
    {
        $differences = [];
        $this->collectDifferences($before, $after, '', $differences);

        return $differences;
    }

    private function collectDifferences(array $before, array $after, string $path, array &$differences): void
    {
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $currentPath = $path === '' ? (string) $key : "{$path}.{$key}";
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if (is_array($oldValue) && is_array($newValue)) {
                $this->collectDifferences($oldValue, $newValue, $currentPath, $differences);

                continue;
            }

            if ($oldValue !== $newValue) {
                $differences[] = [
                    'path' => $currentPath,
                    'before' => $this->format($oldValue),
                    'after' => $this->format($newValue),
                ];
            }
        }
    }

    private function format(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value)
            ? (string) $value
            : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
