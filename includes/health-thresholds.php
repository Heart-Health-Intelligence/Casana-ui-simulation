<?php
/**
 * Casana Health Thresholds
 * Centralized definition of health status thresholds
 * 
 * This file defines the single source of truth for all health status
 * calculations across PHP, JavaScript, and alert taxonomy.
 * 
 * Blood Pressure Classifications (per AHA guidelines):
 * - Normal: <120/<80 mmHg
 * - Elevated: 120-129/<80 mmHg 
 * - High Stage 1: 130-139/80-89 mmHg
 * - High Stage 2: ≥140/≥90 mmHg
 * - Hypertensive Crisis: >180/>120 mmHg
 * 
 * Status mapping for this app:
 * - good: Normal range
 * - warning: Elevated or mildly abnormal (monitor closely)
 * - alert: Requires clinical attention
 */

// Blood Pressure Thresholds
define('BP_NORMAL_SYSTOLIC_MAX', 119);      // < 120 is normal
define('BP_NORMAL_DIASTOLIC_MAX', 79);      // < 80 is normal
define('BP_ELEVATED_SYSTOLIC_MIN', 120);    // 120-129 is elevated
define('BP_ELEVATED_SYSTOLIC_MAX', 129);
define('BP_HIGH_STAGE1_SYSTOLIC_MIN', 130); // 130-139 is stage 1
define('BP_HIGH_STAGE1_DIASTOLIC_MIN', 80); // 80-89 is stage 1
define('BP_HIGH_STAGE2_SYSTOLIC_MIN', 140); // ≥140 is stage 2
define('BP_HIGH_STAGE2_DIASTOLIC_MIN', 90); // ≥90 is stage 2
define('BP_CRISIS_SYSTOLIC_MIN', 180);      // ≥180 is crisis
define('BP_CRISIS_DIASTOLIC_MIN', 120);     // ≥120 is crisis

// Low blood pressure thresholds
define('BP_LOW_SYSTOLIC_MAX', 90);          // <90 is hypotension
define('BP_LOW_DIASTOLIC_MAX', 60);         // <60 is hypotension

// SpO₂ (Oxygen Saturation) Thresholds
define('SPO2_NORMAL_MIN', 95);              // ≥95% is normal
define('SPO2_MILD_LOW_MIN', 92);            // 92-94% is mildly low (warning)
define('SPO2_CRITICAL_MAX', 91);            // <92% is concerning (alert)
define('SPO2_SEVERE_CRITICAL_MAX', 89);     // <90% is critical

// Heart Rate Thresholds (adult resting)
define('HR_NORMAL_MIN', 60);                // 60-100 is normal adult range
define('HR_NORMAL_MAX', 100);
define('HR_BRADYCARDIA_SEVERE', 50);        // <50 is severe bradycardia
define('HR_TACHYCARDIA_SEVERE', 120);       // >120 is severe tachycardia

// Extended sit duration thresholds (seconds)
define('SIT_EXTENDED_MIN', 600);            // >10 minutes
define('SIT_VERY_EXTENDED_MIN', 1200);      // >20 minutes

/**
 * Health Thresholds as array (for JSON export to JS)
 */
