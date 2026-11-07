<?php

namespace App\Features\Settings\Actions;

use App\Features\Audit\Services\AuditLogger;
use App\Models\FeatureFlag;

class UpdateFeatureFlagAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(FeatureFlag $featureFlag, array $attributes): FeatureFlag
    {
        $oldValues = $featureFlag->getAttributes();
        $featureFlag->update($attributes);
        $this->auditLogger->record('feature_flag.updated', $featureFlag, $oldValues, $featureFlag->getAttributes());

        return $featureFlag->refresh();
    }
}
