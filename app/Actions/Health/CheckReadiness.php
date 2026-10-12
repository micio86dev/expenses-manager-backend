<?php

declare(strict_types=1);

namespace App\Actions\Health;

use App\Exceptions\NotReadyException;
use App\Support\Health\ReadinessProbe;
use Throwable;

final readonly class CheckReadiness
{
    public function __construct(private ReadinessProbe $probe) {}

    /**
     * @throws NotReadyException
     */
    public function __invoke(): void
    {
        try {
            $this->probe->check();
        } catch (Throwable $exception) {
            throw new NotReadyException($exception);
        }
    }
}
