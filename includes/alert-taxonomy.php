<?php
/**
 * Casana Alert Taxonomy
 * Centralized definition of alert types, severity, labels, and styling
 * 
 * Use this across Dashboard, Alerts, and Patient views to ensure consistency
 * Thresholds are pulled from health-thresholds.php for consistency
 */

// Include centralized health thresholds
require_once __DIR__ . '/health-thresholds.php';

// Alert type definitions
// Each alert type has: id, label, short_label, severity (1-4), icon, color_class
// Thresholds are dynamically built from health-thresholds.php constants
$ALERT_TAXONOMY = [
    'hypertension' => [
        'id' => 'hypertension',
        'label' => 'Hypertension',
        'short_label' => 'HTN',
        'description' => 'Blood pressure above normal threshold',
        'severity' => 3, // 1=low, 2=medium, 3=high, 4=critical
        'icon' => 'bi-heart-pulse',
        'color' => 'danger',
        'color_class' => 'bg-danger-soft',
        'text_class' => 'text-danger',
        'threshold' => 'Systolic ≥' . BP_HIGH_STAGE2_SYSTOLIC_MIN . ' or Diastolic ≥' . BP_HIGH_STAGE2_DIASTOLIC_MIN . ' mmHg'
    ],
    'low_spo2' => [
        'id' => 'low_spo2',
        'label' => 'Low Oxygen',
        'short_label' => 'Low O₂',
        'description' => 'Blood oxygen saturation below normal',
        'severity' => 3, // High (changed from 4 - only critical below 90)
        'icon' => 'bi-lungs',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'SpO₂ <' . SPO2_NORMAL_MIN . '%'
    ],
    'extended_sit' => [
        'id' => 'extended_sit',
        'label' => 'Extended Sit',
        'short_label' => 'Long Sit',
        'description' => 'Prolonged sitting duration',
        'severity' => 1, // Low
        'icon' => 'bi-clock-history',
        'color' => 'info',
        'color_class' => 'bg-info-soft',
        'text_class' => 'text-info',
        'threshold' => 'Duration >' . (SIT_EXTENDED_MIN / 60) . ' minutes'
    ],
    'bradycardia' => [
        'id' => 'bradycardia',
        'label' => 'Low Heart Rate',
        'short_label' => 'Brady',
        'description' => 'Heart rate below normal',
        'severity' => 2,
        'icon' => 'bi-heart',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'HR <' . HR_BRADYCARDIA_SEVERE . ' bpm'
    ],
    'tachycardia' => [
        'id' => 'tachycardia',
        'label' => 'High Heart Rate',
        'short_label' => 'Tachy',
        'description' => 'Heart rate above normal',
        'severity' => 2,
        'icon' => 'bi-heart-pulse',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'HR >' . HR_NORMAL_MAX . ' bpm'
    ],
    'afib_suspected' => [
        'id' => 'afib_suspected',
        'label' => 'AFib Suspected',
        'short_label' => 'AFib?',
        'description' => 'Possible atrial fibrillation detected',
        'severity' => 3,
        'icon' => 'bi-activity',
        'color' => 'danger',
        'color_class' => 'bg-danger-soft',
        'text_class' => 'text-danger',
        'threshold' => 'Irregular rhythm pattern'
    ],
    'very_extended_sit' => [
        'id' => 'very_extended_sit',
        'label' => 'Very Extended Sit',
        'short_label' => 'Extended',
        'description' => 'Very prolonged sitting duration',
        'severity' => 2,
        'icon' => 'bi-clock-history',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'Duration >' . (SIT_VERY_EXTENDED_MIN / 60) . ' minutes'
    ],
    'critical_spo2' => [
        'id' => 'critical_spo2',
        'label' => 'Critical Oxygen',
        'short_label' => 'Critical O₂',
        'description' => 'Dangerously low blood oxygen',
        'severity' => 4,
        'icon' => 'bi-lungs',
        'color' => 'danger',
        'color_class' => 'bg-danger-soft',
        'text_class' => 'text-danger',
        'threshold' => 'SpO₂ <' . (SPO2_SEVERE_CRITICAL_MAX + 1) . '%'
    ],
    'severe_hypertension' => [
        'id' => 'severe_hypertension',
        'label' => 'Severe Hypertension',
        'short_label' => 'Severe HTN',
        'description' => 'Critically elevated blood pressure',
        'severity' => 4,
        'icon' => 'bi-heart-pulse-fill',
        'color' => 'danger',
        'color_class' => 'bg-danger-soft',
        'text_class' => 'text-danger',
        'threshold' => 'Systolic ≥' . BP_CRISIS_SYSTOLIC_MIN . ' or Diastolic ≥' . BP_CRISIS_DIASTOLIC_MIN . ' mmHg'
    ],
    'low_heart_rate' => [
        'id' => 'low_heart_rate',
        'label' => 'Low Heart Rate',
        'short_label' => 'Low HR',
        'description' => 'Heart rate below normal threshold',
        'severity' => 2,
        'icon' => 'bi-heart',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'HR <' . HR_BRADYCARDIA_SEVERE . ' bpm'
    ],
    'high_heart_rate' => [
        'id' => 'high_heart_rate',
        'label' => 'High Heart Rate',
        'short_label' => 'High HR',
        'description' => 'Heart rate above normal threshold',
        'severity' => 2,
        'icon' => 'bi-heart-pulse',
        'color' => 'warning',
        'color_class' => 'bg-warning-soft',
        'text_class' => 'text-warning',
        'threshold' => 'HR >' . HR_NORMAL_MAX . ' bpm'
    ]
];

