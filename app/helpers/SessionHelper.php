<?php

class SessionHelper {
    public static function setFlash($name, $message, $type) {
        $_SESSION[$name] = [
            'message' => $message,
            'type'  => $type
        ];
    }

    public static function flash($name = 'pesan', $class = 'alert alert-success') {
        if (!empty($name)) {
            if (!empty($_SESSION[$name])) {
                $class = !empty($_SESSION[$name]['type']) ? $_SESSION[$name]['type'] : $class;
                // SECURITY: htmlspecialchars prevents stored/reflected XSS via flash messages
                $safeMessage = htmlspecialchars($_SESSION[$name]['message'], ENT_QUOTES, 'UTF-8');
                echo '<div class="'.$class.' alert-dismissible fade show shadow-sm" role="alert">
                        '.$safeMessage.'
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
                unset($_SESSION[$name]);
            }
        }
    }
}
