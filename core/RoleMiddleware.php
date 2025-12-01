sure<?php
// Simple RoleMiddleware - uses a check_role() helper if available, otherwise expects AuthorizationService in global scope
class RoleMiddleware
{
    /**
     * $options should contain ['roles' => ['ROLE1','ROLE2']]
     */
    public function handle($request, $next, $options = [])
    {
        $roles = $options['roles'] ?? [];
        if (!empty($roles)) {
            if (function_exists('check_role')) {
                $fn = 'check_role';
                if (!$fn($roles)) {
                    header('Location: /403');
                    exit;
                }
            } else {
                // Fallback: try AuthorizationService from globals
                if (isset($GLOBALS['authorizationService']) && method_exists($GLOBALS['authorizationService'], 'currentUserHasAnyRole')) {
                    if (!$GLOBALS['authorizationService']->currentUserHasAnyRole($roles)) {
                        header('Location: /403');
                        exit;
                    }
                }
            }
        }

        return $next($request);
    }
}