// Severity labels and styling
$SEVERITY_LEVELS = [
    1 => ['label' => 'Low', 'class' => 'secondary', 'description' => 'Monitor but not urgent'],
    2 => ['label' => 'Medium', 'class' => 'warning', 'description' => 'Follow up recommended'],
    3 => ['label' => 'High', 'class' => 'danger', 'description' => 'Requires attention'],
    4 => ['label' => 'Critical', 'class' => 'danger', 'description' => 'Immediate action needed']
];

/**
 * Get alert info by reason code
 * Returns default values for unknown types
 */
function getAlertInfo($reason) {
    global $ALERT_TAXONOMY;
    
    if (isset($ALERT_TAXONOMY[$reason])) {
        return $ALERT_TAXONOMY[$reason];
    }
    
    // Default for unknown alert types
    return [
        'id' => $reason,
        'label' => ucfirst(str_replace('_', ' ', $reason)),
        'short_label' => ucfirst($reason),
        'description' => '',
        'severity' => 2,
        'icon' => 'bi-exclamation-triangle',
        'color' => 'secondary',
        'color_class' => 'bg-secondary-soft',
        'text_class' => 'text-secondary',
        'threshold' => ''
    ];
}

/**
 * Get severity info by level
 */
function getSeverityInfo($level) {
    global $SEVERITY_LEVELS;
    
    if (isset($SEVERITY_LEVELS[$level])) {
        return $SEVERITY_LEVELS[$level];
    }
    
    return $SEVERITY_LEVELS[2]; // Default to medium
}

/**
 * Render an alert badge HTML
 */
function renderAlertBadge($reason, $showIcon = true, $useShortLabel = true) {
    $info = getAlertInfo($reason);
    $label = $useShortLabel ? $info['short_label'] : $info['label'];
    $icon = $showIcon ? '<i class="bi ' . $info['icon'] . ' me-1"></i>' : '';
    
    return '<span class="badge ' . $info['color_class'] . ' ' . $info['text_class'] . '">' . $icon . htmlspecialchars($label) . '</span>';
}

/**
 * Get maximum severity from array of alert reasons
 */
function getMaxSeverity($reasons) {
    $maxSeverity = 0;
    
    foreach ($reasons as $reason) {
        $info = getAlertInfo($reason);
        if ($info['severity'] > $maxSeverity) {
            $maxSeverity = $info['severity'];
        }
    }
    
    return $maxSeverity;
}

/**
 * Sort alerts by severity (highest first)
 */
function sortAlertsBySeverity($alerts) {
    usort($alerts, function($a, $b) {
        $severityA = getMaxSeverity($a['alert_reasons'] ?? []);
        $severityB = getMaxSeverity($b['alert_reasons'] ?? []);
        
        if ($severityA === $severityB) {
            // Secondary sort by time (most recent first)
            return strtotime($b['sit_time'] ?? '') - strtotime($a['sit_time'] ?? '');
        }
        
        return $severityB - $severityA;
    });
    
    return $alerts;
}

/**
 * Count alerts by reason
 */
function countAlertsByReason($alerts) {
    $counts = [];
    
    foreach ($alerts as $alert) {
        if (isset($alert['alert_reasons'])) {
            foreach ($alert['alert_reasons'] as $reason) {
                if (!isset($counts[$reason])) {
                    $counts[$reason] = 0;
                }
                $counts[$reason]++;
            }
        }
    }
    
    return $counts;
}

/**
 * Export taxonomy as JavaScript object for client-side use
 */
function getAlertTaxonomyJS() {
    global $ALERT_TAXONOMY, $SEVERITY_LEVELS;
    
    return 'window.ALERT_TAXONOMY = ' . json_encode($ALERT_TAXONOMY) . ";\n" .
           'window.SEVERITY_LEVELS = ' . json_encode($SEVERITY_LEVELS) . ';';
}
?>