$HEALTH_THRESHOLDS = [
    'bp' => [
        'normal' => [
            'systolic_max' => BP_NORMAL_SYSTOLIC_MAX,
            'diastolic_max' => BP_NORMAL_DIASTOLIC_MAX
        ],
        'elevated' => [
            'systolic_min' => BP_ELEVATED_SYSTOLIC_MIN,
            'systolic_max' => BP_ELEVATED_SYSTOLIC_MAX
        ],
        'high_stage1' => [
            'systolic_min' => BP_HIGH_STAGE1_SYSTOLIC_MIN,
            'diastolic_min' => BP_HIGH_STAGE1_DIASTOLIC_MIN
        ],
        'high_stage2' => [
            'systolic_min' => BP_HIGH_STAGE2_SYSTOLIC_MIN,
            'diastolic_min' => BP_HIGH_STAGE2_DIASTOLIC_MIN
        ],
        'crisis' => [
            'systolic_min' => BP_CRISIS_SYSTOLIC_MIN,
            'diastolic_min' => BP_CRISIS_DIASTOLIC_MIN
        ],
        'low' => [
            'systolic_max' => BP_LOW_SYSTOLIC_MAX,
            'diastolic_max' => BP_LOW_DIASTOLIC_MAX
        ]
    ],
    'spo2' => [
        'normal_min' => SPO2_NORMAL_MIN,
        'mild_low_min' => SPO2_MILD_LOW_MIN,
        'critical_max' => SPO2_CRITICAL_MAX,
        'severe_critical_max' => SPO2_SEVERE_CRITICAL_MAX
    ],
    'hr' => [
        'normal_min' => HR_NORMAL_MIN,
        'normal_max' => HR_NORMAL_MAX,
        'bradycardia_severe' => HR_BRADYCARDIA_SEVERE,
        'tachycardia_severe' => HR_TACHYCARDIA_SEVERE
    ],
    'sit_duration' => [
        'extended_min' => SIT_EXTENDED_MIN,
        'very_extended_min' => SIT_VERY_EXTENDED_MIN
    ]
];

/**
 * Status level constants for consistent mapping
 */
define('STATUS_GOOD', 'good');
define('STATUS_WARNING', 'warning');
define('STATUS_ALERT', 'alert');

/**
 * Get health status from vital data using centralized thresholds
 * 
 * @param array $data Vital signs data (bp_systolic, bp_diastolic, blood_oxygenation, heart_rate)
 * @return string 'good', 'warning', or 'alert'
 */
function getHealthStatusFromVitals($data) {
    $systolic = isset($data['bp_systolic']) ? (int)$data['bp_systolic'] : 0;
    $diastolic = isset($data['bp_diastolic']) ? (int)$data['bp_diastolic'] : 0;
    $spo2 = isset($data['blood_oxygenation']) ? (float)$data['blood_oxygenation'] : 100;
    $hr = isset($data['heart_rate']) ? (int)$data['heart_rate'] : 75;
    
    // Check for explicit HTN flag from API
    if (isset($data['htn']) && $data['htn'] === true) {
        return STATUS_ALERT;
    }
    
    // Blood Pressure checks
    // Hypertensive crisis
    if ($systolic >= BP_CRISIS_SYSTOLIC_MIN || $diastolic >= BP_CRISIS_DIASTOLIC_MIN) {
        return STATUS_ALERT;
    }
    // High Stage 2 (≥140/≥90)
    if ($systolic >= BP_HIGH_STAGE2_SYSTOLIC_MIN || $diastolic >= BP_HIGH_STAGE2_DIASTOLIC_MIN) {
        return STATUS_ALERT;
    }
    // High Stage 1 (130-139/80-89)
    if ($systolic >= BP_HIGH_STAGE1_SYSTOLIC_MIN || $diastolic >= BP_HIGH_STAGE1_DIASTOLIC_MIN) {
        return STATUS_ALERT;
    }
    // Hypotension
    if ($systolic > 0 && $systolic < BP_LOW_SYSTOLIC_MAX) {
        return STATUS_WARNING;
    }
    
    // SpO2 checks
    if ($spo2 < SPO2_MILD_LOW_MIN) {
        return STATUS_ALERT;
    }
    if ($spo2 < SPO2_NORMAL_MIN) {
        return STATUS_WARNING;
    }
    
    // Heart rate checks  
    if ($hr < HR_BRADYCARDIA_SEVERE || $hr > HR_TACHYCARDIA_SEVERE) {
        return STATUS_ALERT;
    }
    if ($hr < HR_NORMAL_MIN || $hr > HR_NORMAL_MAX) {
        return STATUS_WARNING;
    }
    
    // Elevated BP (120-129/<80) - warning only, doesn't require provider
    if ($systolic >= BP_ELEVATED_SYSTOLIC_MIN && $systolic <= BP_ELEVATED_SYSTOLIC_MAX && $diastolic < BP_HIGH_STAGE1_DIASTOLIC_MIN) {
        return STATUS_WARNING;
    }
    
    return STATUS_GOOD;
}

/**
 * Get detailed BP classification
 * 
 * @param int $systolic Systolic BP
 * @param int $diastolic Diastolic BP
 * @return array ['status' => 'normal'|'elevated'|'high'|'critical', 'stage' => string, 'description' => string]
 */
