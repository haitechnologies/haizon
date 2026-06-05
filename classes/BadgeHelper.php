<?php
/**
 * BadgeHelper - Centralized Badge Rendering
 * 
 * Standardizes badge styling across all DataTable handlers.
 * Eliminates code duplication and ensures consistent appearance.
 * 
 * Usage:
 *   BadgeHelper::success('Active')
 *   BadgeHelper::danger('Inactive', 'ph-x-circle')
 *   BadgeHelper::warning('Pending')
 *   BadgeHelper::info('System')
 * 
 * @package Helpers
 */

class BadgeHelper {
    
    /**
     * Success badge (green) - Use for: Active, Approved, Verified, Published
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class (e.g. 'ph-check-circle')
     * @return string HTML badge
     */
    public static function success($text, $icon = null) {
        return self::render($text, 'success', $icon);
    }
    
    /**
     * Danger badge (red) - Use for: Inactive, Rejected, Error, Not Approved
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class
     * @return string HTML badge
     */
    public static function danger($text, $icon = null) {
        return self::render($text, 'danger', $icon);
    }
    
    /**
     * Warning badge (yellow) - Use for: Pending, Approval Requested, Unverified
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class
     * @return string HTML badge
     */
    public static function warning($text, $icon = null) {
        return self::render($text, 'warning', $icon);
    }
    
    /**
     * Info badge (blue) - Use for: System, Additional Info
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class
     * @return string HTML badge
     */
    public static function info($text, $icon = null) {
        return self::render($text, 'info', $icon);
    }
    
    /**
     * Secondary badge (gray) - Use for: Default/No value, Unknown, Inactive flag
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class
     * @return string HTML badge
     */
    public static function secondary($text, $icon = null) {
        return self::render($text, 'secondary', $icon);
    }
    
    /**
     * Primary badge (blue variant) - Use for: Category, Tag, Custom info
     * 
     * @param string $text Badge text
     * @param string|null $icon Optional Phosphor icon class
     * @return string HTML badge
     */
    public static function primary($text, $icon = null) {
        return self::render($text, 'primary', $icon);
    }
    
    /**
     * Generic badge renderer
     * 
     * @param string $text Badge text
     * @param string $type Badge type (success, danger, warning, info, secondary, primary)
     * @param string|null $icon Optional icon
     * @return string HTML badge
     */
    private static function render($text, $type, $icon = null) {
        $iconHtml = '';
        if ($icon !== null) {
            $iconHtml = '<i class="' . htmlspecialchars($icon) . ' me-1"></i>';
        }
        
        $text = htmlspecialchars($text);
        
        return '<span class="badge bg-' . $type . ' bg-opacity-20 text-' . $type . '">' . 
               $iconHtml . $text . '</span>';
    }
}
?>
