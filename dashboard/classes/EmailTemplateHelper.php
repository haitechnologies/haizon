<?php
/**
 * Email Template Helper Class
 * 
 * Handles email template processing, variable substitution,
 * and personalization
 */

class EmailTemplateHelper {
    private $mysqli;
    private $template;
    private $variables;

    public function __construct($mysqli, $templateId = null) {
        $this->mysqli = $mysqli;
        $this->variables = [];
        
        if (!empty($templateId)) {
            $this->loadTemplate($templateId);
        }
    }

    public function loadTemplate($templateId) {
        $templateId = (int)$templateId;
        $result = $this->mysqli->query(
            "SELECT * FROM `" . tbl_email_templates . "` WHERE id = $templateId LIMIT 1"
        );

        if ($result && $row = $result->fetch_array(MYSQLI_ASSOC)) {
            $this->template = $row;
            return true;
        }

        return false;
    }

    public function setVariables($variables) {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    public function addVariable($name, $value) {
        $this->variables[$name] = $value;
        return $this;
    }

    public function getProcessedSubject() {
        if (empty($this->template)) {
            return '';
        }

        return $this->processVariables($this->template['subject_default'] ?? '');
    }

    public function getProcessedHtmlBody() {
        if (empty($this->template)) {
            return '';
        }

        $html = $this->template['html_body'] ?? '';
        $html = $this->processVariables($html);
        
        // Add Click tracking to links
        $html = $this->addClickTracking($html);

        return $html;
    }

    public function getProcessedTextBody() {
        if (empty($this->template)) {
            return '';
        }

        return $this->processVariables($this->template['text_body'] ?? '');
    }

    private function processVariables($content) {
        foreach ($this->variables as $name => $value) {
            $placeholder = '{{' . $name . '}}';
            $content = str_replace($placeholder, htmlspecialchars($value, ENT_QUOTES), $content);

            // Also try with underscores
            $placeholder = '{{ ' . $name . ' }}';
            $content = str_replace($placeholder, htmlspecialchars($value, ENT_QUOTES), $content);
        }

        return $content;
    }

    private function addClickTracking($html) {
        // Extract tracking ID from variables
        $trackingId = $this->variables['tracking_id'] ?? '';
        if (empty($trackingId)) {
            return $html;
        }

        // Find all <a> tags and wrap with tracking
        preg_match_all('/<a\s+href=["\'](.*?)["\']/i', $html, $matches);

        foreach ($matches[1] as $url) {
            $trackingUrl = $GLOBALS['SETTINGS']['BASE_URL'] . "/dashboard/email_click.php?t=$trackingId&url=" . urlencode($url);
            $html = str_replace(
                'href="' . $url . '"',
                'href="' . $trackingUrl . '"',
                $html
            );
            $html = str_replace(
                "href='" . $url . "'",
                "href='" . $trackingUrl . "'",
                $html
            );
        }

        return $html;
    }

    public static function getDefaultTemplate($mysqli) {
        $result = $mysqli->query(
            "SELECT * FROM `" . tbl_email_templates . "` WHERE is_default = 1 ORDER BY id ASC LIMIT 1"
        );

        if ($result && $row = $result->fetch_array(MYSQLI_ASSOC)) {
            return new self($mysqli);
        }

        return null;
    }

    public static function systemTemplates($mysqli) {
        $result = $mysqli->query(
            "SELECT * FROM `" . tbl_email_templates . "` WHERE is_system = 1 ORDER BY name ASC"
        );

        $templates = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $templates[$row['id']] = $row;
        }

        return $templates;
    }
}