function getBPClassification($systolic, $diastolic) {
    if ($systolic >= BP_CRISIS_SYSTOLIC_MIN || $diastolic >= BP_CRISIS_DIASTOLIC_MIN) {
        return [
            'status' => 'critical',
            'stage' => 'Hypertensive Crisis',
            'description' => 'Seek immediate medical attention'
        ];
    }
    
    if ($systolic >= BP_HIGH_STAGE2_SYSTOLIC_MIN || $diastolic >= BP_HIGH_STAGE2_DIASTOLIC_MIN) {
        return [
            'status' => 'high',
            'stage' => 'High Stage 2',
            'description' => 'Contact your care provider'
        ];
    }
    
    if ($systolic >= BP_HIGH_STAGE1_SYSTOLIC_MIN || $diastolic >= BP_HIGH_STAGE1_DIASTOLIC_MIN) {
        return [
            'status' => 'high',
            'stage' => 'High Stage 1',
            'description' => 'Consider discussing with provider'
        ];
    }
    
    if ($systolic >= BP_ELEVATED_SYSTOLIC_MIN && $diastolic < BP_HIGH_STAGE1_DIASTOLIC_MIN) {
        return [
            'status' => 'elevated',
            'stage' => 'Elevated',
            'description' => 'Monitor and maintain healthy habits'
        ];
    }
    
    if ($systolic > 0 && $systolic < BP_LOW_SYSTOLIC_MAX) {
        return [
            'status' => 'low',
            'stage' => 'Low',
            'description' => 'May cause dizziness; discuss if persistent'
        ];
    }
    
    return [
        'status' => 'normal',
        'stage' => 'Normal',
        'description' => 'Keep up the good work'
    ];
}

/**
 * Get SpO2 classification
 * 
 * @param float $spo2 Oxygen saturation percentage
 * @return array ['status' => string, 'description' => string]
 */
function getSpO2Classification($spo2) {
    if ($spo2 < SPO2_SEVERE_CRITICAL_MAX + 1) {
        return [
            'status' => 'critical',
            'description' => 'Critically low; seek immediate help'
        ];
    }
    
    if ($spo2 < SPO2_MILD_LOW_MIN) {
        return [
            'status' => 'low',
            'description' => 'Below normal; contact provider if persistent'
        ];
    }
    
    if ($spo2 < SPO2_NORMAL_MIN) {
        return [
            'status' => 'mild_low',
            'description' => 'Slightly below normal; monitor closely'
        ];
    }
    
    return [
        'status' => 'normal',
        'description' => 'Normal oxygen levels'
    ];
}

/**
 * Get heart rate classification
 * 
 * @param int $hr Heart rate in BPM
 * @return array ['status' => string, 'description' => string]
 */
function getHRClassification($hr) {
    if ($hr < HR_BRADYCARDIA_SEVERE) {
        return [
            'status' => 'very_low',
            'description' => 'Very low heart rate; consult provider'
        ];
    }
    
    if ($hr < HR_NORMAL_MIN) {
        return [
            'status' => 'low',
            'description' => 'Lower than average; may be normal for some'
        ];
    }
    
    if ($hr > HR_TACHYCARDIA_SEVERE) {
        return [
            'status' => 'very_high',
            'description' => 'Very high heart rate; consult provider'
        ];
    }
    
    if ($hr > HR_NORMAL_MAX) {
        return [
            'status' => 'high',
            'description' => 'Higher than average resting rate'
        ];
    }
    
    return [
        'status' => 'normal',
        'description' => 'Normal resting heart rate'
    ];
}

/**
 * Export thresholds as JavaScript for client-side use
 * Call this in footer.php to make thresholds available globally
 */
function exportHealthThresholdsJS() {
    global $HEALTH_THRESHOLDS;
    
    return '<script>
window.HEALTH_THRESHOLDS = ' . json_encode($HEALTH_THRESHOLDS) . ';
window.STATUS_GOOD = "good";
window.STATUS_WARNING = "warning";
window.STATUS_ALERT = "alert";
</script>';
}
?>
